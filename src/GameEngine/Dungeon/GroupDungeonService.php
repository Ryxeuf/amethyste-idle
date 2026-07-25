<?php

namespace App\GameEngine\Dungeon;

use App\Entity\App\GroupDungeonMember;
use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Player;
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
    ) {
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

        $membership = $this->partyManager->getPlayerMembership($leader);
        if (null === $membership) {
            throw new GroupDungeonException('game.zone.dungeon.error.no_party');
        }
        $party = $membership->getParty();
        if ($party->getLeader()->getId() !== $leader->getId()) {
            throw new GroupDungeonException('game.zone.dungeon.error.not_leader');
        }

        // Participants = leader + membres de la party (dedupliques).
        $participants = [$leader->getId() => $leader];
        foreach ($party->getMembers() as $member) {
            $participants[$member->getPlayer()->getId()] = $member->getPlayer();
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
        }

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
