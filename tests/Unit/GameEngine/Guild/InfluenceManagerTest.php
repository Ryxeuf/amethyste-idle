<?php

namespace App\Tests\Unit\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildInfluence;
use App\Entity\App\GuildMember;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\Region;
use App\Enum\InfluenceActivityType;
use App\GameEngine\Guild\InfluenceManager;
use App\GameEngine\Guild\SeasonManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InfluenceManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private SeasonManager&MockObject $seasonManager;
    private InfluenceManager $manager;
    private EntityRepository&MockObject $influenceRepo;
    private EntityRepository&MockObject $memberRepo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->seasonManager = $this->createMock(SeasonManager::class);
        $this->influenceRepo = $this->createMock(EntityRepository::class);
        $this->memberRepo = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')
            ->willReturnCallback(fn (string $class) => match ($class) {
                GuildInfluence::class => $this->influenceRepo,
                GuildMember::class => $this->memberRepo,
                default => $this->createMock(EntityRepository::class),
            });

        $this->manager = new InfluenceManager($this->em, $this->seasonManager);
    }

    public function testCalculatePointsMobKill(): void
    {
        $points = $this->manager->calculatePoints(InfluenceActivityType::MobKill, ['mob_level' => 10]);
        $this->assertSame(25, $points); // 5 + (10 * 2)
    }

    public function testCalculatePointsMobKillDefaultLevel(): void
    {
        $points = $this->manager->calculatePoints(InfluenceActivityType::MobKill);
        $this->assertSame(7, $points); // 5 + (1 * 2)
    }

    public function testCalculatePointsCraft(): void
    {
        $points = $this->manager->calculatePoints(InfluenceActivityType::Craft, ['recipe_level' => 3]);
        $this->assertSame(25, $points); // 10 + (3 * 5)
    }

    public function testCalculatePointsHarvest(): void
    {
        $points = $this->manager->calculatePoints(InfluenceActivityType::Harvest, ['item_count' => 4]);
        $this->assertSame(12, $points); // 3 * 4
    }

    public function testCalculatePointsFishing(): void
    {
        $points = $this->manager->calculatePoints(InfluenceActivityType::Fishing);
        $this->assertSame(5, $points);
    }

    public function testCalculatePointsButchering(): void
    {
        $points = $this->manager->calculatePoints(InfluenceActivityType::Butchering, ['item_count' => 3]);
        $this->assertSame(12, $points); // 4 * 3
    }

    public function testCalculatePointsQuest(): void
    {
        $points = $this->manager->calculatePoints(InfluenceActivityType::Quest, ['quest_tier' => 2]);
        $this->assertSame(40, $points); // 20 + (2 * 10)
    }

    public function testAddPointsCreatesNewInfluence(): void
    {
        $guild = $this->createGuild();
        $region = $this->createRegion();
        $season = $this->createActiveSeason();
        $player = $this->createPlayer();

        $this->influenceRepo->method('findOneBy')->willReturn(null);

        $this->em->expects($this->exactly(2))->method('persist');

        $this->manager->addPoints(
            $guild,
            $region,
            $season,
            15,
            $player,
            InfluenceActivityType::MobKill,
            ['monster' => 'goblin'],
        );
    }

    public function testAddPointsUpdatesExistingInfluence(): void
    {
        $guild = $this->createGuild();
        $region = $this->createRegion();
        $season = $this->createActiveSeason();
        $player = $this->createPlayer();

        $existing = new GuildInfluence();
        $existing->setGuild($guild);
        $existing->setRegion($region);
        $existing->setSeason($season);
        $existing->setPoints(100);

        $this->influenceRepo->method('findOneBy')->willReturn($existing);

        // persist only the log, not a new GuildInfluence
        $this->em->expects($this->once())->method('persist');

        $this->manager->addPoints(
            $guild,
            $region,
            $season,
            20,
            $player,
            InfluenceActivityType::Craft,
        );

        $this->assertSame(120, $existing->getPoints());
    }

    public function testAddPointsAppliesSeasonMultiplier(): void
    {
        $guild = $this->createGuild();
        $region = $this->createRegion();
        $player = $this->createPlayer();

        $season = $this->createActiveSeason(['multipliers' => ['craft' => 2.0]]);

        $existing = new GuildInfluence();
        $existing->setGuild($guild);
        $existing->setRegion($region);
        $existing->setSeason($season);
        $existing->setPoints(0);

        $this->influenceRepo->method('findOneBy')->willReturn($existing);
        $this->em->expects($this->once())->method('persist');

        $this->manager->addPoints(
            $guild,
            $region,
            $season,
            10,
            $player,
            InfluenceActivityType::Craft,
        );

        $this->assertSame(20, $existing->getPoints()); // 10 * 2.0
    }

    public function testAwardInfluenceNoGuild(): void
    {
        $player = $this->createPlayer();

        $this->memberRepo->method('findOneBy')->willReturn(null);

        $result = $this->manager->awardInfluence($player, InfluenceActivityType::MobKill);
        $this->assertFalse($result);
    }

    public function testAwardInfluenceNoActiveSeason(): void
    {
        $player = $this->createPlayer();
        $guild = $this->createGuild();

        $member = new GuildMember();
        $member->setGuild($guild);
        $member->setPlayer($player);

        $this->memberRepo->method('findOneBy')->willReturn($member);
        $this->seasonManager->method('getCurrentSeason')->willReturn(null);

        $result = $this->manager->awardInfluence($player, InfluenceActivityType::MobKill);
        $this->assertFalse($result);
    }

    public function testAwardInfluenceNoRegion(): void
    {
        $player = $this->createPlayer(withMap: false);
        $guild = $this->createGuild();
        $season = $this->createActiveSeason();

        $member = new GuildMember();
        $member->setGuild($guild);
        $member->setPlayer($player);

        $this->memberRepo->method('findOneBy')->willReturn($member);
        $this->seasonManager->method('getCurrentSeason')->willReturn($season);

        $result = $this->manager->awardInfluence($player, InfluenceActivityType::MobKill);
        $this->assertFalse($result);
    }

    public function testAwardInfluenceSuccess(): void
    {
        $player = $this->createPlayer(withMap: true);
        $guild = $this->createGuild();
        $season = $this->createActiveSeason();

        $member = new GuildMember();
        $member->setGuild($guild);
        $member->setPlayer($player);

        $this->memberRepo->method('findOneBy')->willReturn($member);
        $this->seasonManager->method('getCurrentSeason')->willReturn($season);
        $this->influenceRepo->method('findOneBy')->willReturn(null);

        $this->em->expects($this->exactly(2))->method('persist');

        $result = $this->manager->awardInfluence(
            $player,
            InfluenceActivityType::MobKill,
            ['mob_level' => 5],
        );
        $this->assertTrue($result);
    }

    public function testAwardInfluenceWithExplicitRegion(): void
    {
        $player = $this->createPlayer(withMap: false);
        $guild = $this->createGuild();
        $season = $this->createActiveSeason();
        $region = $this->createRegion();

        $member = new GuildMember();
        $member->setGuild($guild);
        $member->setPlayer($player);

        $this->memberRepo->method('findOneBy')->willReturn($member);
        $this->seasonManager->method('getCurrentSeason')->willReturn($season);
        $this->influenceRepo->method('findOneBy')->willReturn(null);

        $this->em->expects($this->exactly(2))->method('persist');

        $result = $this->manager->awardInfluence(
            $player,
            InfluenceActivityType::Fishing,
            [],
            $region,
        );
        $this->assertTrue($result);
    }

    private function createPlayer(bool $withMap = false): Player
    {
        $player = $this->createMock(Player::class);

        if ($withMap) {
            $map = $this->createMock(\App\Entity\App\Map::class);
            $region = $this->createRegion();
            $map->method('getRegion')->willReturn($region);
            $player->method('getMap')->willReturn($map);
        } else {
            $player->method('getMap')->willReturn(null);
        }

        return $player;
    }

    private function createGuild(): Guild
    {
        $guild = new Guild();
        $guild->setName('Test Guild');
        $guild->setTag('TST');

        return $guild;
    }

    private function createRegion(): Region
    {
        $region = new Region();
        $region->setName('Plaines');
        $region->setSlug('plaines');

        return $region;
    }

    /**
     * @param array<string, mixed>|null $parameters
     */
    private function createActiveSeason(?array $parameters = null): InfluenceSeason
    {
        $season = new InfluenceSeason();
        $season->setName('Saison 1');
        $season->setSlug('saison-1');
        $season->setSeasonNumber(1);
        $season->setStartsAt(new \DateTime('-7 days'));
        $season->setEndsAt(new \DateTime('+21 days'));
        $season->setStatus(\App\Enum\SeasonStatus::Active);
        $season->setParameters($parameters);

        return $season;
    }
}
