<?php

namespace App\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildInfluence;
use App\Entity\App\GuildMember;
use App\Entity\App\InfluenceLog;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\Region;
use App\Enum\InfluenceActivityType;
use Doctrine\ORM\EntityManagerInterface;

class InfluenceManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SeasonManager $seasonManager,
    ) {
    }

    /**
     * Calcule les points d'influence selon le type d'activite et le contexte.
     *
     * @param array<string, mixed> $context
     */
    public function calculatePoints(InfluenceActivityType $activityType, array $context = []): int
    {
        $base = match ($activityType) {
            InfluenceActivityType::MobKill => 5 + (($context['mob_level'] ?? 1) * 2),
            InfluenceActivityType::Craft => 10 + (($context['recipe_level'] ?? 1) * 5),
            InfluenceActivityType::Harvest => 3 * ($context['item_count'] ?? 1),
            InfluenceActivityType::Fishing => 5,
            InfluenceActivityType::Butchering => 4 * ($context['item_count'] ?? 1),
            InfluenceActivityType::Quest => 20 + (($context['quest_tier'] ?? 1) * 10),
            InfluenceActivityType::Challenge => 50,
        };

        return max(1, $base);
    }

    /**
     * Ajoute des points d'influence a une guilde pour une region/saison.
     *
     * @param array<string, mixed>|null $details
     */
    public function addPoints(
        Guild $guild,
        Region $region,
        InfluenceSeason $season,
        int $points,
        Player $player,
        InfluenceActivityType $activityType,
        ?array $details = null,
    ): void {
        $multiplier = $season->getMultiplier($activityType->value);
        $finalPoints = (int) round($points * $multiplier);

        if ($finalPoints <= 0) {
            return;
        }

        $influence = $this->entityManager->getRepository(GuildInfluence::class)->findOneBy([
            'guild' => $guild,
            'region' => $region,
            'season' => $season,
        ]);

        if ($influence === null) {
            $influence = new GuildInfluence();
            $influence->setGuild($guild);
            $influence->setRegion($region);
            $influence->setSeason($season);
            $this->entityManager->persist($influence);
        }

        $influence->addPoints($finalPoints);

        $log = new InfluenceLog();
        $log->setGuild($guild);
        $log->setRegion($region);
        $log->setSeason($season);
        $log->setPlayer($player);
        $log->setActivityType($activityType);
        $log->setPointsEarned($finalPoints);
        $log->setDetails($details);
        $this->entityManager->persist($log);
    }

    /**
     * Resout le joueur -> guilde. Retourne null si le joueur n'est pas dans une guilde.
     */
    public function getPlayerGuild(Player $player): ?Guild
    {
        $membership = $this->entityManager->getRepository(GuildMember::class)->findOneBy([
            'player' => $player,
        ]);

        return $membership?->getGuild();
    }

    /**
     * Resout la region depuis la map du joueur.
     */
    public function getPlayerRegion(Player $player): ?Region
    {
        return $player->getMap()?->getRegion();
    }

    /**
     * Point d'entree principal : attribue des points d'influence si toutes les conditions sont remplies.
     *
     * @param array<string, mixed> $context
     * @param array<string, mixed>|null $details
     *
     * @return bool true si des points ont ete attribues
     */
    public function awardInfluence(
        Player $player,
        InfluenceActivityType $activityType,
        array $context = [],
        ?Region $region = null,
        ?array $details = null,
    ): bool {
        $guild = $this->getPlayerGuild($player);
        if ($guild === null) {
            return false;
        }

        $season = $this->seasonManager->getCurrentSeason();
        if ($season === null) {
            return false;
        }

        $region ??= $this->getPlayerRegion($player);
        if ($region === null) {
            return false;
        }

        $points = $this->calculatePoints($activityType, $context);

        $this->addPoints($guild, $region, $season, $points, $player, $activityType, $details);

        return true;
    }
}
