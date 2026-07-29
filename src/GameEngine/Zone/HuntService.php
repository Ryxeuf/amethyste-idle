<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Fight;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerJournalEntry;
use App\Entity\App\Zone;
use App\Entity\Game\Monster;
use App\GameEngine\Fight\Handler\FightHandler;
use App\Repository\MobRepository;
use App\Repository\PlayerBestiaryRepository;
use App\Repository\PlayerJournalEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Action Chasser (pivot PBBG, ZON-09).
 *
 * Contrairement a Explorer (tirage pondere), Chasser cible une proie precise :
 * un type de mob deja rencontre dans la zone (lien bestiaire). Coute de
 * l'energie d'action puis engage directement un combat contre un mob de ce
 * monstre — les tours de combat restent gratuits.
 *
 * Cibles = monstres qui ont a la fois (1) un mob vivant hors combat dans la
 * zone et (2) une entree dans le bestiaire du joueur (deja rencontre). Ajouter
 * une proie = peupler la zone et le bestiaire, pas du code.
 */
class HuntService
{
    public const DEFAULT_COST = 5;
    public const PARAM_COST = 'zone.energy.cost.hunt';

    private ?int $costCache = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActionEnergyManager $actionEnergyManager,
        private readonly ZoneTravelService $zoneTravelService,
        private readonly MobRepository $mobRepository,
        private readonly FightHandler $fightHandler,
        private readonly PlayerBestiaryRepository $bestiaryRepository,
        private readonly PlayerJournalEntryRepository $journalRepository,
    ) {
    }

    /**
     * Proies chassables dans la zone : monstres presents (mob vivant hors
     * combat) et deja rencontres par le joueur (bestiaire), tries par nom.
     *
     * @return list<Monster>
     */
    public function getHuntTargets(Player $player, Zone $zone): array
    {
        if ($zone->isSafe()) {
            return [];
        }

        $mobs = $this->mobRepository->findAvailableInZone($zone);
        if ([] === $mobs) {
            return [];
        }

        // Le bestiaire dit ce que le personnage a appris ; un MJ voit le vivier
        // tel qu'il est. Sans cela il ne pourrait pas verifier qu'une proie
        // signalee absente l'est vraiment.
        $revealsAll = $player->isGameMaster();
        $known = array_flip($this->bestiaryRepository->findMonsterIdsByPlayer($player));

        $targets = [];
        foreach ($mobs as $mob) {
            $monster = $mob->getMonster();
            $id = (int) $monster->getId();
            if (isset($targets[$id]) || (!$revealsAll && !isset($known[$id]))) {
                continue;
            }
            $targets[$id] = $monster;
        }

        $targets = array_values($targets);
        usort($targets, static fn (Monster $a, Monster $b): int => strcasecmp($a->getName(), $b->getName()));

        return $targets;
    }

    /**
     * Chasse une proie ciblee dans la zone courante et engage le combat.
     *
     * @throws ZoneActionException            si la chasse est refusee (cle de traduction en message)
     * @throws NotEnoughActionEnergyException si l'energie est insuffisante
     */
    public function hunt(Player $player, Monster $monster): Fight
    {
        $this->zoneTravelService->settleArrival($player, false);

        if ($player->isTraveling()) {
            throw new ZoneActionException('game.zone.hunt.error.traveling');
        }
        if (null !== $player->getFight()) {
            throw new ZoneActionException('game.zone.hunt.error.in_fight');
        }
        $zone = $player->getCurrentZone();
        if (null === $zone) {
            throw new ZoneActionException('game.zone.hunt.error.no_zone');
        }
        if ($zone->isSafe()) {
            throw new ZoneActionException('game.zone.hunt.error.safe_zone');
        }
        if (null === $this->bestiaryRepository->findOneByPlayerAndMonster($player, $monster)) {
            throw new ZoneActionException('game.zone.hunt.error.unknown_target');
        }

        $mob = $this->mobRepository->findAvailableInZoneForMonster($zone, $monster);
        if (null === $mob) {
            throw new ZoneActionException('game.zone.hunt.error.no_prey');
        }

        $this->actionEnergyManager->spend($player, $this->getHuntCost(), false);

        $fight = $this->fightHandler->startFight($player, $mob);

        $entry = new PlayerJournalEntry();
        $entry->setPlayer($player);
        $entry->setType(PlayerJournalEntry::TYPE_EXPLORATION);
        $entry->setMessage(sprintf('Chasse : traque de %s (%s)', $monster->getName(), $zone->getName()));
        $entry->setMetadata([
            'zone' => $zone->getSlug(),
            'action' => 'hunt',
            'monster' => $monster->getSlug(),
        ]);
        $this->entityManager->persist($entry);

        $this->entityManager->flush();
        $this->journalRepository->enforceEntryLimit($player);

        return $fight;
    }

    public function getHuntCost(): int
    {
        if (null !== $this->costCache) {
            return $this->costCache;
        }

        $parameter = $this->entityManager->getRepository(Parameter::class)
            ->findOneBy(['name' => self::PARAM_COST]);
        $value = null !== $parameter ? (int) $parameter->getValue() : self::DEFAULT_COST;

        return $this->costCache = $value >= 0 ? $value : self::DEFAULT_COST;
    }
}
