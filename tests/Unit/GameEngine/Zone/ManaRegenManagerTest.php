<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Player;
use App\GameEngine\Zone\LifeRegenManager;
use App\GameEngine\Zone\ManaRegenManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * La regeneration des PM hors combat (ARC-04a).
 *
 * GAME_ARCHETYPES § 9 septies : *les PV paient les coups recus, les PM paient
 * les gestes faits, et les deux se rechargent en temps reel*. Avant ce jalon
 * seule la premiere moitie etait vraie — les PM ne revenaient qu'en lancant un
 * sort, c'est-a-dire en depensant ce qu'on cherchait a recuperer.
 *
 * Ce qui est verrouille ici, ce sont les **comportements** et non le curseur :
 * § 0.2 previent qu'aucun nombre du canon n'est definitif, et le nombre de
 * secondes par point vit dans un parametre pour cette raison exacte.
 */
class ManaRegenManagerTest extends TestCase
{
    private function manager(): ManaRegenManager
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        return new ManaRegenManager($entityManager);
    }

    private function player(int $energy, int $maxEnergy = 100): Player
    {
        $player = new Player();
        $player->setMaxEnergy($maxEnergy);
        $player->setEnergy($energy);

        return $player;
    }

    /**
     * La premiere lecture pose l'ancre et ne credite rien.
     *
     * Sans cette regle, un compte dormant depuis des semaines se reveillerait
     * plein pour une raison qui n'est pas du jeu.
     */
    public function testTheFirstReadAnchorsWithoutCrediting(): void
    {
        $manager = $this->manager();
        $player = $this->player(10);

        self::assertSame(0, $manager->refresh($player));
        self::assertSame(10, $player->getEnergy());
        self::assertNotNull($player->getEnergyUpdatedAt());
    }

    /**
     * Le temps ecoule rend des points, par pas entiers.
     */
    public function testElapsedTimeGivesPointsBack(): void
    {
        $manager = $this->manager();
        $player = $this->player(10);
        $seconds = $manager->getRegenSeconds();

        $player->setEnergyUpdatedAt(new \DateTimeImmutable(sprintf('-%d seconds', 5 * $seconds)));

        self::assertSame(5, $manager->refresh($player));
        self::assertSame(15, $player->getEnergy());
    }

    /**
     * Le reliquat de temps est conserve : l'ancre avance par pas entiers.
     *
     * Sinon un joueur qui consulte son ecran souvent perdrait a chaque lecture
     * la fraction de seconde non convertie, et regenererait plus lentement que
     * celui qui ne regarde jamais.
     */
    public function testTheRemainderOfTimeIsKept(): void
    {
        $manager = $this->manager();
        $player = $this->player(10);
        $seconds = $manager->getRegenSeconds();

        $anchor = new \DateTimeImmutable(sprintf('-%d seconds', 2 * $seconds + 3));
        $player->setEnergyUpdatedAt($anchor);

        self::assertSame(2, $manager->refresh($player));
        self::assertSame($anchor->modify(sprintf('+%d seconds', 2 * $seconds))->getTimestamp(), $player->getEnergyUpdatedAt()?->getTimestamp());
    }

    /**
     * La regeneration ne depasse jamais le maximum.
     */
    public function testItNeverGoesAboveTheMaximum(): void
    {
        $manager = $this->manager();
        $player = $this->player(98);
        $player->setEnergyUpdatedAt(new \DateTimeImmutable('-1 day'));

        self::assertSame(2, $manager->refresh($player));
        self::assertSame(100, $player->getEnergy());
    }

    /**
     * Au plein, rien n'est credite et l'ancre repart de maintenant.
     */
    public function testAFullPoolJustMovesTheAnchor(): void
    {
        $manager = $this->manager();
        $player = $this->player(100);
        $player->setEnergyUpdatedAt(new \DateTimeImmutable('-1 day'));

        self::assertSame(0, $manager->refresh($player));
        self::assertSame(100, $player->getEnergy());
        self::assertGreaterThan(time() - 5, $player->getEnergyUpdatedAt()?->getTimestamp());
    }

    /**
     * L'ancre de sortie de combat : entrer plein et sortir a sec doit couter.
     *
     * Sans elle, le temps ecoule depuis le dernier plein compterait comme de
     * la regen, et vider ses PM en combat ne couterait rien du tout.
     */
    public function testAnchoringOnFightExitMakesEmptyingThePoolCostSomething(): void
    {
        $manager = $this->manager();
        $player = $this->player(100);
        $player->setEnergyUpdatedAt(new \DateTimeImmutable('-1 day'));

        // Le combat vide le pool, puis la sortie ancre.
        $player->setEnergy(0);
        $manager->anchor($player);

        self::assertSame(0, $manager->refresh($player));
        self::assertSame(0, $player->getEnergy());
    }

    /**
     * Les PM se rechargent **plus vite** que les PV.
     *
     * Le rapport survit a la recalibration meme si les deux nombres changent :
     * un pool de PM se vide en une rencontre quand une barre de vie tient
     * plusieurs combats. Les inverser rendrait le lanceur de sorts injouable.
     */
    public function testManaComesBackFasterThanLife(): void
    {
        self::assertLessThan(
            LifeRegenManager::DEFAULT_REGEN_SECONDS,
            ManaRegenManager::DEFAULT_REGEN_SECONDS,
            'Les PM se vident plus vite que les PV : ils doivent revenir plus vite.',
        );
    }

    /**
     * Le curseur vit dans un parametre, jamais dans le code.
     *
     * C'est la consequence de § 0.2 : aucun nombre du canon n'est definitif, et
     * celui-ci decide de tout l'equilibre solo. Il doit se deplacer sans
     * livraison.
     */
    public function testTheCursorIsAParameter(): void
    {
        self::assertSame('zone.mana.regen_seconds', ManaRegenManager::PARAM_REGEN_SECONDS);
        self::assertGreaterThan(0, $this->manager()->getRegenSeconds());
    }
}
