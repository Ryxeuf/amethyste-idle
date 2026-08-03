<?php

namespace App\Enum;

/**
 * La fonction d'un domaine de combat — le troisieme axe (ARC-01).
 *
 * GAME_ARCHETYPES § 1 : un domaine etait une case **element x registre**, et
 * trois arbres d'eau x sorts occupaient la meme case sans que rien, dans le
 * modele, ne dise en quoi ils different. La fonction dit **ce que l'arbre fait
 * au combat**, donc quels leviers il a le droit d'acheter (§ 5) et quelles
 * intentions ses accords peuvent porter (§ 5.1).
 *
 * > Hydromancien (controle), Guerisseur (entretien) et Maremancien (assaut) ne
 * > se ressemblent plus des lors que leurs palettes sont disjointes. Sans ce
 * > troisieme axe, la seule differenciation possible est le chiffre — et deux
 * > arbres qui ne different que par un chiffre sont un seul arbre mal range.
 *
 * **Ce que la fonction n'est pas** : une classe. Elle ne ferme rien, ne
 * s'affiche nulle part comme un titre, et un joueur mene autant d'arbres qu'il
 * veut (doctrine des trois couches, GAME_DOMAINS § 1). C'est une **contrainte
 * d'auteur**, lisible par le joueur seulement dans ce que l'arbre lui donne —
 * un test verifie qu'aucun gabarit ne l'affiche.
 *
 * Les palettes ne sont pas ici : elles vivent dans
 * `config/game/domain_roles.yaml`, parce qu'un plafond ou un levier se
 * recalibre sans toucher au code.
 */
enum DomainRole: string
{
    /** *Je finis le combat avant qu'il ne devienne un probleme.* */
    case Assault = 'assault';

    /** *Je decide de qui joue, et quand.* */
    case Control = 'control';

    /** *Je ne perds pas le combat que les autres perdent au tour 8.* */
    case Upkeep = 'upkeep';

    /** *Rien ne me casse, rien ne me rate.* */
    case Bulwark = 'bulwark';

    /**
     * Le libelle d'auteur, pour les outils et les messages de test.
     *
     * Ce n'est **pas** un libelle joueur : la fonction ne s'affiche pas en jeu.
     * Il n'a donc pas de cle de traduction, et c'est deliberé — une cle
     * existante finirait par etre appelee par un gabarit.
     */
    public function label(): string
    {
        return match ($this) {
            self::Assault => 'Assaut',
            self::Control => 'Controle',
            self::Upkeep => 'Entretien',
            self::Bulwark => 'Encaisse',
        };
    }
}
