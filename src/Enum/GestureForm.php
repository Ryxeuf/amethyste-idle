<?php

namespace App\Enum;

/**
 * Le vocabulaire ferme des formes de geste (ARC-18, GAME_ARCHETYPES § 13.1).
 *
 * L'intention dit ce qu'un geste **fait** — blesser, soigner, protéger, entraver
 * (ARC-11a). Elle ne dit rien de sa **forme**, et c'est precisement la que vivent
 * les archetypes des autres jeux : *un chasseur et un necromancien ne different
 * pas par leurs statistiques, mais parce qu'un familier joue a leur place*.
 *
 * **Huit formes, et chacune repare un defaut mesure** (§ 13.2). Ce n'est pas une
 * liste d'idees : le canon exige qu'une neuvieme soit *une decision de moteur,
 * instruite, jamais un ajout de fixture* — d'ou l'enum ferme, sur le modele de
 * `CombatLever` (ARC-03a) et d'`AccointanceForm` (ARC-16a).
 *
 * ## Ce que porter une forme veut dire
 *
 * Une forme n'est **pas** un effet de plus : c'est une facon d'occuper un tour
 * que le moteur ne savait pas exprimer. Les huit se livrent **une a la fois**
 * (ARC-18), et chacune est un mecanisme independant — les livrer en bloc
 * reviendrait a poser huit mecaniques dont aucune n'aurait ete jouee.
 *
 * **Une seule est livree a ce jour** (`Riposte`, ARC-18a). Les sept autres sont
 * nommees ici et n'ont **aucun lecteur** : c'est deliberé, et c'est la meme
 * discipline qu'ARC-16a a posee sur les accointances — *une forme inerte n'est
 * pas fausse, mais elle serait un mensonge d'interface si on la laissait
 * s'ecrire sans le savoir*. Un test refuse qu'un geste declare une forme dont
 * le moteur ne sait rien.
 */
enum GestureForm: string
{
    /**
     * *Etre frappe est une action.* Un depot sur soi qui rend des degats a qui
     * vous touche (ARC-18a).
     *
     * Repare : **le tank ne tue pas** — le Mur met 14 tours la ou l'archer en
     * met 6 (§ 9 sexies). La riposte lui donne des degats **sans lui donner de
     * la vitesse**, donc sans effacer son cout structurel.
     */
    case Riposte = 'riposte';

    /** *Un depot offensif qui frappe a votre place quand vous n'etes pas la.* */
    case Familiar = 'familiar';

    /** *Une ressource qui se construit dans la rencontre, et meurt avec elle.* */
    case Charge = 'charge';

    /** *Une part des degats des allies vous revient.* */
    case Transfer = 'transfer';

    /** *Un geste pose depuis l'ecran de zone, applique a la rencontre suivante.* */
    case Opening = 'opening';

    /** *Echanger une ressource contre une autre, a taux defavorable.* */
    case Conversion = 'conversion';

    /** *Un depot sur soi, sans duree, exclusif : en changer coute le tour.* */
    case Stance = 'stance';

    /** *Une file d'effets resolus en tours de rencontre.* */
    case Delayed = 'delayed';

    /**
     * Les formes dont le moteur sait quelque chose.
     *
     * **Rendue plutot que sous-entendue** : c'est elle qui permet de refuser un
     * geste declarant une forme inerte, et elle grandit d'un cran a chaque
     * sous-phase d'ARC-18.
     *
     * *ARC-18b avait livre la posture sans l'y inscrire* — la liste ne coutant
     * rien a oublier tant qu'aucun lecteur ne s'y adosse. C'est ARC-18c qui la
     * rattrape en l'ecrivant, et le test qui l'accompagne la tient desormais
     * **par le comportement du moteur** plutot que par la vigilance : une forme
     * n'y figure que si quelque chose sait la lire.
     *
     * @return list<self>
     */
    public static function implemented(): array
    {
        return [self::Riposte, self::Stance, self::Conversion, self::Transfer, self::Charge, self::Delayed, self::Opening];
    }

    public function isImplemented(): bool
    {
        return \in_array($this, self::implemented(), true);
    }
}
