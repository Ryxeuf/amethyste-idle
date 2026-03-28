<?php

namespace App\GameEngine\Realtime\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildInfluence;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\Region;
use App\Enum\InfluenceActivityType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class InfluenceMercurePublisher
{
    private const BATCH_INTERVAL_SECONDS = 300; // 5 minutes
    private const CACHE_PREFIX = 'influence_batch_';

    public function __construct(
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
        private readonly CacheItemPoolInterface $cache,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Enregistre des points d'influence et publie une notification batchee (1 / 5 min par guilde).
     */
    public function onInfluenceAwarded(
        Guild $guild,
        Region $region,
        InfluenceSeason $season,
        Player $player,
        InfluenceActivityType $activityType,
        int $pointsEarned,
    ): void {
        $cacheKey = self::CACHE_PREFIX . $guild->getId();

        $item = $this->cache->getItem($cacheKey);

        /** @var array{points: int, last_published: int} $batch */
        $batch = $item->isHit() ? $item->get() : ['points' => 0, 'last_published' => 0];

        $batch['points'] += $pointsEarned;
        $now = time();

        if ($batch['last_published'] === 0 || ($now - $batch['last_published']) >= self::BATCH_INTERVAL_SECONDS) {
            $accumulatedPoints = $batch['points'];

            // Reset batch
            $item->set(['points' => 0, 'last_published' => $now]);
            $this->cache->save($item);

            $this->publishInfluenceUpdate($guild, $region, $season, $player, $activityType, $accumulatedPoints);
            $this->checkOvertake($guild, $region, $season);
        } else {
            // Store accumulated batch
            $item->set($batch);
            $this->cache->save($item);
        }
    }

    /**
     * Publie une annonce globale de changement de controle de ville en fin de saison.
     *
     * @param array<string, string|null> $results regionSlug => guildName|null
     */
    public function publishCityControlChange(InfluenceSeason $season, array $results): void
    {
        $changes = [];
        foreach ($results as $regionSlug => $guildName) {
            $changes[] = [
                'region' => $regionSlug,
                'guild' => $guildName,
            ];
        }

        $update = new Update(
            'guild/city_control',
            json_encode([
                'topic' => 'guild/city_control',
                'type' => 'city_control_change',
                'season' => $season->getName(),
                'seasonNumber' => $season->getSeasonNumber(),
                'changes' => $changes,
            ], JSON_THROW_ON_ERROR),
        );

        $this->hub->publish($update);

        $this->logger->info('Mercure published guild/city_control for season "{season}"', [
            'season' => $season->getName(),
            'changes' => \count($changes),
        ]);
    }

    private function publishInfluenceUpdate(
        Guild $guild,
        Region $region,
        InfluenceSeason $season,
        Player $player,
        InfluenceActivityType $activityType,
        int $pointsEarned,
    ): void {
        $topic = 'guild/influence/' . $guild->getId();

        $update = new Update(
            $topic,
            json_encode([
                'topic' => $topic,
                'type' => 'influence_awarded',
                'guildId' => $guild->getId(),
                'guildName' => $guild->getName(),
                'guildTag' => $guild->getTag(),
                'guildColor' => $guild->getColor(),
                'regionName' => $region->getName(),
                'regionSlug' => $region->getSlug(),
                'seasonName' => $season->getName(),
                'playerName' => $player->getName(),
                'activityType' => $activityType->value,
                'activityLabel' => $activityType->label(),
                'pointsEarned' => $pointsEarned,
            ], JSON_THROW_ON_ERROR),
        );

        $this->hub->publish($update);

        $this->logger->info('Mercure published {topic}: +{points} pts ({activity}) by {player}', [
            'topic' => $topic,
            'points' => $pointsEarned,
            'activity' => $activityType->value,
            'player' => $player->getName(),
        ]);
    }

    /**
     * Verifie si la guilde a depasse une autre guilde au classement de la region et publie une alerte.
     */
    private function checkOvertake(Guild $guild, Region $region, InfluenceSeason $season): void
    {
        /** @var GuildInfluence[] $rankings */
        $rankings = $this->entityManager->getRepository(GuildInfluence::class)->findBy(
            ['region' => $region, 'season' => $season],
            ['points' => 'DESC'],
            5,
        );

        if (\count($rankings) < 2) {
            return;
        }

        // Only alert if our guild is now #1
        $leader = $rankings[0];
        if ($leader->getGuild()->getId() !== $guild->getId()) {
            return;
        }

        $previousLeader = $rankings[1];

        $topic = 'guild/influence/' . $previousLeader->getGuild()->getId();

        $update = new Update(
            $topic,
            json_encode([
                'topic' => $topic,
                'type' => 'influence_overtake',
                'regionName' => $region->getName(),
                'regionSlug' => $region->getSlug(),
                'overtakenByGuild' => $guild->getName(),
                'overtakenByTag' => $guild->getTag(),
                'overtakenByColor' => $guild->getColor(),
                'yourPoints' => $previousLeader->getPoints(),
                'theirPoints' => $leader->getPoints(),
            ], JSON_THROW_ON_ERROR),
        );

        $this->hub->publish($update);

        $this->logger->info('Mercure published influence_overtake: {guild} overtook {prev} in {region}', [
            'guild' => $guild->getName(),
            'prev' => $previousLeader->getGuild()->getName(),
            'region' => $region->getSlug(),
        ]);
    }
}
