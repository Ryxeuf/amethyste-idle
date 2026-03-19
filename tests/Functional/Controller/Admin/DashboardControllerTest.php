<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Controller\Admin\DashboardController;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class DashboardControllerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private DashboardController $controller;
    /** @var array<string, mixed> */
    private array $capturedTemplateVars = [];

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->controller = new DashboardController($this->em);

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturnCallback(function (string $template, array $vars) {
            $this->capturedTemplateVars = $vars;

            return '';
        });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(function (string $id) use ($twig) {
            if ($id === 'twig') {
                return $twig;
            }
            if ($id === 'parameter_bag') {
                $bag = $this->createMock(\Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface::class);
                $bag->method('get')->with('kernel.project_dir')->willReturn('/tmp/test-project');

                return $bag;
            }

            return null;
        });
        $this->controller->setContainer($container);
    }

    public function testIndexReturnsOk(): void
    {
        $this->configureEntityManagerMocks();

        $response = $this->controller->index();

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testIndexContainsZoneStats(): void
    {
        $this->configureEntityManagerMocks();

        $this->controller->index();

        $this->assertArrayHasKey('zoneStats', $this->capturedTemplateVars);
    }

    public function testZoneStatsContainsCorrectCounts(): void
    {
        $this->configureEntityManagerMocks(
            pnjData: [
                ['id' => 1, 'name' => 'Plaine de Départ', 'pnjCount' => 3],
                ['id' => 2, 'name' => 'Forêt Sombre', 'pnjCount' => 1],
            ],
            mobData: [
                ['id' => 1, 'name' => 'Plaine de Départ', 'mobCount' => 5],
                ['id' => 2, 'name' => 'Forêt Sombre', 'mobCount' => 12],
            ],
            playerData: [
                ['id' => 1, 'name' => 'Plaine de Départ', 'playerCount' => 2],
                ['id' => 2, 'name' => 'Forêt Sombre', 'playerCount' => 0],
            ],
        );

        $this->controller->index();

        $zoneStats = $this->capturedTemplateVars['zoneStats'];
        $this->assertCount(2, $zoneStats);

        $this->assertSame('Plaine de Départ', $zoneStats[0]['name']);
        $this->assertSame(3, $zoneStats[0]['pnjCount']);
        $this->assertSame(5, $zoneStats[0]['mobCount']);
        $this->assertSame(2, $zoneStats[0]['playerCount']);

        $this->assertSame('Forêt Sombre', $zoneStats[1]['name']);
        $this->assertSame(1, $zoneStats[1]['pnjCount']);
        $this->assertSame(12, $zoneStats[1]['mobCount']);
        $this->assertSame(0, $zoneStats[1]['playerCount']);
    }

    public function testZoneStatsEmptyWhenNoMaps(): void
    {
        $this->configureEntityManagerMocks(pnjData: [], mobData: [], playerData: []);

        $this->controller->index();

        $zoneStats = $this->capturedTemplateVars['zoneStats'];
        $this->assertCount(0, $zoneStats);
    }

    /**
     * @param list<array{id: int, name: string, pnjCount: int}>    $pnjData
     * @param list<array{id: int, name: string, mobCount: int}>    $mobData
     * @param list<array{id: int, name: string, playerCount: int}> $playerData
     */
    private function configureEntityManagerMocks(
        array $pnjData = [['id' => 1, 'name' => 'TestMap', 'pnjCount' => 0]],
        array $mobData = [['id' => 1, 'name' => 'TestMap', 'mobCount' => 0]],
        array $playerData = [['id' => 1, 'name' => 'TestMap', 'playerCount' => 0]],
    ): void {
        // Mock repository for count() calls (metrics + liveStats)
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('count')->willReturn(0);

        // Mock repository for AdminLog with its own query builder
        $logQuery = $this->createMock(AbstractQuery::class);
        $logQuery->method('getResult')->willReturn([]);

        $logQb = $this->createMock(QueryBuilder::class);
        $logQb->method('leftJoin')->willReturnSelf();
        $logQb->method('addSelect')->willReturnSelf();
        $logQb->method('orderBy')->willReturnSelf();
        $logQb->method('setMaxResults')->willReturnSelf();
        $logQb->method('getQuery')->willReturn($logQuery);

        $logRepo = $this->createMock(EntityRepository::class);
        $logRepo->method('count')->willReturn(0);
        $logRepo->method('createQueryBuilder')->willReturn($logQb);

        $this->em->method('getRepository')->willReturnCallback(
            function (string $class) use ($repo, $logRepo) {
                if ($class === \App\Entity\App\AdminLog::class) {
                    return $logRepo;
                }

                return $repo;
            }
        );

        // Order matches controller: totalGils, bannedPlayers, then 3 zone stats queries
        $queryBuilders = [];

        // 1. totalGils query builder
        $gilsQuery = $this->createMock(AbstractQuery::class);
        $gilsQuery->method('getSingleScalarResult')->willReturn(0);
        $gilsQb = $this->createMock(QueryBuilder::class);
        $gilsQb->method('select')->willReturnSelf();
        $gilsQb->method('from')->willReturnSelf();
        $gilsQb->method('getQuery')->willReturn($gilsQuery);
        $queryBuilders[] = $gilsQb;

        // 2. bannedPlayers query builder
        $bannedQuery = $this->createMock(AbstractQuery::class);
        $bannedQuery->method('getSingleScalarResult')->willReturn(0);
        $bannedQb = $this->createMock(QueryBuilder::class);
        $bannedQb->method('select')->willReturnSelf();
        $bannedQb->method('from')->willReturnSelf();
        $bannedQb->method('where')->willReturnSelf();
        $bannedQb->method('getQuery')->willReturn($bannedQuery);
        $queryBuilders[] = $bannedQb;

        // 3-5. Zone stats query builders (pnj, mobs, players)
        foreach ([$pnjData, $mobData, $playerData] as $result) {
            $query = $this->createMock(AbstractQuery::class);
            $query->method('getResult')->willReturn($result);

            $qb = $this->createMock(QueryBuilder::class);
            $qb->method('select')->willReturnSelf();
            $qb->method('from')->willReturnSelf();
            $qb->method('leftJoin')->willReturnSelf();
            $qb->method('groupBy')->willReturnSelf();
            $qb->method('orderBy')->willReturnSelf();
            $qb->method('setParameter')->willReturnSelf();
            $qb->method('getQuery')->willReturn($query);
            $queryBuilders[] = $qb;
        }

        $callIndex = 0;
        $this->em->method('createQueryBuilder')->willReturnCallback(
            function () use (&$callIndex, $queryBuilders) {
                return $queryBuilders[$callIndex++];
            }
        );
    }
}
