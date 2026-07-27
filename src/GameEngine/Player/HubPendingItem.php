<?php

declare(strict_types=1);

namespace App\GameEngine\Player;

/**
 * Une attente actionnable du tableau de bord.
 *
 * La regle d'admission est etroite, et c'est le point : une ligne n'entre ici
 * que si le joueur peut **agir dessus maintenant**. Une commande de craft qu'un
 * autre artisan doit encore livrer, une vente a l'hotel des ventes dont les Gils
 * sont deja credites, une enchere expiree dont l'objet est deja revenu — rien de
 * tout cela n'est une attente : c'est de l'information, et l'information a son
 * ecran.
 */
final readonly class HubPendingItem
{
    /** Rien a signaler : la ligne informe. */
    public const TONE_NEUTRAL = 'neutral';

    /** Quelque chose est a recuperer. */
    public const TONE_GAIN = 'gain';

    /** Quelque chose se perd tant que le joueur n'agit pas. */
    public const TONE_LOSS = 'loss';

    /**
     * @param string                    $key         suffixe de cle de traduction sous `game.home.pending.`
     * @param string                    $route       route Symfony de l'action
     * @param array<string, int|string> $params      parametres de traduction (`%count%`…)
     * @param array<string, int|string> $routeParams parametres de la route
     * @param string                    $tone        une des constantes `TONE_*`
     */
    public function __construct(
        public string $key,
        public string $route,
        public array $params = [],
        public array $routeParams = [],
        public string $tone = self::TONE_NEUTRAL,
    ) {
    }
}
