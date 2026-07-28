<?php

namespace App\Tests\Unit\GameEngine\World;

use App\Entity\App\Parameter;
use App\GameEngine\Codex\WorldFactService;
use App\GameEngine\World\WorldLoadService;
use App\GameEngine\World\WorldScaleService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * FOY-17b — le facteur de monde `W`.
 *
 * L'invariant a servir (BALANCE § 22.4) : **le temps qu'il faut pour faire
 * monter un foyer, et la tension ressentie sur un filon, doivent etre les memes
 * a 50 joueurs et a 500**. Ce n'est pas « le monde grossit », c'est
 * « l'experience reste constante quand la population change ».
 *
 * Les trois regles qui en decoulent sont testees ici ; la quatrieme — `W`
 * n'atteint jamais la vitalite, la purete ni la Paleur — se verifie la ou elle
 * s'applique, dans `GatherServiceTest`.
 */
class WorldScaleServiceTest extends TestCase
{
    private const TIDE_DAYS = 28;

    /**
     * Memes paliers que `config/services.yaml` : `W = 1` a ~50 joueurs
     * effectifs, la cible de calibrage de BALANCE § 22.3.
     */
    private const BANDS = [
        ['population' => 0, 'scale' => 0.5],
        ['population' => 15, 'scale' => 0.75],
        ['population' => 35, 'scale' => 1],
        ['population' => 80, 'scale' => 1.5],
        ['population' => 160, 'scale' => 2],
        ['population' => 320, 'scale' => 3],
    ];

    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $parameterRepository;
    private WorldLoadService&MockObject $worldLoad;
    private WorldFactService&MockObject $worldFacts;

    /** @var array<string, Parameter> */
    private array $parameters = [];

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepository = $this->createMock(EntityRepository::class);
        $this->worldLoad = $this->createMock(WorldLoadService::class);
        $this->worldFacts = $this->createMock(WorldFactService::class);

