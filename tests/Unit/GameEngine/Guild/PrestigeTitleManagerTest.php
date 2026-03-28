<?php

namespace App\Tests\Unit\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildMember;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\Region;
use App\Entity\App\RegionControl;
use App\Enum\GuildRank;
use App\Enum\SeasonStatus;
use App\GameEngine\Guild\PrestigeTitleManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PrestigeTitleManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private PrestigeTitleManager $manager;
    private EntityRepository&MockObject $controlRepo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->controlRepo = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')
            ->willReturnCallback(fn (string $class) => match ($class) {
                RegionControl::class => $this->controlRepo,
                default => $this->createMock(EntityRepository::class),
            });

        $this->mockQueryBuilder();

        $this->manager = new PrestigeTitleManager($this->em);
    }

    public function testBuildTitle(): void
    {
        $region = $this->createRegion('Plaines du Vent');

        $this->assertSame('Protecteur de Plaines du Vent', $this->manager->buildTitle($region));
    }

    public function testUpdateTitlesAssignsTitlesToControllingGuildMembers(): void
    {
        $season = $this->createSeason();
        $region = $this->createRegion('Plaines du Vent');
        $guild = $this->createGuild('Alpha');

        $player1 = $this->createPlayer('Joueur1');
        $player2 = $this->createPlayer('Joueur2');

        $this->addMember($guild, $player1);
        $this->addMember($guild, $player2);

        $control = new RegionControl();
        $control->setRegion($region);
        $control->setGuild($guild);
        $control->setSeason($season);
        $control->setStartedAt(new \DateTime());
        $control->setCreatedAt(new \DateTime());
        $control->setUpdatedAt(new \DateTime());

        $this->controlRepo->method('findBy')
            ->with(['endsAt' => null])
            ->willReturn([$control]);

        $this->manager->updateTitles($season);

        $this->assertSame('Protecteur de Plaines du Vent', $player1->getPrestigeTitle());
        $this->assertSame('Protecteur de Plaines du Vent', $player2->getPrestigeTitle());
    }

    public function testUpdateTitlesSkipsUncontrolledRegions(): void
    {
        $season = $this->createSeason();
        $region = $this->createRegion('Plaines du Vent');

        $control = new RegionControl();
        $control->setRegion($region);
        $control->setSeason($season);
        $control->setStartedAt(new \DateTime());
        $control->setCreatedAt(new \DateTime());
        $control->setUpdatedAt(new \DateTime());
        // guild is null — no controlling guild

        $this->controlRepo->method('findBy')
            ->with(['endsAt' => null])
            ->willReturn([$control]);

        // Should not fail
        $this->manager->updateTitles($season);

        $this->assertTrue(true);
    }

    public function testUpdateTitlesMultipleRegions(): void
    {
        $season = $this->createSeason();
        $region1 = $this->createRegion('Plaines du Vent');
        $region2 = $this->createRegion('Foret Sombre');
        $guildA = $this->createGuild('Alpha');
        $guildB = $this->createGuild('Beta');

        $playerA = $this->createPlayer('JoueurA');
        $playerB = $this->createPlayer('JoueurB');

        $this->addMember($guildA, $playerA);
        $this->addMember($guildB, $playerB);

        $control1 = new RegionControl();
        $control1->setRegion($region1);
        $control1->setGuild($guildA);
        $control1->setSeason($season);
        $control1->setStartedAt(new \DateTime());
        $control1->setCreatedAt(new \DateTime());
        $control1->setUpdatedAt(new \DateTime());

        $control2 = new RegionControl();
        $control2->setRegion($region2);
        $control2->setGuild($guildB);
        $control2->setSeason($season);
        $control2->setStartedAt(new \DateTime());
        $control2->setCreatedAt(new \DateTime());
        $control2->setUpdatedAt(new \DateTime());

        $this->controlRepo->method('findBy')
            ->with(['endsAt' => null])
            ->willReturn([$control1, $control2]);

        $this->manager->updateTitles($season);

        $this->assertSame('Protecteur de Plaines du Vent', $playerA->getPrestigeTitle());
        $this->assertSame('Protecteur de Foret Sombre', $playerB->getPrestigeTitle());
    }

    private function mockQueryBuilder(): void
    {
        $query = $this->createMock(Query::class);
        $query->method('execute')->willReturn(0);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('update')->willReturnSelf();
        $qb->method('set')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->em->method('createQueryBuilder')->willReturn($qb);
    }

    private function createRegion(string $name): Region
    {
        $region = new Region();
        $region->setName($name);
        $region->setSlug(strtolower(str_replace(' ', '-', $name)));

        return $region;
    }

    private function createGuild(string $name): Guild
    {
        $guild = new Guild();
        $guild->setName($name);
        $guild->setTag(strtoupper(substr($name, 0, 3)));

        return $guild;
    }

    private function createPlayer(string $name): Player
    {
        $player = new Player();
        $player->setName($name);

        return $player;
    }

    private function addMember(Guild $guild, Player $player): void
    {
        $member = new GuildMember();
        $member->setPlayer($player);
        $member->setRank(GuildRank::Member);
        $guild->addMember($member);
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
}
