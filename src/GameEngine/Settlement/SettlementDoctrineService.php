<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Guild;
use App\Entity\App\Player;
use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementDoctrine;
use App\Enum\SettlementRank;
use App\GameEngine\Codex\WorldFactService;
use App\GameEngine\Guild\GuildSpendingAuthority;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doter un foyer d'un atelier de doctrine (FOY-13).
 *
 * L'axe qui divise tout le monde — Extraire / Preserver (GAME_WORLD § 6.2) —
 * cesse d'etre une couleur de faction et devient **un batiment qu'on voit sur
 * l'ecran de zone**.
 *
 * **Le contraste avec le type est le jalon.** Le type d'un foyer se *deduit* de
 * la frequentation : personne ne le choisit, et c'est voulu (FOY-01). La
 * doctrine est l'inverse exact — c'est la seule chose qu'une guilde decide
 * explicitement pour un lieu. D'ou les trois contraintes :
 *
 * 1. **Elle se paie**, sur le tresor, avec la meme autorite que tout retrait.
 * 2. **Elle ne se cumule pas** : une colonne, pas deux booleens. Le schema
 *    rend l'erreur impossible plutot que de la surveiller.
 * 3. **Elle se verrouille une maree.** Une doctrine qui se retourne a la
 *    semaine ne divise plus personne, et l'axe ne serait qu'un interrupteur.
 *
 * **Ce qu'elle ne fait pas.** Elle ne disparait jamais a la regression : un
 * foyer qui retombe garde son atelier. C'est la regle du patrimoine (FOY-05 /
 * FOY-10) — on borne ce qui reste a acquerir, on ne reprend pas ce qui est
 * acquis. Le rang minimum ne se verifie donc qu'a **l'adoption**.
 */
