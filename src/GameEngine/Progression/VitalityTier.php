<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\GameEngine\Balance\VitalityLaw;

/**
 * Le Socle : ce qu'un personnage est devenu capable d'encaisser (ARC-20b).
 *
 * GAME_VITALITY § 3. Le personnage n'a pas de niveau, et **rien ne faisait
 * monter sa barre de vie** : 20 PV de base, plafonnes entre 26 et 40 une fois
 * tout appris, quand une elite de palier 4 frappe 110. Le Socle est ce qui la
 * fait monter — et sa forme entiere decoule d'une seule regle :
 *
 * > ***La progression verticale ne doit jamais etre un choix ; seule la
 * > differenciation l'est.***
 *
 * ## Pourquoi un nœud visible ET une valeur calculee
 *
 * **Les deux, jamais l'un ou l'autre.** Le nœud existe pour etre **vu** — *un
 * palier de vie est un moment de la progression, pas une variable cachee* —,
 * mais sa valeur ne s'ecrit pas : une valeur posee a la main dans 24 arbres
 * diverge au premier ajustement du bestiaire. Elle se lit sur `VitalityLaw`.
 *
 * ## Le maximum, jamais la somme
 *
 * ***C'est la seule forme qui survive a « le savoir n'est jamais borne ».*** Un
 * nœud additif a +100 PV donnerait **+3 200 PV** au joueur qui a mene les
 * 32 arbres — et c'est exactement le defaut de `Skill::life` aujourd'hui, plat
 * et cumulatif, ecrit en dur dans `Player::maxLife` a chaque apprentissage.
 *
 * Le maximum a une consequence qu'il faut dire : **ouvrir un dixieme arbre ne
 * rend pas plus resistant**. C'est voulu — sinon la barre recompenserait le
 * nombre d'arbres ouverts, c'est-a-dire le temps passe, la seule chose que ce
 * jeu a decide de ne jamais recompenser (GAME_PROGRESSION § 5).
 *
 * ## Sa forme : une porte, et gratuite
 *
 * 0 point, 0 point de budget, aucun levier, aucun geste, aucun droit de port.
 * **Gratuit parce qu'il n'est pas une recompense** : le faire payer en points en
 * ferait un peage, et en budget la taxe de PoE — un cout que les 24 arbres
 * paieraient tous, donc qui ne differencie rien.
 */
final class VitalityTier
{
    /**
     * La cle sous laquelle un nœud declare le palier qu'il ouvre.
     *
     * Elle vit dans `actions`, comme `materia.unlock` : *un nœud dit ce qu'il
     * ouvre, il n'ecrit jamais de statistique*.
     */
    public const ACTION_KEY = 'vitality';

    /**
     * Le palier qu'ouvre ce nœud, ou `null` si ce n'en est pas un.
     */
    public static function of(Skill $skill): ?int
    {
        $actions = $skill->getActions();
        $tier = $actions[self::ACTION_KEY]['tier'] ?? null;

        if (!\is_int($tier) || $tier < VitalityLaw::FIRST_TIER || $tier > VitalityLaw::LAST_TIER) {
            return null;
        }

        return $tier;
    }

    public static function isSocle(Skill $skill): bool
    {
        return self::of($skill) !== null;
    }

    /**
     * Le palier de vitalite de ce joueur : **le plus haut appris**, jamais la
     * somme.
     *
     * Le plancher est rendu meme sans aucun arbre : ***on ne peut pas se
     * retrouver sans barre de vie***. C'est le meme principe que l'outil de
     * palier 1 offert avec l'arbre de recolte (OBJ-06) et que le plancher du
     * jour 1 de GAME_MATERIA § 3 — et il est porte ici plutot que par un arbre,
     * parce qu'un personnage qui ne mene que des metiers n'en ouvrira jamais.
     */
    public static function tierOf(Player $player): int
    {
        $tier = VitalityLaw::FIRST_TIER;

        foreach ($player->getSkills() as $skill) {
            $socle = self::of($skill);
            if ($socle !== null && $socle > $tier) {
                $tier = $socle;
            }
        }

        return $tier;
    }

    /**
     * La barre de ce joueur, avant les leviers et l'equipement.
     */
    public static function barOf(Player $player): int
    {
        return VitalityLaw::barFor(self::tierOf($player));
    }
}
