<?php

namespace App\Tests\Unit\GameEngine\Crafting;

use App\Entity\App\CraftJob;
use App\Entity\App\Player;
use App\Entity\Game\Recipe;
use PHPUnit\Framework\TestCase;

/**
 * ECO-20 — le travail d'atelier a une duree.
 *
 * `Recipe.craftingTime` etait affiche au joueur sur chaque carte de recette et
 * applique nulle part : `craft()` consommait et produisait dans la meme
 * requete. Depuis ECO-07a les commandes le respectaient, l'etabli non — deux
 * regimes de temps pour la meme action.
 */
final class CraftJobTest extends TestCase
{
    public function testAJobIsNotReadyBeforeItsDeadline(): void
    {
        $job = $this->job(new \DateTimeImmutable('+30 seconds'));

        self::assertFalse($job->isReady());
        self::assertGreaterThan(25, $job->getRemainingSeconds());
    }

    public function testAJobIsReadyOnceItsDeadlineHasPassed(): void
    {
        $job = $this->job(new \DateTimeImmutable('-1 second'));

        self::assertTrue($job->isReady());
        self::assertSame(0, $job->getRemainingSeconds(), 'Le decompte ne devient jamais negatif.');
    }

    /**
     * La quantite ne doit pas pouvoir descendre sous 1 : un travail de zero
     * piece occuperait l'etabli sans rien produire.
     */
    public function testQuantityIsFlooredAtOne(): void
    {
        $job = $this->job(new \DateTimeImmutable('+10 seconds'));

        $job->setQuantity(0);
        self::assertSame(1, $job->getQuantity());

        $job->setQuantity(-5);
        self::assertSame(1, $job->getQuantity());
    }

    private function job(\DateTimeImmutable $readyAt): CraftJob
    {
        $recipe = new Recipe();
        $recipe->setName('Dague de fer');
        $recipe->setSlug('recipe-iron-dagger');
        $recipe->setCraft('forgeron');
        $recipe->setCraftingTime(12);

        $job = new CraftJob();
        $job->setPlayer(new Player());
        $job->setRecipe($recipe);
        $job->setQuantity(3);
        $job->setReadyAt($readyAt);

        return $job;
    }
}
