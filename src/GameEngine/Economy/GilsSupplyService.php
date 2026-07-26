<?php

declare(strict_types=1);

namespace App\GameEngine\Economy;

use App\Entity\App\GilsSupplySnapshot;
use App\Enum\AuctionStatus;
use App\Enum\CraftOrderStatus;
use App\Repository\GilsSupplySnapshotRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Mesure de la masse monetaire et detection d'inflation (ECO-15).
 *
 * Voir `GilsSupplySnapshot` pour le raisonnement : on mesure un **stock**, pas
 * un flux, parce que le flux passe par 26 appelants directs et que le stock
 * repond a la meme question.
 *
 * L'alerte compare la masse **par personnage** entre deux releves. Le total brut
 * ne dit rien : il monte quand la population monte, ce qui n'est pas de
 * l'inflation. Par tete, une hausse soutenue signifie que les robinets
 * (recolte, PvE, quetes) versent plus que les puits (reparation, loyers, taxe
 * sans guilde, voyage rapide) n'absorbent.
 */
final class GilsSupplyService
{
    /**
     * Hausse par tete au-dela de laquelle on alerte, en pourcentage sur 7 jours.
     *
     * Calibre a partir de rien — aucune mesure n'existe encore. C'est un point
     * de depart declare, a corriger des que la premiere semaine de releves est
     * lisible, pas une valeur derivee.
     */
    public const WEEKLY_ALERT_PERCENT = 15.0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GilsSupplySnapshotRepository $snapshots,
    ) {
    }

    /**
     * Compte la masse monetaire actuelle, sans rien ecrire.
     */
    public function measure(): GilsSupplyMeasure
    {
        return new GilsSupplyMeasure(
            playerGils: $this->sum('SELECT COALESCE(SUM(p.gils), 0) FROM player p'),
            guildGils: $this->sum('SELECT COALESCE(SUM(g.gils_treasury), 0) FROM guild g'),
            shopGils: $this->sum('SELECT COALESCE(SUM(s.vault_gils), 0) FROM player_shop s'),
            escrowGils: $this->auctionEscrow() + $this->craftOrderEscrow(),
            playerCount: $this->sum('SELECT COUNT(*) FROM player'),
        );
    }

    /**
     * Enregistre un releve.
     */
    public function capture(?\DateTimeImmutable $at = null): GilsSupplySnapshot
    {
        $measure = $this->measure();

        $snapshot = new GilsSupplySnapshot(
            playerGils: $measure->playerGils,
            guildGils: $measure->guildGils,
            shopGils: $measure->shopGils,
            escrowGils: $measure->escrowGils,
            playerCount: $measure->playerCount,
            capturedAt: $at,
        );

        $this->entityManager->persist($snapshot);
        $this->entityManager->flush();

        return $snapshot;
    }

    /**
     * Variation de la masse par tete depuis le dernier releve d'il y a N jours.
     *
     * Renvoie `null` tant qu'il n'y a pas deux points a comparer — un seul
     * releve ne dit rien d'une tendance, et inventer un zero de depart ferait
     * apparaitre une inflation infinie au premier jour.
     */
    public function perCapitaTrend(int $days = 7): ?GilsSupplyTrend
    {
        $current = $this->snapshots->latest();
        if (null === $current) {
            return null;
        }

        $earlier = $this->snapshots->latestBefore(
            $current->getCapturedAt()->modify(sprintf('-%d days', $days)),
        );
        if (null === $earlier || $earlier->getId() === $current->getId()) {
            return null;
        }

        return new GilsSupplyTrend($earlier, $current, $days);
    }

    /**
     * Mises immobilisees par les encheres en cours.
     *
     * Une vente a prix fixe n'immobilise rien cote acheteur — les Gils ne
     * quittent sa bourse qu'au moment de l'achat. La condition retenue n'est
     * donc pas le **type** de l'annonce mais la presence d'une mise : dans
     * `AuctionManager::placeBid()`, poser `current_bid` et retirer les Gils du
     * misant sont le meme geste. Un type futur qui accepterait des mises serait
     * compte sans qu'on ait a y penser.
     */
    private function auctionEscrow(): int
    {
        return $this->sum(
            'SELECT COALESCE(SUM(l.current_bid), 0) FROM auction_listing l
             WHERE l.status = :status AND l.current_bid IS NOT NULL',
            ['status' => AuctionStatus::Active->value],
        );
    }

    /**
     * Commissions immobilisees par les commandes vivantes.
     */
    private function craftOrderEscrow(): int
    {
        return $this->sum(
            'SELECT COALESCE(SUM(o.commission), 0) FROM craft_order o WHERE o.status IN (:statuses)',
            ['statuses' => [CraftOrderStatus::Open->value, CraftOrderStatus::Claimed->value]],
            ['statuses' => ArrayParameterType::STRING],
        );
    }

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $types
     */
    private function sum(string $sql, array $parameters = [], array $types = []): int
    {
        return (int) $this->entityManager->getConnection()
            ->executeQuery($sql, $parameters, $types)
            ->fetchOne();
    }
}
