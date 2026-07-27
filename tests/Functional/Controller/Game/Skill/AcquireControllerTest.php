<?php

namespace App\Tests\Functional\Controller\Game\Skill;

use App\Controller\Game\Skill\AcquireController;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\SkillAcquiring;
use App\GameEngine\Progression\SkillAcquisitionResult;
use App\Helper\PlayerSkillHelper;
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
use Symfony\Contracts\Translation\TranslatorInterface;

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

        // Le traducteur rend la cle : le test verifie le message choisi, pas sa
        // traduction.
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $this->controller = new AcquireController(
            $this->entityManager,
            $this->skillAcquiring,
            $translator,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testAcquireSuccess(): void
    {
        $skill = $this->createMock(Skill::class);
        $this->setupRepository($skill);

        $this->skillAcquiring->expects($this->once())->method('acquireSkill')->with($skill)
            ->willReturn(SkillAcquisitionResult::acquired());

        $response = $this->controller->__invoke($this->createAcquireRequest(1));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertSame(['game.skills.acquire.success'], $this->flashBag->peek('success'));
    }

    /**
     * Un refus disait « acquise avec succes » : le joueur cliquait en boucle sans
     * savoir ce qui manquait.
     */
    public function testRefusalIsReportedWithItsReason(): void
    {
        $skill = $this->createMock(Skill::class);
        $this->setupRepository($skill);

        $this->skillAcquiring->method('acquireSkill')
            ->willReturn(SkillAcquisitionResult::refused(PlayerSkillHelper::REFUSAL_NOT_ENOUGH_XP));

        $response = $this->controller->__invoke($this->createAcquireRequest(1));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEmpty($this->flashBag->peek('success'));
        $this->assertSame(
            ['game.skills.acquire.refused.not_enough_xp'],
            $this->flashBag->peek('warning'),
        );
    }

    public function testAcquireSkillNotFoundShowsError(): void
    {
        $this->setupRepository(null);

        $this->skillAcquiring->expects($this->never())->method('acquireSkill');

        $response = $this->controller->__invoke($this->createAcquireRequest(999));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertSame(['game.skills.acquire.not_found'], $this->flashBag->peek('error'));
    }

    /**
     * Le domaine d'ou vient le clic n'entre plus dans la decision : l'acquisition
     * credite tous les domaines de la competence.
     */
    public function testAcquireWorksWithoutDomainField(): void
    {
        $skill = $this->createMock(Skill::class);
        $this->setupRepository($skill);

        $this->skillAcquiring->expects($this->once())->method('acquireSkill')
            ->willReturn(SkillAcquisitionResult::acquired());

        $request = Request::create('/game/skills/acquire', 'POST', ['skill_id' => 1]);
        $response = $this->controller->__invoke($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertSame(['game.skills.acquire.success'], $this->flashBag->peek('success'));
    }

    private function createAcquireRequest(int $skillId): Request
    {
        return Request::create('/game/skills/acquire', 'POST', [
            'skill_id' => $skillId,
            'domain_id' => 1,
        ]);
    }

    private function setupRepository(?Skill $skill): void
    {
        $skillRepo = $this->createMock(EntityRepository::class);
        $skillRepo->method('find')->willReturn($skill);

        $this->entityManager->method('getRepository')->willReturn($skillRepo);
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
