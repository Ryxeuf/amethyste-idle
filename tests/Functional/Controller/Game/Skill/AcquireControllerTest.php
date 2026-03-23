<?php

namespace App\Tests\Functional\Controller\Game\Skill;

use App\Controller\Game\Skill\AcquireController;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\SkillAcquiring;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AcquireControllerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private SkillAcquiring&MockObject $skillAcquiring;
    private AcquireController $controller;
    private FlashBag $flashBag;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->skillAcquiring = $this->createMock(SkillAcquiring::class);
        $this->flashBag = new FlashBag();

        $this->controller = new AcquireController(
            $this->entityManager,
            $this->skillAcquiring,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testAcquireSuccess(): void
    {
        $skill = $this->createMock(Skill::class);
        $domain = $this->createMock(Domain::class);

        $this->setupRepositories($skill, $domain);

        $this->skillAcquiring->expects($this->once())->method('acquireSkill')->with($skill);

        $request = $this->createAcquireRequest(1, 1);
        $response = $this->controller->__invoke($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNotEmpty($this->flashBag->peek('success'));
        $this->assertStringContainsString('acquise', $this->flashBag->peek('success')[0]);
    }

    public function testAcquireSkillNotFoundShowsError(): void
    {
        $this->setupRepositories(skill: null, domain: $this->createMock(Domain::class));

        $this->skillAcquiring->expects($this->never())->method('acquireSkill');

        $request = $this->createAcquireRequest(999, 1);
        $response = $this->controller->__invoke($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNotEmpty($this->flashBag->peek('error'));
        $this->assertStringContainsString('non trouvé', $this->flashBag->peek('error')[0]);
    }

    public function testAcquireDomainNotFoundShowsError(): void
    {
        $this->setupRepositories(skill: $this->createMock(Skill::class), domain: null);

        $this->skillAcquiring->expects($this->never())->method('acquireSkill');

        $request = $this->createAcquireRequest(1, 999);
        $response = $this->controller->__invoke($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNotEmpty($this->flashBag->peek('error'));
    }

    private function createAcquireRequest(int $skillId, int $domainId): Request
    {
        return Request::create('/game/skills/acquire', 'POST', [
            'skill_id' => $skillId,
            'domain_id' => $domainId,
        ]);
    }

    private function setupRepositories(?Skill $skill, ?Domain $domain): void
    {
        $skillRepo = $this->createMock(EntityRepository::class);
        $skillRepo->method('find')->willReturn($skill);

        $domainRepo = $this->createMock(EntityRepository::class);
        $domainRepo->method('find')->willReturn($domain);

        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Skill::class => $skillRepo,
                Domain::class => $domainRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );
    }

    private function createContainer(): ContainerInterface&MockObject
    {
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/game/skills');

        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);

        $requestStack = $this->createMock(\Symfony\Component\HttpFoundation\RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $services = [
            'security.authorization_checker' => $authChecker,
            'router' => $router,
            'request_stack' => $requestStack,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => isset($services[$id]));
        $container->method('get')->willReturnCallback(fn (string $id) => $services[$id] ?? null);

        return $container;
    }
}
