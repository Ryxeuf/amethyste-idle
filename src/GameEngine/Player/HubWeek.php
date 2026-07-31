<?php

declare(strict_types=1);

namespace App\GameEngine\Player;

/**
 * Le bloc « La semaine » du tableau de bord (RET-08, GAME_DASHBOARD § 3).
 *
 * **Un seul panneau, jamais cinq.** La semaine est une strate du hub, pas un
 * centre de commande : cinq systemes hebdomadaires s'y lisent en cinq lignes,
 * et chacune mene a l'ecran ou le geste se fait.
 *
 * Le plafond n'est pas une consigne, c'est une propriete du type : `MAX_ROWS`
 * est applique a la construction. Un sixieme systeme hebdomadaire ne pourra pas
 * s'ajouter en silence — il faudra decider **laquelle des cinq lignes il
 * remplace**, ce qui est precisement la conversation que le cadrage veut
 * imposer.
 *
 * Les lignes absentes ne laissent pas de vide : sans guilde, les deux lignes de
 * guilde n'existent pas — pas d'etat vide qui culpabilise (§ 3).
 *
 * **Le repere de semaine est date, jamais chronometre** (RET-10) : « Semaine du
 * 27 juillet », et a partir du samedi « se referme demain soir ». Le § 3
 * l'exige dans les deux sens — une date situe, un compte a rebours presse, et
 * la semaine n'est pas un timer.
 */
final readonly class HubWeek
{
    /**
     * Cinq lignes, et le cadrage le dit deux fois.
     *
     * Au-dela, le bloc cesse d'etre lisible d'un coup d'œil, ce qui est sa
     * seule raison d'exister.
     */
    public const MAX_ROWS = 5;

    /** @var list<HubWeekRow> */
    public array $rows;

    /**
     * @param list<HubWeekRow>          $rows           dans l'ordre canonique du § 3
     * @param string                    $weekKey        semaine ISO courante (`2026-W31`), pour le repere d'en-tete
     * @param bool                      $closesTomorrow vrai a partir du samedi : « se referme demain soir »
     * @param array<string, int|string> $startParams    jour et mois du lundi (`%day%`, `%month%`) ; vide = en-tete non datee
     */
    public function __construct(
        array $rows,
        public string $weekKey = '',
        public bool $closesTomorrow = false,
        public array $startParams = [],
    ) {
        // Tronque plutot que de lever : un ecran de jeu ne doit pas tomber
        // parce qu'un systeme de plus s'est declare. La ligne perdue est la
        // derniere de l'ordre canonique, et le test du digest verifie qu'on
        // n'en arrive jamais la.
        // `array_slice` sans preservation de cles rend deja une liste : un
        // `array_values` par-dessus serait sans effet, et PHPStan le dit.
        $this->rows = \array_slice($rows, 0, self::MAX_ROWS);
    }

    /**
     * Le bloc a-t-il quelque chose a dire ?
     *
     * Un bloc vide ne se rend pas du tout : c'est la regle de l'etat vide
     * replie (§ 7, dette 2). Un joueur sans guilde, sans commission et sans
     * foyer ne doit pas lire cinq lignes barrees.
     */
    public function isEmpty(): bool
    {
        return [] === $this->rows;
    }
}
