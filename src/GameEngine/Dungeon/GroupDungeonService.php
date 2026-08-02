<?php

namespace App\GameEngine\Dungeon;

use App\Entity\App\GroupDungeonMember;
use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\Game\Dungeon;
use App\GameEngine\Party\PartyManager;
use App\Repository\GroupDungeonRunRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Donjon de groupe semi-synchrone (pivot PBBG, ZON-19) — modele & formation.
 *
 * Un leader forme un groupe parmi les joueurs presents dans sa zone (systeme
 * `Party` existant) puis lance le donjon : un `GroupDungeonRun` est cree avec
 * un instantane des membres. Cette premiere livraison couvre la formation et la
 * garde d'unicite ; la boucle de combat tour par tour partagee (delai par tour,
 * action par defaut) et l'experience temps reel Mercure sont livrees dans les
 * sous-jalons suivants.
 */
class GroupDungeonService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupDungeonRunRepository $runRepository,
        private readonly PartyManager $partyManager,
        private readonly DungeonManager $dungeonManager,
    ) {
    }

    /**
     * Donjons lancables depuis une zone, dans l'ordre de leur exigence
     * d'experience. Renvoie la donnee brute : l'eligibilite du joueur est
     * evaluee par `getLaunchBlocker()`.
     *
     * DON-01 : un seul modele — les donjons solo (`maxPlayers: 1`) vivent
     * dans le meme graphe et s'offrent par le meme ecran de zone.
     *
     * @return list<Dungeon>
     */
    public function findOfferedInZone(Zone $zone): array
    {
        /** @var list<Dungeon> $dungeons */
        $dungeons = $this->entityManager->getRepository(Dungeon::class)
            ->createQueryBuilder('d')
            ->where('d.zone = :zone')
            ->setParameter('zone', $zone)
            ->orderBy('d.minLevel', 'ASC')
            ->addOrderBy('d.slug', 'ASC')
            ->getQuery()
            ->getResult();

        return $dungeons;
    }

    public function getActiveRunForPlayer(Player $player): ?GroupDungeonRun
    {
        return $this->runRepository->findActiveForPlayer($player);
    }

    /**
     * Lance un donjon de groupe : le leader et tous les membres de sa `Party`
     * presents dans sa zone forment le groupe.
     *
     * @throws GroupDungeonException si la formation est refusee (cle de traduction en message)
     */
    public function launch(Player $leader, Dungeon $dungeon): GroupDungeonRun
    {
        $zone = $leader->getCurrentZone();
        if (null === $zone) {
            throw new GroupDungeonException('game.zone.dungeon.error.no_zone');
        }

        $participants = $this->resolveParticipants($leader, $dungeon, $zone);

        $run = new GroupDungeonRun($dungeon, $leader, $zone);
        $run->setStatus(GroupDungeonRun::STATUS_IN_PROGRESS);
        foreach ($participants as $participant) {
            $run->addMember(new GroupDungeonMember($run, $participant));
        }

        $this->entityManager->persist($run);
        $this->entityManager->flush();

        return $run;
    }

    /**
     * Cle de traduction de l'erreur qui bloquerait `launch()`, ou null si le
     * lancement passerait.
     *
     * L'ecran de zone s'appuie sur cette methode plutot que de reimplementer les
     * regles de formation : le bouton propose ne peut donc pas deriver de ce que
     * `launch()` accepte reellement.
     */
    public function getLaunchBlocker(Player $leader, Dungeon $dungeon): ?string
    {
        $zone = $leader->getCurrentZone();
        if (null === $zone) {
            return 'game.zone.dungeon.error.no_zone';
        }

        try {
            $this->resolveParticipants($leader, $dungeon, $zone);
        } catch (GroupDungeonException $exception) {
            return $exception->getMessage();
        }

        return null;
    }

    /**
     * Groupe effectif d'un lancement : le leader et tous les membres de sa
     * `Party`, dedupliques par identifiant.
     *
     * @return array<int, Player>
     *
     * @throws GroupDungeonException si la formation est refusee
     */
    private function resolveParticipants(Player $leader, Dungeon $dungeon, Zone $zone): array
    {
        // On ne lance que depuis la zone du donjon (regle #7 : position = zone).
        if ($dungeon->getZone()?->getId() !== $zone->getId()) {
            throw new GroupDungeonException('game.zone.dungeon.error.wrong_zone');
        }

        // DON-01 : un seul modele de donjon. Le solo passe par la meme
        // mecanique — un donjon a `maxPlayers: 1` se lance seul, sans party ;
        // `maxPlayers` est la seule borne de taille.
        $membership = $this->partyManager->getPlayerMembership($leader);
        if (null === $membership) {
            if ($dungeon->isGroupDungeon()) {
                throw new GroupDungeonException('game.zone.dungeon.error.no_party');
            }

            $participants = [$leader->getId() => $leader];
        } else {
            $party = $membership->getParty();
            if ($party->getLeader()->getId() !== $leader->getId()) {
                throw new GroupDungeonException('game.zone.dungeon.error.not_leader');
            }

            $participants = [$leader->getId() => $leader];
            foreach ($party->getMembers() as $member) {
                $participants[$member->getPlayer()->getId()] = $member->getPlayer();
            }
        }

        if (\count($participants) > max(1, $dungeon->getMaxPlayers())) {
            throw new GroupDungeonException('game.zone.dungeon.error.too_many');
        }

        foreach ($participants as $participant) {
            if ($participant->getCurrentZone()?->getId() !== $zone->getId()) {
                throw new GroupDungeonException('game.zone.dungeon.error.member_absent');
            }
            if (null !== $this->runRepository->findActiveForPlayer($participant)) {
                throw new GroupDungeonException('game.zone.dungeon.error.already_running');
            }
            // Les prerequis du donjon valent pour chaque membre, pas seulement
            // pour le leader : sinon un groupe porte un joueur dans un contenu
            // dont il n'a ni l'experience ni les objets d'entree.
            if (!$this->dungeonManager->meetsLevelRequirement($participant, $dungeon)) {
                throw new GroupDungeonException('game.zone.dungeon.error.member_experience');
            }
            if ([] !== $this->dungeonManager->getMissingEntryItems($participant, $dungeon)) {
                throw new GroupDungeonException('game.zone.dungeon.error.member_items');
            }
        }

        return $participants;
    }

    /**
     * Abandonne un run actif (leader uniquement).
     *
     * @throws GroupDungeonException si l'abandon est refuse
     */
    public function abandon(Player $player, GroupDungeonRun $run): void
    {
        if ($run->getLeader()->getId() !== $player->getId()) {
            throw new GroupDungeonException('game.zone.dungeon.error.not_leader');
        }
        if (!$run->isActive()) {
            throw new GroupDungeonException('game.zone.dungeon.error.not_active');
        }

        $run->setStatus(GroupDungeonRun::STATUS_ABANDONED);
        $this->entityManager->flush();
    }
}