        $this->em->method('getRepository')->willReturn($this->parameterRepository);
        $this->parameterRepository->method('findOneBy')->willReturnCallback(
            fn (array $criteria): ?Parameter => $this->parameters[$criteria['name']] ?? null,
        );
        $this->em->method('persist')->willReturnCallback(function (object $entity): void {
            if ($entity instanceof Parameter) {
                $this->parameters[$entity->getName()] = $entity;
            }
        });
    }

    private function service(float $population, int $measuredDays = self::TIDE_DAYS): WorldScaleService
    {
        // Un mock de charge **neuf** a chaque appel : re-configurer le meme
        // laisserait la premiere valeur gagner, et le balayage des paliers
        // lirait partout la meme population.
        $this->worldLoad = $this->createMock(WorldLoadService::class);
        $this->worldLoad->method('effectivePopulation')->willReturn($population);
        $this->worldLoad->method('measuredDays')->willReturn($measuredDays);

        return new WorldScaleService(
            $this->em,
            $this->worldLoad,
            $this->worldFacts,
            new NullLogger(),
            self::BANDS,
            0.5,
            1,
            self::TIDE_DAYS,
        );
    }

    private function seedScale(float $scale): void
    {
        $parameter = new Parameter();
        $parameter->setName(WorldScaleService::PARAM_SCALE);
        $parameter->setValue((string) $scale);
        $this->parameters[WorldScaleService::PARAM_SCALE] = $parameter;
    }

    // -----------------------------------------------------------------
    // Paliers
    // -----------------------------------------------------------------

    /**
     * L'ancrage du calibrage : `W = 1` a la cible de 50 joueurs effectifs.
     */
    public function testFiftyEffectivePlayersAnchorTheScaleAtOne(): void
    {
        self::assertSame(1.0, $this->service(50.0)->targetScale());
    }

    public function testScaleMovesByDiscreteBandsNeverContinuously(): void
    {
        $seen = [];
        foreach ([0.0, 5.0, 20.0, 40.0, 50.0, 79.0, 100.0, 200.0, 500.0] as $population) {
            $seen[] = $this->service($population)->targetScale();
        }

        self::assertSame([0.5, 0.5, 0.75, 1.0, 1.0, 1.0, 1.5, 2.0, 3.0], $seen);
    }

    /**
     * Un serveur qui demarre a cinq joueurs ne doit pas se refermer sur eux.
     */
    public function testScaleNeverFallsBelowItsFloor(): void
    {
        self::assertSame(0.5, $this->service(0.0)->targetScale());
    }

    public function testDefaultScaleIsOneWhenNothingWasEverWritten(): void
    {
        self::assertSame(1.0, $this->service(50.0)->current());
    }

    // -----------------------------------------------------------------
    // Asymetrie : monte vite, redescend lentement
    // -----------------------------------------------------------------

    public function testExpansionHappensOnAnyDailyTick(): void
    {
        $this->seedScale(1.0);

        $service = $this->service(200.0);

        self::assertSame(2.0, $service->evaluate(false));
        self::assertSame(2.0, $service->current());
    }

    /**
     * Une baisse passagere de frequentation ne doit jamais retrecir le monde
     * sous les pieds des joueurs presents.
     */
    public function testContractionIsRefusedOutsideATideBoundary(): void
    {
        $this->seedScale(2.0);

        $service = $this->service(10.0);

        self::assertNull($service->evaluate(false));
        self::assertSame(2.0, $service->current());
    }

    public function testContractionHappensAtATideBoundary(): void
    {
        $this->seedScale(2.0);

        $service = $this->service(10.0);

        self::assertSame(0.5, $service->evaluate(true));
        self::assertSame(0.5, $service->current());
    }

    /**
     * Periode de grace : on ne contracte pas sur une fenetre qu'on n'a pas eu
     * le temps de remplir. Les premieres marees ne retrecissent jamais.
     */
    public function testContractionIsHeldBackWhileTheWindowIsIncomplete(): void
    {
        $this->seedScale(2.0);

        $service = $this->service(10.0, 12);

        self::assertNull($service->evaluate(true));
        self::assertSame(2.0, $service->current());
    }

    /**
     * L'expansion, elle, n'attend pas la periode de grace : un jeune serveur
     * qui grandit doit pouvoir ouvrir son monde tout de suite.
     */
    public function testExpansionIgnoresTheGracePeriod(): void
    {
        $this->seedScale(1.0);

        self::assertSame(2.0, $this->service(200.0, 3)->evaluate(false));
    }

    public function testNothingHappensWhenTheBandDoesNotChange(): void
    {
        $this->seedScale(1.0);

        $this->worldFacts->expects(self::never())->method('recordWorldFact');

        self::assertNull($this->service(50.0)->evaluate(true));
    }

    // -----------------------------------------------------------------
    // Verrou et annonce
    // -----------------------------------------------------------------

    /**
     * Pour un evenement, pour un test, et pour le jour ou la valeur automatique
     * aura tort.
     */
    public function testALockedScaleNeverMoves(): void
    {
        $this->seedScale(1.0);
        $lock = new Parameter();
        $lock->setName(WorldScaleService::PARAM_LOCK);
        $lock->setValue('1');
        $this->parameters[WorldScaleService::PARAM_LOCK] = $lock;

        $service = $this->service(500.0);

        self::assertTrue($service->isLocked());
        self::assertNull($service->evaluate(true));
        self::assertSame(1.0, $service->current());
    }

    /**
     * Annonce, jamais silencieux : une necessite technique devient un evenement
     * du monde plutot qu'un ajustement subi.
     */
    public function testEveryChangeIsWrittenToTheWorldJournal(): void
    {
        $this->seedScale(1.0);

        $this->worldFacts->expects(self::once())->method('recordWorldFact');

        $this->service(200.0)->evaluate(false);
    }
}
