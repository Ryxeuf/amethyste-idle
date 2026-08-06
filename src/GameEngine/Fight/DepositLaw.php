<?php

namespace App\GameEngine\Fight;

use App\Enum\SpellIntent;
use App\Enum\SpellScope;

/**
 * La loi du depot (ARC-11b).
 *
 * GAME_ARCHETYPES § 7 bis. **Ce jalon decide si le donjon de groupe a un
 * sens.** Le combat de groupe est semi-synchrone — un seul joueur actif a la
 * fois, 45 s par tour, et au-dela **le tour d'un absent est resolu tout
 * seul**. Dans un donjon dont les tours peuvent s'etaler sur des heures, « un
 * allie tombe a 20 %, je le soigne » suppose d'etre la quand ca arrive : c'est
 * une mecanique morte, et avec elle tout l'archetype d'entretien en groupe.
 *
 * > **Un geste qui touche le groupe ne reagit pas : il se depose.** Il pose une
 * > duree sur les allies, et cette duree agit **que son lanceur soit connecte
 * > ou non**. Le soigneur ne soigne pas : il provisionne.
 *
 * **L'extension a toute `protection`, quelle que soit sa portee** (correction
 * du § 9 bis) : une garde qui coute un tour entier pour couvrir *ce* tour
 * punit l'encaisse de se defendre — il perd en degats exactement ce qu'il
 * gagne en survie, et son tour defensif est toujours un mauvais calcul. Une
 * protection pose une absorption **qui dure** : un tour paye, trois tours
 * couverts. Ce qui etait une regle de jeu de groupe devient une regle
 * d'**intention**, et l'encaisse redevient jouable seul.
 *
 * Cette classe ne fait qu'**enoncer la loi**. Ce qui la depose est
 * `StatusEffectManager` ; ce qui la lit en combat est `SpellApplicator`.
 */
final class DepositLaw
{
    /**
     * Un depot court au moins deux tours de rencontre.
     *
     * Meme raison qu'`ElementalMark::MIN_DURATION` (ARC-13a), et c'est
     * arithmetique : un effet qui ne dure que le tour ou il est joue n'a rien
     * depose — il a **reagi**, et c'est precisement ce que le modele
     * semi-synchrone interdit. Un depot d'un tour est un nœud mort quel que
     * soit le chiffre qu'on y mette.
     */
    public const MIN_DURATION = 2;

    /**
     * Ce geste se depose-t-il ?
     *
     * Deux portes, et une seule des deux est une question de portee :
     * **tout ce qui touche le groupe** (la loi), et **toute protection**
     * (son extension). Le reste reste direct — la loi n'interdit **pas** le
     * soin direct (§ 7 bis.2 bis) : *le direct est l'urgence, le depot est la
     * provision*, et un guerisseur solo joue surtout le premier.
     */
    public static function deposits(?SpellIntent $intent, ?SpellScope $scope): bool
    {
        if ($scope === SpellScope::Group) {
            return true;
        }

        return $intent === SpellIntent::Protection;
    }

    /**
     * La duree opposable a un depot : jamais moins de deux tours.
     */
    public static function durationFor(int $declared): int
    {
        return max(self::MIN_DURATION, $declared);
    }

    /**
     * Ce qu'un depot rend **par tour**.
     *
     * > **La duree etale la valeur, elle ne l'augmente pas** (correction du
     * > § 9 ter). La valeur **totale** d'un depot est fixee par le palier de la
     * > materia ; la duree ne decide que de son etalement.
     *
     * Sans cette regle, allonger un depot serait le levier le moins cher du
     * jeu : mesure, un depot de 10 tours sur quatre allies vaut **14,7 tours
     * d'attaque**, et un groupe sans entretien cesse d'etre « plus lent » pour
     * devenir **non viable** — exactement ce que le garde-fou interdit. Une
     * duree longue n'achete pas de la puissance, elle achete de la
     * **robustesse a l'absence**, et c'est deja bien assez.
     *
     * Le reste de la division n'est pas perdu : `spreadPerTurn` arrondit au
     * plus proche, et un total qui ne tombe pas juste rend au plus un point de
     * moins que declare — jamais plus. On ne rend jamais **plus** que le total.
     */
    public static function spreadPerTurn(int $totalValue, int $duration): int
    {
        if ($totalValue <= 0) {
            return 0;
        }

        $duration = max(1, $duration);

        // Un depot rend au moins 1 par tour tant qu'il vaut quelque chose :
        // un depot qui rend zero a chaque tour n'est pas un depot etale, c'est
        // un depot supprime.
        return max(1, (int) floor($totalValue / $duration));
    }

    /**
     * Ce qu'un depot vaut a N allies.
     *
     * > **Ce qui agit sur un etat se multiplie par le nombre d'allies. Ce qui
     * > agit sur une action ne se multiplie pas.**
     *
     * Parce qu'un seul joueur agit par tour. Un soin, une absorption, une
     * resistance touchent **quatre corps** a chaque tour → x4. Une
     * amelioration de degats ne touche que **l'action du tour** → x1, qu'on
     * soit un ou quatre.
     *
     * **C'est structurel, et il ne faut pas le corriger** : l'entretien et
     * l'encaisse gagnent mecaniquement au groupe, l'assaut et le controle n'y
     * gagnent rien (mesure au § 9 quinquies : x8,8 a quatre sur les allies,
     * x0,9 sur l'ennemi). Nier cette asymetrie reviendrait a equilibrer le
     * controle comme un soutien qu'il n'est pas — et un archetype de « barde »
     * n'est pas, dans ce modele, un archetype de groupe.
     */
    public static function multipliesWithAllies(?SpellIntent $intent): bool
    {
        return match ($intent) {
            // L'etat d'un corps : autant de fois que de corps.
            SpellIntent::Heal, SpellIntent::Protection => true,
            // L'action du tour : une seule est jouee, quel que soit le groupe.
            SpellIntent::Buff, SpellIntent::Damage, SpellIntent::Hinder, null => false,
        };
    }
}
