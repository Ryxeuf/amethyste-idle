<?php

namespace App\GameEngine\Economy;

use App\Enum\Purity;

/**
 * Ce que la recette rendra, et **qui le decide** (ECO-26).
 *
 * Le nom du maillon faible est la moitie utile de l'apercu : « ce lingot sera
 * trouble » est une sanction, « ce lingot sera trouble a cause de ce cuivre-la »
 * est une decision — celle d'aller en chercher du meilleur.
 */
final readonly class PurityPreview
{
    public function __construct(
        public Purity $band,
        public string $weakLinkName,
    ) {
    }
}
