<?php

namespace App\GameEngine\Retention;

/**
 * La semaine, et il n'y en a qu'une (RET-07).
 *
 * Cinq briques hebdomadaires cohabitent — defi de guilde (RET-01), commission
 * personnelle (RET-02), chantier de foyer (RET-05), affleurement (RET-06),
 * assiduite (RET-04) — et chacune a besoin de savoir « quelle semaine on est ».
 * Tant que ce calcul vit a plusieurs endroits, rien n'empeche deux briques de
 * ne pas parler de la meme semaine : c'est le risque nomme par le plan, *cinq
 * mecaniques hebdomadaires = cinq horloges qui derivent*.
 *
 * Il vivait effectivement a **deux** endroits avant ce jalon : la formule
 * complete etait recopiee dans `WeeklyChallengeRotator`, en plus de l'helper
 * partage par les quatre autres. Les deux s'accordaient — rien ne garantissait
 * qu'elles continueraient.
 *
 * `RetentionPlanContractTest` verrouille l'unicite : le format de semaine ISO
 * ne doit apparaitre que **dans ce fichier**.
 *
 * Convention : `o-\WW`, ancre sur le **lundi** de la semaine. C'est ce lundi
 * qui est la bascule de tout l'horizon hebdomadaire, et l'ancrage explicite
 * evite de dependre du jour ou tombe l'appel.
 */
final class WeekKey
{
    /**
     * Clef de la semaine ISO contenant l'instant donne.
     */
    public static function of(\DateTimeImmutable $now): string
    {
        return self::mondayOf($now)->format('o-\WW');
    }

    /**
     * Lundi 00h00 de la semaine contenant l'instant donne.
     *
     * Rendue publique parce que la rotation des defis a besoin des **bornes**
     * de la semaine et non seulement de son nom ; les recalculer sur place
     * reintroduirait exactement la duplication que ce fichier supprime.
     */
    public static function mondayOf(\DateTimeImmutable $now): \DateTimeImmutable
    {
        return $now->modify('monday this week')->setTime(0, 0, 0);
    }

    /**
     * Lundi 00h00 de la semaine **nommee par une clef deja ecrite**.
     *
     * Le chemin inverse de `of()`, et il vit ici pour la meme raison : une clef
     * stockee (celle de la derniere visite du hub, RET-09) doit pouvoir se
     * relire en dates sans que le format se recopie ailleurs. Sans cette
     * methode, le premier appelant qui a besoin des bornes d'une semaine
     * passee reintroduit la formule — et deux formules finissent par ne plus
     * dire la meme semaine.
     *
     * Rend `null` sur une clef illisible plutot que de lever : une colonne de
     * base vieille d'un an ne doit pas faire tomber un ecran de jeu.
     */
    public static function mondayOfKey(string $weekKey): ?\DateTimeImmutable
    {
        if (1 !== preg_match('/^(\d{4})-W(\d{2})$/', $weekKey, $matches)) {
            return null;
        }

        return (new \DateTimeImmutable())
            ->setISODate((int) $matches[1], (int) $matches[2])
            ->setTime(0, 0, 0);
    }
}
