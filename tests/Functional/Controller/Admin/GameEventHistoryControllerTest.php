<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Controller\Admin\GameEventController;
use App\Entity\App\GameEvent;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class GameEventHistoryControllerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private GameEventController $controller;
    /** @var array<string, mixed> */
    private array $capturedTemplateVars = [];
    private string $capturedTemplateName = '';
    /** @var list<array{method: string, args: array<int, mixed>}> */
    private array $qbCalls = [];

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->controller = new GameEventController(
            $this->em,
            $this->createMock(AdminLogger::class),
            $this->createMock(EventDispatcherInterface::class),
        );

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturnCallback(function (string $template, array $vars) {
            $this->capturedTemplateName = $template;
            $this->capturedTemplateVars = $vars;

            return '';
        });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(function (string $id) use ($twig) {
            if ($id === 'twig') {
                return $twig;
            }

            return null;
        });
        $this->controller->setContainer($container);
    }

    public function testHistoryRendersHistoryTemplate(): void
    {
        $this->configureEntityManagerMocks(events: [], total: 0);

        $response = $this->controller->history(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('admin/event/history.html.twig', $this->capturedTemplateName);
        $this->assertSame([], $this->capturedTemplateVars['events']);
        $this->assertSame(0, $this->capturedTemplateVars['total']);
        $this->assertSame(1, $this->capturedTemplateVars['currentPage']);
        $this->assertSame(1, $this->capturedTemplateVars['totalPages']);
    }

    public function testHistoryFiltersOnPastStatusesByDefault(): void
    {
        $this->configureEntityManagerMocks(events: [], total: 0);

        $this->controller->history(new Request());

        $whereCalls = array_values(array_filter($this->qbCalls, fn ($c) => $c['method'] === 'where'));
        $this->assertNotEmpty($whereCalls);
        $this->assertSame('e.status IN (:pastStatuses)', $whereCalls[0]['args'][0]);

        $pastStatuses = null;
        foreach ($this->qbCalls as $call) {
            if ($call['method'] === 'setParameter' && $call['args'][0] === 'pastStatuses') {
                $pastStatuses = $call['args'][1];
                break;
            }
        }
        $this->assertSame([GameEvent::STATUS_COMPLETED, GameEvent::STATUS_CANCELLED], $pastStatuses);
    }

    public function testHistoryAppliesStatusFilterWhenProvided(): void
    {
        $this->configureEntityManagerMocks(events: [], total: 0);

        $request = new Request(query: ['status' => GameEvent::STATUS_CANCELLED]);
        $this->controller->history($request);

        $andWheres = array_map(
            fn ($c) => $c['args'][0],
            array_filter($this->qbCalls, fn ($c) => $c['method'] === 'andWhere')
        );
        $this->assertContains('e.status = :status', $andWheres);
        $this->assertSame(GameEvent::STATUS_CANCELLED, $this->capturedTemplateVars['status']);
    }

    public function testHistoryIgnoresInvalidStatusFilter(): void
    {
        $this->configureEntityManagerMocks(events: [], total: 0);

        $request = new Request(query: ['status' => 'scheduled']);
        $this->controller->history($request);

        $andWheres = array_map(
            fn ($c) => $c['args'][0],
            array_filter($this->qbCalls, fn ($c) => $c['method'] === 'andWhere')
        );
        $this->assertNotContains('e.status = :status', $andWheres);
        $this->assertSame('scheduled', $this->capturedTemplateVars['status']);
    }

    public function testHistoryAppliesTypeFilterWhenWhitelisted(): void
    {
        $this->configureEntityManagerMocks(events: [], total: 0);

        $request = new Request(query: ['type' => GameEvent::TYPE_BOSS_SPAWN]);
        $this->controller->history($request);

        $andWheres = array_map(
            fn ($c) => $c['args'][0],
            array_filter($this->qbCalls, fn ($c) => $c['method'] === 'andWhere')
        );
        $this->assertContains('e.type = :type', $andWheres);
        $this->assertSame(GameEvent::TYPE_BOSS_SPAWN, $this->capturedTemplateVars['type']);
    }

    public function testHistoryIgnoresUnknownType(): void
    {
        $this->configureEntityManagerMocks(events: [], total: 0);

        $request = new Request(query: ['type' => 'not_a_real_type']);
        $this->controller->history($request);

        $andWheres = array_map(
            fn ($c) => $c['args'][0],
            array_filter($this->qbCalls, fn ($c) => $c['method'] === 'andWhere')
        );
        $this->assertNotContains('e.type = :type', $andWheres);
    }

    public function testHistoryComputesPaginationFromTotal(): void
    {
        $events = [new GameEvent(), new GameEvent()];
        $this->configureEntityManagerMocks(events: $events, total: 60);

        $request = new Request(query: ['page' => '2']);
        $this->controller->history($request);

        $this->assertSame($events, $this->capturedTemplateVars['events']);
        $this->assertSame(60, $this->capturedTemplateVars['total']);
        $this->assertSame(2, $this->capturedTemplateVars['currentPage']);
        $this->assertSame(3, $this->capturedTemplateVars['totalPages']); // ceil(60 / 25) = 3
    }

    /**
     * @param list<GameEvent> $events
     */
    private function configureEntityManagerMocks(array $events, int $total): void
    {
        $countQuery = $this->createMock(Query::class);
        $countQuery->method('getSingleScalarResult')->willReturn($total);

        $listQuery = $this->createMock(Query::class);
        $listQuery->method('getResult')->willReturn($events);

        $getQueryCalls = 0;

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('where')->willReturnCallback(function (string $dql) use ($qb): QueryBuilder {
            $this->qbCalls[] = ['method' => 'where', 'args' => [$dql]];

            return $qb;
        });
        $qb->method('andWhere')->willReturnCallback(function (string $dql) use ($qb): QueryBuilder {
            $this->qbCalls[] = ['method' => 'andWhere', 'args' => [$dql]];

            return $qb;
        });
        $qb->method('setParameter')->willReturnCallback(function (string $name, mixed $value) use ($qb): QueryBuilder {
            $this->qbCalls[] = ['method' => 'setParameter', 'args' => [$name, $value]];

            return $qb;
        });
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('select')->willReturnSelf();
        $qb->method('resetDQLPart')->willReturnSelf();
        // First getQuery() call -> count query (from cloned builder with select COUNT)
        // Second getQuery() call -> list query
        $qb->method('getQuery')->willReturnCallback(function () use (&$getQueryCalls, $countQuery, $listQuery): Query {
            ++$getQueryCalls;

            return $getQueryCalls === 1 ? $countQuery : $listQuery;
        });

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('createQueryBuilder')->willReturn($qb);

        $this->em->method('getRepository')->with(GameEvent::class)->willReturn($repo);
    }
}
