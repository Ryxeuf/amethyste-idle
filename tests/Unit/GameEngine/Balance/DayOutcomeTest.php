<?php

namespace App\Tests\Unit\GameEngine\Balance;

use App\GameEngine\Balance\DayOutcome;
use PHPUnit\Framework\TestCase;

/**
 * La lecture d'une journee simulee (ARC-17c-c).
 *
 * Le releve d'une journee a **deux colonnes qui ne se lisent pas l'une sans
 * l'autre** : ce qu'elle a coute, et ce qu'elle a permis de faire. Ce test tient
 * la seconde, parce que c'est elle qui empeche la premiere de mentir.
 */
class DayOutcomeTest extends TestCase
{
    /**
     * **Un build qui tombe avant la fin de ses communs ne compte pas.**.
     *
     * C'est le garde-fou de l'ancre de fonction : une journee arretee a la
     * troisieme rencontre coute peu, et la lire comme une attente courte ferait
     * du build le plus fragile le plus econome — l'inverse exact de ce qu'on
     * mesure.
     */
    public function testADayCutShortIsNotADayCarriedToItsEnd(): void
    {
        $cutShort = $this->outcome(commonsBudgeted: 14, cleared: 3, deaths: 1);

        self::assertFalse($cutShort->clearedItsCommons());
    }

    /**
     * **Tomber sur une tentative d'elite ne disqualifie pas la journee.**.
     *
     * Le mot du canon est « tentative », et le § 9 octies exige justement qu'une
     * elite tue son joueur seul. Exiger qu'elles se concluent ecarterait tous
     * les builds du releve au motif qu'ils obeissent a la regle.
     */
    public function testFallingOnAnEliteAttemptStillCountsAsAFullDay(): void
    {
        $full = $this->outcome(commonsBudgeted: 14, cleared: 14, deaths: 1);

        self::assertTrue($full->clearedItsCommons());
        self::assertSame(88.0, round($full->completionShare()));
    }

    /**
     * L'attente se lit en minutes, arrondies — l'unite de la table du canon.
     */
    public function testTheWaitIsReadInMinutes(): void
    {
        $outcome = $this->outcome(commonsBudgeted: 14, cleared: 14, deaths: 0, restSeconds: 9_870);

        self::assertSame(165, $outcome->restMinutes());
    }

    private function outcome(int $commonsBudgeted, int $cleared, int $deaths, int $restSeconds = 0): DayOutcome
    {
        return new DayOutcome(
            buildLabel: 'Banc d\'essai',
            tier: 1,
            encountersBudgeted: 16,
            commonsBudgeted: $commonsBudgeted,
            encountersCleared: $cleared,
            deaths: $deaths,
            lifeLost: 0,
            resourceSpent: 0,
            restSeconds: $restSeconds,
        );
    }
}
