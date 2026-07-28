<?php

namespace App\GameEngine\Settlement;

use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;

/**
 * Les trois calculs qui font monter, descendre et nommer un foyer (FOY-03).
 *
 * Tout est **pur** et statique : pas de base, pas d'horloge, pas d'entite. Le
 * chiffrage de BALANCE § 23.2 a § 23.4 se verifie alors directement, sans monter
 * un monde autour. C'est le meme parti pris que `GatherService::regenerate()`,
 * pour la meme raison — une regle d'equilibrage qu'on ne peut pas eprouver
 * seule finit par n'etre eprouvee par personne.
 */
final class SettlementRankCalculator
{
    /**
     * Rang lu sur la **somme** des quatre indices.
     *
     * @param array<string, int> $thresholds seuils par rang, ordonnes croissants
     */
    public static function rankFor(int $total, array $thresholds): SettlementRank
    {
        $rank = SettlementRank::Ruin;

        foreach (SettlementRank::ordered() as $candidate) {
            if ($candidate === SettlementRank::Ruin) {
                continue;
            }
            $threshold = $thresholds[$candidate->value] ?? null;
            if ($threshold === null || $total < $threshold) {
                break;
            }
            $rank = $candidate;
        }

        return $rank;
    }

    /**
     * Stock restant apres `$days` jours de decroissance.
     *
     * Compose sur le nombre de jours plutot que de s'appliquer une fois : un
     * tick manque ne doit pas offrir une journee de repit. Un serveur eteint
     * une semaine reprend ou il en etait, pas sept jours plus haut.
     *
     * L'arrondi vers le bas est ce qui permet a un foyer d'atteindre reellement
     * zero : avec un arrondi au plus proche, un stock de 1 ne descendrait
     * jamais et chaque zone jamais frequentee garderait une trace indelebile.
     */
    public static function decay(int $stock, float $dailyRate, int $days): int
    {
        if ($stock <= 0 || $days <= 0) {
            return max(0, $stock);
        }

        return (int) floor($stock * (1.0 - $dailyRate) ** $days);
    }

    /**
     * Indice qui **pretend** au type : le plus fourni, s'il depasse le second
     * de la marge exigee.
     *
     * Pretendre n'est pas s'installer — c'est `SettlementTickService` qui exige
     * ensuite que la pretention tienne une maree entiere. Sans cette duree, le
     * type clignoterait au gre des semaines et la ville n'aurait pas d'identite.
     *
     * Deux cas rendent `null`, et ce sont deux facons de n'avoir personne
     * devant : l'egalite parfaite, et l'avance trop courte.
     *
     * @param array<string, int> $sediment les quatre indices, par valeur d'enum
     */
    public static function challenger(array $sediment, float $margin): ?SettlementIndex
    {
        $values = [];
        foreach (SettlementIndex::cases() as $index) {
            $values[$index->value] = $sediment[$index->value] ?? 0;
        }

        arsort($values);
        $ordered = array_keys($values);
        $best = $values[$ordered[0]];
        $second = $values[$ordered[1]];

        if ($best <= 0) {
            return null;
        }

        // Un foyer nourri par un seul indice a un pretendant des le premier
        // grain : il n'y a personne a depasser.
        if ($second <= 0) {
            return SettlementIndex::from($ordered[0]);
        }

        if ($best < $second * (1.0 + $margin)) {
            return null;
        }

        return SettlementIndex::from($ordered[0]);
    }
}
