<?php

namespace App\GameEngine\GameMaster;

use App\Entity\App\GameEvent;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\Game\Monster;
use App\Event\Game\GameEventActivatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Les gestes d'animation, depuis le jeu.
 *
 * Tout existait deja — mais dans `/admin`, donc sur un second ecran, donc mal :
 * pendant une soiree en direct, changer d'onglet pour faire apparaitre trois
 * loups, c'est trois minutes ou l'animateur n'est plus dans sa zone. Les memes
 * gestes sont ici, contextualises par la zone ou se tient le MJ.
 *
 * Deux garde-fous tenus a l'entree de chaque methode :
 *  - le geste est refuse a qui n'est pas MJ, meme si la route l'a deja verifie ;
 *  - il est **journalise** (`GameMasterJournal`). Une animation sans trace est
 *    une animation qu'on ne pourra pas expliquer.
 */
class GameMasterAnimationService
{
    /**
     * Plafond par geste. Non pas une regle de jeu, un garde-fou : une faute de
     * frappe ne doit pas peupler une zone de deux cents monstres.
     */
    public const MAX_SPAWN_COUNT = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly GameMasterJournal $journal,
    ) {
    }

    /**
     * Fait apparaitre des monstres dans une zone.
     *
     * Les mobs poses sont ordinaires : meme vie, meme niveau, meme butin que
     * ceux du vivier. Un monstre d'animation qui frapperait plus fort qu'un
     * autre rendrait la soiree illisible pour ceux qui la jouent.
     *
     * @return list<Mob>
     *
     * @throws GameMasterRestrictionException
     */
    public function spawnMonsters(Player $gameMaster, Zone $zone, Monster $monster, int $count = 1): array
    {
        $this->assertGameMaster($gameMaster);

        $count = max(1, min($count, self::MAX_SPAWN_COUNT));

        $spawned = [];
        for ($i = 0; $i < $count; ++$i) {
            $mob = new Mob();
            $mob->setMonster($monster);
            $mob->setZone($zone);
            $mob->setLevel($monster->getLevel());
            $mob->setLife((int) $monster->getLife());
            // Champ herite de l'ere carte (regle #7) : la position de reference
            // est la zone. Non nullable en base, d'ou cette valeur neutre.
            $mob->setCoordinates('0.0');

            $this->entityManager->persist($mob);
            $spawned[] = $mob;
        }

        $this->entityManager->flush();

        $this->journal->record($gameMaster, 'spawn', sprintf(
            '%d × %s dans %s',
            $count,
            $monster->getName(),
            $zone->getName(),
        ), [
            'zone' => $zone->getSlug(),
            'monster' => $monster->getName(),
            'count' => $count,
        ]);

        return $spawned;
    }

    /**
     * Lance immediatement un evenement, en conservant sa duree prevue.
     *
     * Reprend le geste de `/admin/events/{id}/launch-now` : la fenetre est
     * translatee a maintenant plutot qu'etiree, pour qu'un evenement d'une heure
     * dure une heure quel que soit le moment ou on le declenche.
     *
     * @throws GameMasterRestrictionException
     */
    public function launchEvent(Player $gameMaster, GameEvent $event): void
    {
        $this->assertGameMaster($gameMaster);

        if (GameEvent::STATUS_ACTIVE === $event->getStatus()) {
            throw new GameMasterRestrictionException('Cet evenement est deja en cours.');
        }

        $duration = $event->getEndsAt()->getTimestamp() - $event->getStartsAt()->getTimestamp();
        if ($duration <= 0) {
            $duration = 3600;
        }

        $now = new \DateTime();
        $event->setStartsAt($now);
        $event->setEndsAt((clone $now)->modify(sprintf('+%d seconds', $duration)));
        $event->setStatus(GameEvent::STATUS_ACTIVE);
        $event->setUpdatedAt($now);

        $this->entityManager->flush();

        // Meme evenement de domaine que le lancement admin : les boss de zone,
        // les invasions et l'annonce temps reel s'y branchent deja.
        $this->eventDispatcher->dispatch(
            new GameEventActivatedEvent($event),
            GameEventActivatedEvent::NAME,
        );

        $this->journal->record($gameMaster, 'event_launch', $event->getName(), [
            'event_id' => $event->getId(),
            'zone' => $event->getZone()?->getSlug(),
        ]);
    }

    /**
     * Clot un evenement en cours.
     *
     * @throws GameMasterRestrictionException
     */
    public function stopEvent(Player $gameMaster, GameEvent $event): void
    {
        $this->assertGameMaster($gameMaster);

        if (GameEvent::STATUS_ACTIVE !== $event->getStatus()) {
            throw new GameMasterRestrictionException('Cet evenement n\'est pas en cours.');
        }

        $event->setStatus(GameEvent::STATUS_COMPLETED);
        $event->setEndsAt(new \DateTime());
        $event->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        $this->journal->record($gameMaster, 'event_stop', $event->getName(), [
            'event_id' => $event->getId(),
            'zone' => $event->getZone()?->getSlug(),
        ]);
    }

    /**
     * Evenements rattaches a une zone, quel que soit leur statut : c'est la
     * liste sur laquelle le MJ agit.
     *
     * @return list<GameEvent>
     */
    public function eventsForZone(Zone $zone): array
    {
        /** @var list<GameEvent> $events */
        $events = $this->entityManager->getRepository(GameEvent::class)->createQueryBuilder('e')
            ->andWhere('e.zone = :zone')
            ->setParameter('zone', $zone)
            ->orderBy('e.startsAt', 'DESC')
            ->setMaxResults(25)
            ->getQuery()
            ->getResult();

        return $events;
    }

    /**
     * @throws GameMasterRestrictionException
     */
    private function assertGameMaster(Player $player): void
    {
        if (!$player->isGameMaster()) {
            throw new GameMasterRestrictionException('Reserve aux maitres du jeu.');
        }
    }
}
