<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\Game\Spell;
use App\Event\Fight\PlayerAttackHitEvent;
use App\Event\Fight\PlayerAttackMissEvent;
use App\GameEngine\Fight\BareHandsAttack;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Item\ItemUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * ONB-20a — les mains nues.
 *
 * Le repli qui empeche un chemin de combat d'echouer faute d'arme.
 */
class BareHandsAttackTest extends TestCase
{
    public function testAStrikeThatLandsAppliesTheSpellAndAnnouncesItself(): void
    {
        $spell = new Spell();
        $applicator = $this->createMock(SpellApplicator::class);
        $applicator->expects($this->once())->method('apply')->with($spell);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch')
            ->with($this->isInstanceOf(PlayerAttackHitEvent::class), PlayerAttackHitEvent::NAME)
            ->willReturnArgument(0);

        // Chances forcees a 100 % : on teste le chemin, pas le tirage.
        $attack = $this->attackAlwaysHitting($spell, $applicator, $dispatcher);

        $this->assertTrue($attack->strike(new Player(), new Player()));
    }

    /**
     * Sans sort en base, on rate le coup — on ne leve pas. Une exception ici
     * rendrait le combat impossible, c'est-a-dire exactement le defaut que ce
     * jalon repare.
     */
    public function testAMissingSpellMissesInsteadOfThrowing(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch')
            ->with($this->isInstanceOf(PlayerAttackMissEvent::class), PlayerAttackMissEvent::NAME)
            ->willReturnArgument(0);

        $applicator = $this->createMock(SpellApplicator::class);
        $applicator->expects($this->never())->method('apply');

        $attack = new BareHandsAttack($this->entityManagerReturning(null), $applicator, $dispatcher);

        $this->assertFalse($attack->strike(new Player(), new Player()));
    }

    /**
     * On ne frappe pas mieux en ne sachant rien : les chances sont celles d'un
     * geste sans entrainement, sous les chances de base.
     */
    public function testBareHandsAreWorseThanAnyTrainedGesture(): void
    {
        $attack = new BareHandsAttack(
            $this->entityManagerReturning(new Spell()),
            $this->createMock(SpellApplicator::class),
            $this->createMock(EventDispatcherInterface::class),
        );

        $this->assertLessThan(ItemUtils::DEFAULT_HIT_CHANCES, $attack->hitChances());
        $this->assertGreaterThan(0, $attack->hitChances());
    }

    private function entityManagerReturning(?Spell $spell): EntityManagerInterface
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($spell);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        return $entityManager;
    }

    private function attackAlwaysHitting(
        Spell $spell,
        SpellApplicator $applicator,
        EventDispatcherInterface $dispatcher,
    ): BareHandsAttack {
        return new class($this->entityManagerReturning($spell), $applicator, $dispatcher) extends BareHandsAttack {
            public function hitChances(): int
            {
                return 100;
            }
        };
    }
}
