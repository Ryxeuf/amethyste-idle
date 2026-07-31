<?php

declare(strict_types=1);

namespace App\GameEngine\Player;

/**
 * Une ligne du recap de la semaine close (RET-09).
 *
 * **Le recap constate, il ne renvoie nulle part.** La ligne n'a pas de route,
 * a la difference de `HubWeekRow` : la semaine close n'a pas d'ecran ou aller,
 * et proposer une destination reviendrait a demander une action sur un
 * rendez-vous deja termine. La forme du type l'interdit.
 *
 * **Le recap ne fait jamais reproche**, et c'est la seconde regle tenue ici par
 * la forme : le vocabulaire de ton se limite a *neutre* et *gain*. Il n'y a pas
 * de constante pour le manque, donc pas de moyen d'en teinter une ligne sans
 * ajouter le mot au type — ce qui est exactement la conversation que
 * GAME_DASHBOARD § 4 veut imposer (« sur le ton du constat, jamais du
 * reproche »). L'invariant remonte a RET-04 : on recompense la presence, on ne
 * sanctionne jamais l'absence.
 */
final readonly class HubWeekRecapLine
{
    /** Ce qui s'est passe, dit sans le colorer. */
    public const TONE_NEUTRAL = 'neutral';

    /** Quelque chose s'est depose : la ligne peut s'en rejouir. */
    public const TONE_GAIN = 'gain';

    /**
     * @param string                    $key    suffixe de cle de traduction sous `game.home.week.recap.`
     * @param array<string, int|string> $params parametres de traduction (`%gils%`, `%count%`…)
     * @param string                    $tone   une des deux constantes `TONE_*`
     */
    public function __construct(
        public string $key,
        public array $params = [],
        public string $tone = self::TONE_NEUTRAL,
    ) {
    }
}
