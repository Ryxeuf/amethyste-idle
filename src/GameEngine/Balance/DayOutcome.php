<?php

namespace App\GameEngine\Balance;

/**
 * Ce qu'une journee de jeu a coute a un build (ARC-17c-c).
 *
 * GAME_ARCHETYPES § 6.4 : *un archetype ne se juge pas sur un combat, il se juge
 * sur la journee que la barre d'energie autorise*. `EncounterOutcome` repond pour
 * une rencontre ; celui-ci repond pour l'horizon ou les fonctions se comparent.
 *
 * **La colonne qui compte est la derniere.** Les PV perdus et la ressource
 * depensee ne se comparent pas entre un soldat et un guerisseur — l'un paie en
 * fragilite, l'autre en PM. Converties en **minutes d'attente** (§ 9 septies.2),
 * elles deviennent la meme monnaie, et c'est la seule dans laquelle l'ancre de
 * fonction a un sens.
 */
final readonly class DayOutcome
{
    /**
     * @param int $encountersBudgeted ce que le budget d'energie autorise
     * @param int $commonsBudgeted    la part de ce budget qui est du tout-venant
     * @param int $encountersCleared  ce que le build a reellement conclu
     * @param int $deaths             combien de fois il est tombe
     */
    public function __construct(
        public string $buildLabel,
        public int $tier,
        public int $encountersBudgeted,
        public int $commonsBudgeted,
        public int $encountersCleared,
        public int $deaths,
        public int $lifeLost,
        public int $resourceSpent,
        public int $restSeconds,
    ) {
    }

    /**
     * Ce build a-t-il mene sa journee jusqu'aux tentatives ?
     *
     * **C'est la condition pour que son attente veuille dire quelque chose.**
     * L'ancre de fonction compare ce qu'une journee coute ; une journee arretee
     * a la troisieme rencontre coute peu, et la lire comme une efficacite
     * inverserait exactement ce qu'on mesure — le guerisseur qui tombe au
     * premier combat afficherait la plus courte attente du releve.
     *
     * Les **tentatives** d'elite ne comptent pas dans cette condition, et le mot
     * est celui du canon : elles sont censees pouvoir tuer (§ 9 octies, *une
     * elite tue un joueur seul*). Exiger qu'elles se concluent reviendrait a
     * exclure du releve tous les builds au motif qu'ils obeissent a la regle.
     */
    public function clearedItsCommons(): bool
    {
        return $this->encountersCleared >= $this->commonsBudgeted;
    }

    /**
     * L'attente d'une journee, en minutes — l'unite du canon.
     */
    public function restMinutes(): int
    {
        return (int) round($this->restSeconds / 60);
    }

    /**
     * La part du budget que le build a effectivement jouee.
     *
     * **Un build qui meurt ne joue pas sa journee.** C'est ce que la seule
     * attente ne peut pas dire : un personnage qui tombe a la troisieme
     * rencontre a une attente courte, et il n'a rien fait. Les deux colonnes se
     * lisent ensemble ou pas du tout.
     */
    public function completionShare(): float
    {
        if ($this->encountersBudgeted <= 0) {
            return 0.0;
        }

        return $this->encountersCleared * 100.0 / $this->encountersBudgeted;
    }
}
