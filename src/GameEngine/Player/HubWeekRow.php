<?php

declare(strict_types=1);

namespace App\GameEngine\Player;

/**
 * Une ligne du bloc « La semaine » du tableau de bord (RET-08).
 *
 * **Le hub lit, il ne fait pas** (GAME_DASHBOARD § 3), et ce DTO est la forme
 * qui le garantit. Il ne porte ni action, ni methode HTTP, ni jeton, ni charge
 * utile : il n'y a rien ici avec quoi poster quoi que ce soit. Une ligne dit ce
 * qui en est, et mene a l'ecran ou le geste se fait — pas moyen d'en faire un
 * bouton sans changer le type, ce qui est exactement le but.
 *
 * La regle qui va avec, tenue par le digest et non par le DTO : **chaque ligne
 * dit ce qui reste**, jamais une donnee brute que le joueur devrait convertir.
 */
final readonly class HubWeekRow
{
    /** Rien a signaler : la ligne informe. */
    public const TONE_NEUTRAL = 'neutral';

    /** Quelque chose est a portee cette semaine. */
    public const TONE_GAIN = 'gain';

    /** C'est fait : la ligne le dit et n'en demande pas plus. */
    public const TONE_DONE = 'done';

    /**
     * @param string                    $key         suffixe de cle de traduction sous `game.home.week.`
     * @param string                    $route       route de l'ecran ou le geste se fait — une **destination**, jamais une action
     * @param array<string, int|string> $params      parametres de traduction (`%count%`, `%zone%`…)
     * @param array<string, int|string> $routeParams parametres de la route
     * @param ?int                      $current     avancement, pour la jauge miniature ; null = pas de jauge
     * @param ?int                      $target      cible de la jauge ; null = pas de jauge
     * @param string                    $tone        une des constantes `TONE_*`
     */
    public function __construct(
        public string $key,
        public string $route,
        public array $params = [],
        public array $routeParams = [],
        public ?int $current = null,
        public ?int $target = null,
        public string $tone = self::TONE_NEUTRAL,
    ) {
    }

    /**
     * La ligne porte-t-elle une jauge lisible ?
     *
     * Une cible nulle ou negative n'en est pas une : elle rendrait une barre a
     * l'infini, ou une division par zero au rendu.
     */
    public function hasGauge(): bool
    {
        return null !== $this->current && null !== $this->target && $this->target > 0;
    }

    /**
     * Avancement en pourcentage, borne a 100.
     *
     * Borne, parce qu'une commission livrable peut depasser sa cible — 12
     * truites pour 10 demandees — et qu'une barre a 120 % se lit comme un bug.
     */
    public function percent(): int
    {
        if (!$this->hasGauge()) {
            return 0;
        }

        return min(100, (int) floor(($this->current / $this->target) * 100));
    }
}
