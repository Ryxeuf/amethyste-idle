<?php

namespace App\Tests\Unit\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildInfluence;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Region;
use App\Entity\App\RegionControl;
use App\Enum\SeasonStatus;
use App\GameEngine\Guild\TownControlManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TownControlManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private TownControlManager $manager;
    private EntityRepository&MockObject $regionRepo;
    private EntityRepository&MockObject $influenceRepo;
    private EntityRepository&MockObject $controlRepo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->regionRepo = $this->createMock(EntityRepository::class);
        $this->influenceRepo = $this->createMock(EntityRepository::class);
        $this->controlRepo = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')
            ->willReturnCallback(fn (string $class) => match ($class) {
                Region::class => $this->regionRepo,
                GuildInfluence::class => $this->influenceRepo,
                RegionControl::class => $this->controlRepo,
                default => $this->createMock(EntityRepository::class),
            });

        $this->manager = new TownControlManager($this->em);
    }

    public function testAttributeControlSingleWinner(): void
    {
        $region = $this->createRegion('plaines', true);
        $season = $this->createSeason();
        $guildA = $this->createGuild('Alpha');
        $guildB = $this->createGuild('Beta');

        $influenceA = $this->createInfluence($guildA, $region, $season, 200);
        $influenceB = $this->createInfluence($guildB, $region, $season, 100);

        $this->regionRepo->method('findBy')->willReturn([$region]);
        $this->controlRepo->method('findOneBy')->willReturn(null);
        $this->influenceRepo->method('findBy')->willReturn([$influenceA, $influenceB]);

        $persisted = [];
        $this->em->expects($this->once())->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $this->em->expects($this->once())->method('flush');

        $results = $this->manager->attributeControl($season);

        $this->assertCount(1, $persisted);
        $this->assertInstanceOf(RegionControl::class, $persisted[0]);
        $this->assertSame($guildA, $persisted[0]->getGuild());
        $this->assertSame($region, $persisted[0]->getRegion());
        $this->assertSame($season, $persisted[0]->getSeason());
        $this->assertTrue($persisted[0]->isActive());
        $this->assertSame(['plaines' => 'Alpha'], $results);
    }

    public function testAttributeControlNoInfluence(): void
    {
        $region = $this->createRegion('plaines', true);
        $season = $this->createSeason();

        $this->regionRepo->method('findBy')->willReturn([$region]);
        $this->controlRepo->method('findOneBy')->willReturn(null);
        $this->influenceRepo->method('findBy')->willReturn([]);

        $persisted = [];
        $this->em->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $results = $this->manager->attributeControl($season);

        $this->assertCount(1, $persisted);
        $this->assertNull($persisted[0]->getGuild());
        $this->assertSame(['plaines' => null], $results);
    }

    public function testAttributeControlTieExistingHolderKeeps(): void
    {
        $region = $this->createRegion('plaines', true);
        $season = $this->createSeason();
        $guildA = $this->createGuild('Alpha');
        $guildB = $this->createGuild('Beta');

        $influenceA = $this->createInfluence($guildA, $region, $season, 150);
        $influenceB = $this->createInfluence($guildB, $region, $season, 150);

        // GuildB currently controls the region
        $existingControl = new RegionControl();
        $existingControl->setRegion($region);
        $existingControl->setGuild($guildB);
        $existingControl->setSeason($this->createSeason()); // previous season
        $existingControl->setStartedAt(new \DateTime('-28 days'));

        $this->regionRepo->method('findBy')->willReturn([$region]);
        $this->controlRepo->method('findOneBy')->willReturn($existingControl);
        $this->influenceRepo->method('findBy')->willReturn([$influenceA, $influenceB]);

        $persisted = [];
        $this->em->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $results = $this->manager->attributeControl($season);

        // Existing control should be closed
        $this->assertNotNull($existingControl->getEndsAt());

        // New control should go to guildB (current holder in tie)
        $newControl = end($persisted);
        $this->assertSame($guildB, $newControl->getGuild());
        $this->assertSame(['plaines' => 'Beta'], $results);
    }

    public function testAttributeControlSkipsNonContestable(): void
    {
        $safeRegion = $this->createRegion('sanctuaire', false);
        $contestRegion = $this->createRegion('plaines', true);
        $season = $this->createSeason();
        $guild = $this->createGuild('Alpha');

        $influence = $this->createInfluence($guild, $contestRegion, $season, 100);

        // Only contestable regions are returned by findBy filter
        $this->regionRepo->method('findBy')->willReturn([$contestRegion]);
        $this->controlRepo->method('findOneBy')->willReturn(null);
        $this->influenceRepo->method('findBy')->willReturn([$influence]);

        $persisted = [];
        $this->em->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $results = $this->manager->attributeControl($season);

        $this->assertCount(1, $persisted);
        $this->assertArrayNotHasKey('sanctuaire', $results);
        $this->assertSame(['plaines' => 'Alpha'], $results);
    }

    public function testAttributeControlZeroPointsNoWinner(): void
    {
        $region = $this->createRegion('plaines', true);
        $season = $this->createSeason();
        $guild = $this->createGuild('Alpha');

        $influence = $this->createInfluence($guild, $region, $season, 0);

        $this->regionRepo->method('findBy')->willReturn([$region]);
        $this->controlRepo->method('findOneBy')->willReturn(null);
        $this->influenceRepo->method('findBy')->willReturn([$influence]);

        $persisted = [];
        $this->em->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $results = $this->manager->attributeControl($season);

        $this->assertNull($persisted[0]->getGuild());
        $this->assertSame(['plaines' => null], $results);
    }

    public function testGetControllingGuildActive(): void
    {
        $region = $this->createRegion('plaines', true);
        $guild = $this->createGuild('Alpha');

        $control = new RegionControl();
        $control->setRegion($region);
        $control->setGuild($guild);
        $control->setSeason($this->createSeason());
        $control->setStartedAt(new \DateTime());

        $this->controlRepo->method('findOneBy')
            ->with(['region' => $region, 'endsAt' => null])
            ->willReturn($control);

        $result = $this->manager->getControllingGuild($region);
        $this->assertSame($guild, $result);
    }

    public function testGetControllingGuildNone(): void
    {
        $region = $this->createRegion('plaines', true);

        $this->controlRepo->method('findOneBy')->willReturn(null);

        $result = $this->manager->getControllingGuild($region);
        $this->assertNull($result);
    }

    public function testAttributeControlClosesExistingControl(): void
    {
        $region = $this->createRegion('plaines', true);
        $season = $this->createSeason();

        $existingControl = new RegionControl();
        $existingControl->setRegion($region);
        $existingControl->setGuild($this->createGuild('Old'));
        $existingControl->setSeason($this->createSeason());
        $existingControl->setStartedAt(new \DateTime('-28 days'));
        $existingControl->setCreatedAt(new \DateTime('-28 days'));
        $existingControl->setUpdatedAt(new \DateTime('-28 days'));

        $this->assertNull($existingControl->getEndsAt());

        $this->regionRepo->method('findBy')->willReturn([$region]);
        $this->controlRepo->method('findOneBy')->willReturn($existingControl);
        $this->influenceRepo->method('findBy')->willReturn([]);

        $this->em->method('persist');
        $this->em->method('flush');

        $this->manager->attributeControl($season);

        $this->assertNotNull($existingControl->getEndsAt());
        $this->assertFalse($existingControl->isActive());
    }

    private function createRegion(string $slug, bool $contestable): Region
    {
        $region = new Region();
        $region->setName(ucfirst($slug));
        $region->setSlug($slug);
        $region->setIsContestable($contestable);

        return $region;
    }

    private function createGuild(string $name): Guild
    {
        $guild = new Guild();
        $guild->setName($name);
        $guild->setTag(strtoupper(substr($name, 0, 3)));

        return $guild;
    }

    private function createSeason(): InfluenceSeason
    {
        $season = new InfluenceSeason();
        $season->setName('Saison 1');
        $season->setSlug('saison-1');
        $season->setSeasonNumber(1);
        $season->setStartsAt(new \DateTime('-28 days'));
        $season->setEndsAt(new \DateTime());
        $season->setStatus(SeasonStatus::Active);

        return $season;
    }

    private function createInfluence(Guild $guild, Region $region, InfluenceSeason $season, int $points): GuildInfluence
    {
        $influence = new GuildInfluence();
        $influence->setGuild($guild);
        $influence->setRegion($region);
        $influence->setSeason($season);
        $influence->setPoints($points);

        return $influence;
    }
}
