<?php

namespace App\Controller\Game;

use App\Entity\App\Guild;
use App\Entity\App\GuildChallengeProgress;
use App\Entity\App\GuildInfluence;
use App\Entity\App\GuildInvitation;
use App\Entity\App\GuildMember;
use App\Entity\App\InfluenceLog;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Region;
use App\Entity\App\RegionControl;
use App\Entity\App\WeeklyChallenge;
use App\Enum\SeasonStatus;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\GuildQuestManager;
use App\GameEngine\Guild\GuildVaultManager;
use App\GameEngine\Guild\RegionUpgradeManager;
use App\GameEngine\Guild\SeasonManager;
use App\GameEngine\Guild\TownControlManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/guild')]
class GuildController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly GuildManager $guildManager,
        private readonly GuildVaultManager $vaultManager,
        private readonly GuildQuestManager $guildQuestManager,
        private readonly SeasonManager $seasonManager,
        private readonly RegionUpgradeManager $regionUpgradeManager,
        private readonly TownControlManager $townControlManager,
    ) {
    }

    #[Route('', name: 'app_game_guild', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $membership = $this->guildManager->getPlayerMembership($player);
        $guild = $membership?->getGuild();
        $invitations = $this->guildManager->getPendingInvitations($player);

        return $this->render('game/guild/index.html.twig', [
            'guild' => $guild,
            'membership' => $membership,
            'invitations' => $invitations,
            'creationCost' => GuildManager::CREATION_COST,
            'player' => $player,
        ]);
    }

    #[Route('/create', name: 'app_game_guild_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $body = json_decode($request->getContent(), true) ?? [];
        $name = trim($body['name'] ?? '');
        $tag = trim($body['tag'] ?? '');
        $description = trim($body['description'] ?? '') ?: null;
        $color = trim($body['color'] ?? '') ?: null;

        if (!$name || !$tag) {
            return new JsonResponse(['error' => 'Nom et tag requis.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $guild = $this->guildManager->createGuild($player, $name, $tag, $description, $color);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Guilde "%s" [%s] créée !', $guild->getName(), $guild->getTag()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/invite/{playerId}', name: 'app_game_guild_invite', methods: ['POST'])]
    public function invite(int $playerId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $target = $this->entityManager->getRepository(Player::class)->find($playerId);
        if (!$target) {
            return new JsonResponse(['error' => 'Joueur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->guildManager->invitePlayer($player, $target);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Invitation envoyée à %s.', $target->getName()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/invitation/{id}/accept', name: 'app_game_guild_accept', methods: ['POST'])]
    public function acceptInvitation(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $invitation = $this->entityManager->getRepository(GuildInvitation::class)->find($id);
        if (!$invitation) {
            return new JsonResponse(['error' => 'Invitation introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->guildManager->acceptInvitation($player, $invitation);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Vous avez rejoint la guilde "%s" !', $invitation->getGuild()->getName()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/invitation/{id}/decline', name: 'app_game_guild_decline', methods: ['POST'])]
    public function declineInvitation(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $invitation = $this->entityManager->getRepository(GuildInvitation::class)->find($id);
        if (!$invitation) {
            return new JsonResponse(['error' => 'Invitation introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->guildManager->declineInvitation($player, $invitation);

            return new JsonResponse([
                'success' => true,
                'message' => 'Invitation déclinée.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/leave', name: 'app_game_guild_leave', methods: ['POST'])]
    public function leave(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        try {
            $this->guildManager->leaveGuild($player);

            return new JsonResponse([
                'success' => true,
                'message' => 'Vous avez quitté la guilde.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/kick/{memberId}', name: 'app_game_guild_kick', methods: ['POST'])]
    public function kick(int $memberId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $target = $this->entityManager->getRepository(GuildMember::class)->find($memberId);
        if (!$target) {
            return new JsonResponse(['error' => 'Membre introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->guildManager->kickMember($player, $target);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%s a été expulsé de la guilde.', $target->getPlayer()->getName()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/promote/{memberId}', name: 'app_game_guild_promote', methods: ['POST'])]
    public function promote(int $memberId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $target = $this->entityManager->getRepository(GuildMember::class)->find($memberId);
        if (!$target) {
            return new JsonResponse(['error' => 'Membre introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->guildManager->promote($player, $target);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%s a été promu %s.', $target->getPlayer()->getName(), $target->getRank()->label()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/demote/{memberId}', name: 'app_game_guild_demote', methods: ['POST'])]
    public function demote(int $memberId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $target = $this->entityManager->getRepository(GuildMember::class)->find($memberId);
        if (!$target) {
            return new JsonResponse(['error' => 'Membre introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->guildManager->demote($player, $target);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%s a été rétrogradé %s.', $target->getPlayer()->getName(), $target->getRank()->label()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/vault', name: 'app_game_guild_vault', methods: ['GET'])]
    public function vault(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $membership = $this->guildManager->getPlayerMembership($player);
        if (!$membership) {
            return $this->redirectToRoute('app_game_guild');
        }

        $guild = $membership->getGuild();
        $vault = $this->vaultManager->getOrCreateVault($guild);
        $logs = $this->vaultManager->getRecentLogs($guild);

        $bag = null;
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->isBag()) {
                $bag = $inventory;
                break;
            }
        }

        return $this->render('game/guild/vault.html.twig', [
            'guild' => $guild,
            'membership' => $membership,
            'vault' => $vault,
            'logs' => $logs,
            'bag' => $bag,
            'player' => $player,
        ]);
    }

    #[Route('/vault/deposit/{itemId}', name: 'app_game_guild_vault_deposit', methods: ['POST'])]
    public function vaultDeposit(int $itemId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($itemId);
        if (!$playerItem) {
            return new JsonResponse(['error' => 'Objet introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->vaultManager->deposit($player, $playerItem);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%s déposé dans le coffre.', $playerItem->getGenericItem()->getName()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/vault/withdraw/{itemId}', name: 'app_game_guild_vault_withdraw', methods: ['POST'])]
    public function vaultWithdraw(int $itemId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($itemId);
        if (!$playerItem) {
            return new JsonResponse(['error' => 'Objet introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->vaultManager->withdraw($player, $playerItem);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%s retiré du coffre.', $playerItem->getGenericItem()->getName()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/quests', name: 'app_game_guild_quests', methods: ['GET'])]
    public function quests(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $membership = $this->guildManager->getPlayerMembership($player);
        if (!$membership) {
            return $this->redirectToRoute('app_game_guild');
        }

        $guild = $membership->getGuild();
        $activeQuests = $this->guildQuestManager->generateWeeklyQuests($guild);
        $completedQuests = $this->guildQuestManager->getCompletedQuests($guild);

        return $this->render('game/guild/quests.html.twig', [
            'guild' => $guild,
            'membership' => $membership,
            'activeQuests' => $activeQuests,
            'completedQuests' => $completedQuests,
        ]);
    }

    #[Route('/ranking', name: 'app_game_guild_ranking', methods: ['GET'])]
    public function ranking(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $repo = $this->entityManager->getRepository(Guild::class);

        $guilds = $repo->createQueryBuilder('g')
            ->orderBy('g.points', 'DESC')
            ->addOrderBy('g.name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $total = $repo->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($total / $limit));

        $player = $this->playerHelper->getPlayer();
        $playerGuild = $this->guildManager->getPlayerGuild($player);

        return $this->render('game/guild/ranking.html.twig', [
            'guilds' => $guilds,
            'page' => $page,
            'totalPages' => $totalPages,
            'offset' => $offset,
            'playerGuildId' => $playerGuild?->getId(),
        ]);
    }

    #[Route('/influence', name: 'app_game_guild_influence', methods: ['GET'])]
    public function influence(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();
        $playerGuild = $this->guildManager->getPlayerGuild($player);

        $regions = $this->entityManager->getRepository(Region::class)->findBy(
            ['isContestable' => true],
            ['name' => 'ASC'],
        );

        $selectedRegionSlug = $request->query->getString('region', '');
        $selectedRegion = null;
        foreach ($regions as $region) {
            if ($region->getSlug() === $selectedRegionSlug) {
                $selectedRegion = $region;
                break;
            }
        }
        if ($selectedRegion === null && \count($regions) > 0) {
            $selectedRegion = $regions[0];
        }

        $seasonSlug = $request->query->getString('season', '');
        $season = null;
        if ($seasonSlug !== '') {
            $season = $this->entityManager->getRepository(InfluenceSeason::class)->findOneBy(['slug' => $seasonSlug]);
        }
        $season ??= $this->seasonManager->getCurrentSeason();

        $completedSeasons = $this->entityManager->getRepository(InfluenceSeason::class)->findBy(
            ['status' => SeasonStatus::Completed],
            ['seasonNumber' => 'DESC'],
        );

        $rankings = [];
        $playerGuildInfluence = null;
        $playerGuildRank = null;
        $topContributors = [];
        $regionControl = null;

        if ($selectedRegion !== null && $season !== null) {
            $rankings = $this->entityManager->getRepository(GuildInfluence::class)
                ->createQueryBuilder('gi')
                ->join('gi.guild', 'g')
                ->where('gi.region = :region')
                ->andWhere('gi.season = :season')
                ->andWhere('gi.points > 0')
                ->setParameter('region', $selectedRegion)
                ->setParameter('season', $season)
                ->orderBy('gi.points', 'DESC')
                ->addOrderBy('g.name', 'ASC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();

            if ($playerGuild !== null) {
                $playerGuildInfluence = $this->entityManager->getRepository(GuildInfluence::class)->findOneBy([
                    'guild' => $playerGuild,
                    'region' => $selectedRegion,
                    'season' => $season,
                ]);

                if ($playerGuildInfluence !== null && $playerGuildInfluence->getPoints() > 0) {
                    $playerGuildRank = (int) $this->entityManager->getRepository(GuildInfluence::class)
                        ->createQueryBuilder('gi')
                        ->select('COUNT(gi.id)')
                        ->where('gi.region = :region')
                        ->andWhere('gi.season = :season')
                        ->andWhere('gi.points > :points')
                        ->setParameter('region', $selectedRegion)
                        ->setParameter('season', $season)
                        ->setParameter('points', $playerGuildInfluence->getPoints())
                        ->getQuery()
                        ->getSingleScalarResult() + 1;
                }

                $topContributors = $this->entityManager->getRepository(InfluenceLog::class)
                    ->createQueryBuilder('il')
                    ->select('IDENTITY(il.player) AS playerId, p.name AS playerName, SUM(il.pointsEarned) AS totalPoints')
                    ->join('il.player', 'p')
                    ->where('il.guild = :guild')
                    ->andWhere('il.region = :region')
                    ->andWhere('il.season = :season')
                    ->setParameter('guild', $playerGuild)
                    ->setParameter('region', $selectedRegion)
                    ->setParameter('season', $season)
                    ->groupBy('il.player, p.name')
                    ->orderBy('totalPoints', 'DESC')
                    ->setMaxResults(5)
                    ->getQuery()
                    ->getResult();
            }

            if ($season->isCompleted()) {
                $regionControl = $this->entityManager->getRepository(RegionControl::class)->findOneBy([
                    'region' => $selectedRegion,
                    'season' => $season,
                ]);
            } else {
                $regionControl = $this->entityManager->getRepository(RegionControl::class)
                    ->createQueryBuilder('rc')
                    ->where('rc.region = :region')
                    ->andWhere('rc.endsAt IS NULL')
                    ->setParameter('region', $selectedRegion)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
            }
        }

        $maxPoints = 0;
        foreach ($rankings as $gi) {
            if ($gi->getPoints() > $maxPoints) {
                $maxPoints = $gi->getPoints();
            }
        }

        return $this->render('game/guild/influence.html.twig', [
            'regions' => $regions,
            'selectedRegion' => $selectedRegion,
            'season' => $season,
            'completedSeasons' => $completedSeasons,
            'rankings' => $rankings,
            'maxPoints' => $maxPoints,
            'playerGuild' => $playerGuild,
            'playerGuildInfluence' => $playerGuildInfluence,
            'playerGuildRank' => $playerGuildRank,
            'topContributors' => $topContributors,
            'regionControl' => $regionControl,
        ]);
    }

    #[Route('/influence/api/{regionSlug}', name: 'app_game_guild_influence_api', methods: ['GET'])]
    public function influenceApi(string $regionSlug, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $region = $this->entityManager->getRepository(Region::class)->findOneBy(['slug' => $regionSlug]);
        if ($region === null) {
            return new JsonResponse(['error' => 'Region introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $seasonSlug = $request->query->getString('season', '');
        $season = null;
        if ($seasonSlug !== '') {
            $season = $this->entityManager->getRepository(InfluenceSeason::class)->findOneBy(['slug' => $seasonSlug]);
        }
        $season ??= $this->seasonManager->getCurrentSeason();

        if ($season === null) {
            return new JsonResponse(['error' => 'Aucune saison trouvee.'], Response::HTTP_NOT_FOUND);
        }

        /** @var GuildInfluence[] $rankings */
        $rankings = $this->entityManager->getRepository(GuildInfluence::class)
            ->createQueryBuilder('gi')
            ->join('gi.guild', 'g')
            ->where('gi.region = :region')
            ->andWhere('gi.season = :season')
            ->andWhere('gi.points > 0')
            ->setParameter('region', $region)
            ->setParameter('season', $season)
            ->orderBy('gi.points', 'DESC')
            ->addOrderBy('g.name', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $data = [];
        $rank = 1;
        foreach ($rankings as $gi) {
            $data[] = [
                'rank' => $rank++,
                'guild' => [
                    'name' => $gi->getGuild()->getName(),
                    'tag' => $gi->getGuild()->getTag(),
                ],
                'points' => $gi->getPoints(),
            ];
        }

        return new JsonResponse([
            'region' => [
                'name' => $region->getName(),
                'slug' => $region->getSlug(),
            ],
            'season' => [
                'name' => $season->getName(),
                'slug' => $season->getSlug(),
                'status' => $season->getStatus()->value,
                'startsAt' => $season->getStartsAt()->format('Y-m-d'),
                'endsAt' => $season->getEndsAt()->format('Y-m-d'),
            ],
            'rankings' => $data,
        ]);
    }

    #[Route('/challenges', name: 'app_game_guild_challenges', methods: ['GET'])]
    public function challenges(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $membership = $this->guildManager->getPlayerMembership($player);
        if (!$membership) {
            return $this->redirectToRoute('app_game_guild');
        }

        $guild = $membership->getGuild();
        $season = $this->seasonManager->getCurrentSeason();

        $activeChallenges = [];
        $completedChallenges = [];

        if ($season !== null) {
            $now = new \DateTime();

            $challenges = $this->entityManager->getRepository(WeeklyChallenge::class)
                ->createQueryBuilder('wc')
                ->where('wc.season = :season')
                ->setParameter('season', $season)
                ->orderBy('wc.weekNumber', 'DESC')
                ->addOrderBy('wc.startsAt', 'ASC')
                ->getQuery()
                ->getResult();

            $progressMap = [];
            $progressRecords = $this->entityManager->getRepository(GuildChallengeProgress::class)
                ->createQueryBuilder('gcp')
                ->where('gcp.guild = :guild')
                ->andWhere('gcp.challenge IN (:challenges)')
                ->setParameter('guild', $guild)
                ->setParameter('challenges', $challenges)
                ->getQuery()
                ->getResult();

            foreach ($progressRecords as $p) {
                $progressMap[$p->getChallenge()->getId()] = $p;
            }

            foreach ($challenges as $challenge) {
                $progress = $progressMap[$challenge->getId()] ?? null;
                $entry = [
                    'challenge' => $challenge,
                    'progress' => $progress,
                    'current' => $progress ? $progress->getProgress() : 0,
                    'target' => $challenge->getTarget(),
                    'percentage' => $progress ? $progress->getPercentage() : 0,
                    'completed' => $progress && $progress->isCompleted(),
                ];

                if ($challenge->getEndsAt() >= $now && !($progress && $progress->isCompleted())) {
                    $activeChallenges[] = $entry;
                } else {
                    $completedChallenges[] = $entry;
                }
            }
        }

        return $this->render('game/guild/challenges.html.twig', [
            'guild' => $guild,
            'membership' => $membership,
            'season' => $season,
            'activeChallenges' => $activeChallenges,
            'completedChallenges' => $completedChallenges,
        ]);
    }

    #[Route('/upgrades', name: 'app_game_guild_upgrades', methods: ['GET'])]
    public function upgrades(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $membership = $this->guildManager->getPlayerMembership($player);
        if (!$membership) {
            return $this->redirectToRoute('app_game_guild');
        }

        $guild = $membership->getGuild();

        $regions = $this->entityManager->getRepository(Region::class)->findBy(
            ['isContestable' => true],
            ['name' => 'ASC'],
        );

        $regionData = [];
        foreach ($regions as $region) {
            $controllingGuild = $this->townControlManager->getControllingGuild($region);
            $isOurs = $controllingGuild !== null && $controllingGuild->getId() === $guild->getId();

            $regionData[] = [
                'region' => $region,
                'controllingGuild' => $controllingGuild,
                'isOurs' => $isOurs,
                'upgrades' => $isOurs ? $this->regionUpgradeManager->getUpgradeSummary($region) : [],
            ];
        }

        $canManage = \in_array($membership->getRank()->value, ['leader', 'officer'], true);

        return $this->render('game/guild/upgrades.html.twig', [
            'guild' => $guild,
            'membership' => $membership,
            'regionData' => $regionData,
            'canManage' => $canManage,
        ]);
    }

    #[Route('/upgrade/{regionSlug}/{upgradeSlug}', name: 'app_game_guild_upgrade_purchase', methods: ['POST'])]
    public function upgradePurchase(string $regionSlug, string $upgradeSlug): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        $membership = $this->guildManager->getPlayerMembership($player);
        if (!$membership) {
            return new JsonResponse(['error' => 'Vous devez etre dans une guilde.'], Response::HTTP_BAD_REQUEST);
        }

        if (!\in_array($membership->getRank()->value, ['leader', 'officer'], true)) {
            return new JsonResponse(['error' => 'Seuls le chef et les officiers peuvent acheter des ameliorations.'], Response::HTTP_FORBIDDEN);
        }

        $region = $this->entityManager->getRepository(Region::class)->findOneBy(['slug' => $regionSlug]);
        if ($region === null) {
            return new JsonResponse(['error' => 'Region introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $upgrade = $this->regionUpgradeManager->purchase($membership->getGuild(), $region, $upgradeSlug);

            $def = RegionUpgradeManager::UPGRADES[$upgradeSlug] ?? null;
            $label = $def['label'] ?? $upgradeSlug;

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%s ameliore au niveau %d !', $label, $upgrade->getLevel()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
