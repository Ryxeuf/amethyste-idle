<?php

namespace App\GameEngine\Progression;

/**
 * Issue d'une tentative d'apprentissage de competence.
 *
 * `acquireSkill()` ne rendait rien : un refus etait indiscernable d'une reussite,
 * et l'ecran annoncait « Compétence acquise avec succès ! » dans les deux cas.
 * Le motif du refus voyage desormais jusqu'a l'appelant.
 */
final class SkillAcquisitionResult
{
    private function __construct(
        public readonly bool $acquired,
        public readonly ?string $refusal,
    ) {
    }

    public static function acquired(): self
    {
        return new self(true, null);
    }

    /**
     * @param string $refusal l'une des constantes `PlayerSkillHelper::REFUSAL_*`
     */
    public static function refused(string $refusal): self
    {
        return new self(false, $refusal);
    }
}