class SettlementDoctrineService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementDefinitionLoader $loader,
        private readonly WorldFactService $worldFactService,
        private readonly GuildSpendingAuthority $authority,
        private readonly SettlementDoctrineBonus $bonus,
    ) {
    }

    /**
     * Les deux ateliers, tels que cet ecran doit les montrer.
     *
     * Vide quand la zone n'a pas de foyer : il n'y a alors rien a orienter, et
     * afficher un choix impossible ne dirait rien d'utile.
     *
     * @return list<SettlementDoctrineOffer>
     */
    public function offersFor(?Player $player, Zone $zone, ?\DateTimeImmutable $now = null): array
    {
        $settlement = $this->settlementRepository->findOneByZone($zone);
        if (null === $settlement) {
            return [];
        }

        $now ??= new \DateTimeImmutable();
        $definition = $this->loader->load()['doctrine'];
        $lockedUntil = $this->lockedUntil($settlement, $definition['lock_days']);
        $blocked = $this->blockedReason($player, $settlement, $lockedUntil, $now, $definition);

        $offers = [];
        foreach (SettlementDoctrine::cases() as $doctrine) {
            $adopted = $settlement->getDoctrine() === $doctrine;

            $offers[] = new SettlementDoctrineOffer(
                $doctrine,
                $adopted,
                $definition['cost'],
                $adopted ? null : $blocked,
                $lockedUntil,
            );
        }

        return $offers;
    }

    /**
     * Adopte une doctrine et debite le tresor.
     *
     * @throws SettlementDoctrineException si la zone n'a pas de foyer, si le foyer est trop petit, si la doctrine est deja la, si le verrou tient encore, si le joueur n'a pas l'autorite ou si le tresor ne suit pas
     */
    public function adopt(Player $player, Zone $zone, SettlementDoctrine $doctrine, ?\DateTimeImmutable $now = null): Settlement
    {
        $now ??= new \DateTimeImmutable();
        $definition = $this->loader->load()['doctrine'];

        $settlement = $this->settlementRepository->findOneByZone($zone);
        if (null === $settlement) {
            throw new SettlementDoctrineException('game.zone.doctrine.error.no_settlement');
        }

        if ($settlement->getDoctrine() === $doctrine) {
            // Idempotence : repayer pour ce qu'on a deja n'achete rien.
            throw new SettlementDoctrineException('game.zone.doctrine.error.already_adopted');
        }

        if (!$settlement->getRank()->isAtLeast($definition['minimum_rank'])) {
            throw new SettlementDoctrineException('game.zone.doctrine.error.settlement_too_small');
        }

        $lockedUntil = $this->lockedUntil($settlement, $definition['lock_days']);
        if (null !== $lockedUntil && $lockedUntil > $now) {
            throw new SettlementDoctrineException('game.zone.doctrine.error.locked');
        }

        $guild = $this->authorizedGuild($player);
        if ($guild->getGilsTreasury() < $definition['cost']) {
            throw new SettlementDoctrineException('game.zone.doctrine.error.treasury_too_low');
        }

        $previous = $settlement->getDoctrine();

        $guild->addGilsTreasury(-$definition['cost']);
        $settlement->adoptDoctrine($doctrine, $now);

        $this->entityManager->flush();

        // La memoire du lecteur de bonus porte l'ancienne doctrine : sans cet
        // oubli, la zone continuerait de rendre l'ancien facteur jusqu'a la fin
        // de la requete — le joueur paierait et ne verrait rien changer.
        $this->bonus->forget();

        $this->announce($zone, $doctrine, $previous, $guild, $definition['cost'], $now);

        return $settlement;
    }

    /**
     * @throws SettlementDoctrineException
     */
    private function authorizedGuild(Player $player): Guild
    {
        [$guild, $reason] = $this->authority->resolve($player);
        if (null === $guild) {
            throw new SettlementDoctrineException('game.zone.doctrine.error.' . $reason);
        }

        return $guild;
    }

    /**
     * Jusqu'a quand la doctrine en place tient, ou `null` s'il n'y en a pas.
     */
    private function lockedUntil(Settlement $settlement, int $lockDays): ?\DateTimeImmutable
    {
        $since = $settlement->getDoctrineSince();

        return null === $since ? null : $since->modify(sprintf('+%d days', $lockDays));
    }

    /**
     * Pourquoi le bouton manque, ou `null` s'il est la.
     *
     * L'ordre des motifs est celui de la lecture : ce qui tient au **lieu**
     * avant ce qui tient au **joueur**. Annoncer « votre rang ne suffit pas »
     * sur un Campement qui n'a de toute facon pas d'atelier ferait chercher au
     * joueur un pouvoir qui ne changerait rien.
     *
     * @param array{minimum_rank: SettlementRank, cost: int, lock_days: int, foundry: array{gather_bonus: float, paleness_multiplier: float}, readers: array{lore_multiplier: float, paleness_multiplier: float}} $definition
     */
    private function blockedReason(?Player $player, Settlement $settlement, ?\DateTimeImmutable $lockedUntil, \DateTimeImmutable $now, array $definition): ?string
    {
        if (!$settlement->getRank()->isAtLeast($definition['minimum_rank'])) {
            return 'game.zone.doctrine.error.settlement_too_small';
        }

        if (null !== $lockedUntil && $lockedUntil > $now) {
            return 'game.zone.doctrine.error.locked';
        }

        if (null === $player) {
            return 'game.zone.doctrine.error.' . GuildSpendingAuthority::REASON_NO_GUILD;
        }

        [$guild, $reason] = $this->authority->resolve($player);
        if (null === $guild) {
            return 'game.zone.doctrine.error.' . $reason;
        }

        return $guild->getGilsTreasury() >= $definition['cost'] ? null : 'game.zone.doctrine.error.treasury_too_low';
    }

    /**
     * La mention publique.
     *
     * Une doctrine est un acte de gouvernement visible par definition — c'est
     * meme tout son interet. Le slug porte la date : changer d'atelier est un
     * second fait, pas une correction du premier.
     */
    private function announce(Zone $zone, SettlementDoctrine $doctrine, ?SettlementDoctrine $previous, Guild $guild, int $cost, \DateTimeImmutable $now): void
    {
        $description = null === $previous
            ? sprintf(
                'La guilde « %s » a verse %d Gils pour doter %s d\'un %s. Le lieu a désormais une doctrine, et elle se voit.',
                $guild->getName(),
                $cost,
                $zone->getName(),
                mb_strtolower($doctrine->label()),
            )
            : sprintf(
                'La guilde « %s » a verse %d Gils pour remplacer l\'%s de %s par un %s. Le lieu a changé de camp.',
                $guild->getName(),
                $cost,
                mb_strtolower($previous->label()),
                $zone->getName(),
                mb_strtolower($doctrine->label()),
            );

        $this->worldFactService->recordWorldFact(
            sprintf('doctrine_%s_%s_%s', $zone->getSlug(), $doctrine->value, $now->format('Y-m-d')),
            sprintf('%s — %s', $doctrine->label(), $zone->getName()),
            $description,
            $guild->getName(),
        );
    }
}
