<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\App\Zone;
use App\Entity\Game\Faction;
use App\Enum\ReputationTier;
use App\Enum\SettlementType;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ce qu'un joueur Hostile subit, la ou il le subit (FAC-03).
 *
 * Le catalogue dit ce qu'une maison fait payer ; le resolveur dit si **ce
 * joueur, ici, maintenant** le paie. Deux consequences actives : la surcharge
 * des Marchands au comptoir PNJ, et les fouilles de l'Ordre a l'entree des
 * zones a foyer Bastion — un surcout de temps, jamais un refus.
 *
 * **Jamais Hostile par defaut.** Un joueur qui n'a jamais rencontre une
 * faction n'a pas de ligne de reputation, donc pas de palier, donc rien a
 * payer : l'hostilite se gagne par le geste oppose, elle ne s'herite pas.
 */
class HostileConsequenceResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HostileConsequenceCatalog $catalog,
        private readonly SettlementRepository $settlementRepository,
    ) {
    }

    /**
     * La surcharge des Marchands sur un achat PNJ, en pourcentage (0 si le
     * joueur n'est pas Hostile chez eux).
     */
    public function shopSurchargePercent(Player $player): int
    {
        if (!$this->isHostileToward($player, 'marchands')) {
            return 0;
        }

        return $this->catalog->percentFor('marchands', 'shop_surcharge');
    }

    /**
     * Le surcout de voyage des fouilles de l'Ordre, en pourcentage — seulement
     * vers une zone dont le foyer est un Bastion, seulement si le joueur est
     * Hostile aux Chevaliers. Le voyage lui-meme n'est jamais refuse.
     */
    public function travelSurchargePercent(Player $player, Zone $destination): int
    {
        if ($this->settlementRepository->findOneByZone($destination)?->getType() !== SettlementType::Bastion) {
            return 0;
        }
        if (!$this->isHostileToward($player, 'chevaliers')) {
            return 0;
        }

        return $this->catalog->percentFor('chevaliers', 'bastion_travel_surcharge');
    }

    /**
     * FAC-04a : le plancher d'achat du cristal se ferme aux Hostiles de la
     * Fonderie — si et seulement si la table declarative porte la consequence.
     * C'est le crochet `buyback_floor_closed` de FAC-03 qui prend vie.
     */
    public function isCrystalBuybackClosed(Player $player): bool
    {
        if (!$this->catalog->hasConsequence('fonderie', 'buyback_floor_closed')) {
            return false;
        }

        return $this->isHostileToward($player, 'fonderie');
    }

    /**
     * Hostile = ligne de reputation strictement negative. Une faction pas
     * encore semee, ou jamais rencontree, ne rend jamais Hostile.
     */
    public function isHostileToward(Player $player, string $factionSlug): bool
    {
        $faction = $this->entityManager->getRepository(Faction::class)->findOneBy(['slug' => $factionSlug]);
        if (null === $faction) {
            return false;
        }

        $playerFaction = $this->entityManager->getRepository(PlayerFaction::class)->findOneBy([
            'player' => $player,
            'faction' => $faction,
        ]);

        return null !== $playerFaction && ReputationTier::Hostile === $playerFaction->getTier();
    }
}
