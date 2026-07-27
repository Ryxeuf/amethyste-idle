<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Controller\Admin\DashboardController;
use App\Entity\App\Zone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
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
            zones: [$this->zone(1, 'Plaine de Départ'), $this->zone(2, 'Forêt Sombre')],
            pnjData: [
                ['zoneId' => 1, 'total' => 3],
                ['zoneId' => 2, 'total' => 1],
            ],
            mobData: [
                ['zoneId' => 1, 'total' => 5],
                ['zoneId' => 2, 'total' => 12],
            ],
            playerData: [
                ['zoneId' => 1, 'total' => 2],
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
        // Aucune ligne renvoyee pour cette zone : le compteur reste a zero.
        $this->assertSame(0, $zoneStats[1]['playerCount']);
    }

    public function testZoneStatsEmptyWhenNoZone(): void
    {
        $this->configureEntityManagerMocks(zones: []);

        $this->controller->index();

        $this->assertCount(0, $this->capturedTemplateVars['zoneStats']);
    }

    private function zone(int $id, string $name): Zone
    {
        $zone = (new Zone())->setName($name)->setSlug(strtolower(str_replace(' ', '-', $name)));
        $ref = new \ReflectionProperty(Zone::class, 'id');
        $ref->setValue($zone, $id);

        return $zone;
    }

    /**
     * @param list<Zone>                           $zones
     * @param list<array{zoneId: int, total: int}> $pnjData
     * @param list<array{zoneId: int, total: int}> $mobData
     * @param list<array{zoneId: int, total: int}> $playerData
     */
    private function configureEntityManagerMocks(
        ?array $zones = null,
        array $pnjData = [],
        array $mobData = [],
        array $playerData = [],
    ): void {
        $zones ??= [$this->zone(1, 'TestZone')];

        // Repository generique pour les compteurs (metriques + stats live).
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('count')->willReturn(0);

        $logQuery = $this->createMock(Query::class);
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

        // Liste des zones actives, lue via le repository de Zone.
        $zonesQuery = $this->createMock(Query::class);
        $zonesQuery->method('getResult')->willReturn($zones);

        $zonesQb = $this->createMock(QueryBuilder::class);
        $zonesQb->method('andWhere')->willReturnSelf();
        $zonesQb->method('orderBy')->willReturnSelf();
        $zonesQb->method('getQuery')->willReturn($zonesQuery);

        $zoneRepo = $this->createMock(EntityRepository::class);
        $zoneRepo->method('count')->willReturn(0);
        $zoneRepo->method('createQueryBuilder')->willReturn($zonesQb);

        $this->em->method('getRepository')->willReturnCallback(
            function (string $class) use ($repo, $logRepo, $zoneRepo) {
                if ($class === \App\Entity\App\AdminLog::class) {
                    return $logRepo;
                }
                if ($class === Zone::class) {
                    return $zoneRepo;
                }

                return $repo;
            }
        );

        // Ordre des appels du controleur : totalGils, bannedPlayers, puis les
        // trois agregats par zone (PNJ, creatures vivantes, joueurs connectes).
        $queryBuilders = [];

        $gilsQuery = $this->createMock(Query::class);
        $gilsQuery->method('getSingleScalarResult')->willReturn(0);
        $gilsQb = $this->createMock(QueryBuilder::class);
        $gilsQb->method('select')->willReturnSelf();
        $gilsQb->method('from')->willReturnSelf();
        $gilsQb->method('getQuery')->willReturn($gilsQuery);
        $queryBuilders[] = $gilsQb;

        $bannedQuery = $this->createMock(Query::class);
        $bannedQuery->method('getSingleScalarResult')->willReturn(0);
        $bannedQb = $this->createMock(QueryBuilder::class);
        $bannedQb->method('select')->willReturnSelf();
        $bannedQb->method('from')->willReturnSelf();
        $bannedQb->method('where')->willReturnSelf();
        $bannedQb->method('getQuery')->willReturn($bannedQuery);
        $queryBuilders[] = $bannedQb;

        foreach ([$pnjData, $mobData, $playerData] as $result) {
            $query = $this->createMock(Query::class);
            $query->method('getResult')->willReturn($result);

            $qb = $this->createMock(QueryBuilder::class);
            $qb->method('select')->willReturnSelf();
            $qb->method('from')->willReturnSelf();
            $qb->method('join')->willReturnSelf();
            $qb->method('where')->willReturnSelf();
            $qb->method('andWhere')->willReturnSelf();
            $qb->method('groupBy')->willReturnSelf();
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
