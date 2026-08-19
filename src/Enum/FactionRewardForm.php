<?php

namespace App\Enum;

/**
 * Ce qu'une recompense de palier a le droit d'etre (FAC-09).
 *
 * GAME_WORLD § 6.4 c : « les bonus de statistiques des paliers deviennent un
 * **patronage** — on porte les couleurs d'une seule faction a la fois. **Tout
 * le reste des recompenses de palier est lateral, jamais vertical** : recettes
 * exclusives, cosmetiques, montures, teintures, titres, acces secrets. Un
 * palier de reputation ouvre des portes ; il n'empile jamais de la puissance. »
 *
 * **Ce que la mesure a trouve, et qui est pire que l'ecart annonce.** Le plan
 * demandait « une revue systematique contre § 6.4 c », comme si quelques
 * recompenses verticales s'etaient glissees parmi des recompenses laterales.
 * Les 12 recompenses livrees se repartissent en **3 remises** (la Guilde des
 * Marchands) et **9 bonus de statistiques** : hors de la seule maison qui soit
 * hors tension, l'echelle **ne contient rien d'autre que des statistiques**. Et
 * comme FAC-01 les a bornees au patron, un Exalte chez les Chevaliers qui porte
 * d'autres couleurs recoit, pour une echelle entiere, **exactement rien**.
 * *Ce n'est pas « le reste est vertical » : il n'y avait pas de reste.*
 *
 * D'ou une liste **fermee**, refusee a la lecture plutot qu'ignoree — la lecon
 * d'ARC-12a : une forme mal orthographiee produirait une recompense
 * silencieusement inerte, et un cadeau muet se lit comme un choix de design.
 *
 * La regle qui compte tient en une ligne : **seul le patronage peut nommer une
 * statistique**. C'est le meme geste qu'ARC-16a sur les accointances — fermer
 * la porte de service par laquelle la puissance entrait hors de tout budget.
 */
enum FactionRewardForm: string
{
    /**
     * La seule forme verticale du jeu, et elle ne parle que pour la faction
     * dont on porte les couleurs (FAC-01). Les paliers ne s'y empilent pas non
     * plus : le plus haut atteint remplace les precedents.
     */
    case Patronage = 'patronage';

    /** Un prix, jamais une statistique. */
    case Discount = 'discount';

    /** Ce qu'on **sait** : cote des marches, fourchette de purete, rumeurs. */
    case Information = 'information';

    /** Ce qu'un PNJ **fait** pour vous : benedictions, escorte, lecture a tarif reduit. */
    case Service = 'service';

    /** Une recette exclusive. */
    case Recipe = 'recipe';

    /** Une apparence : tabard, lunettes d'erudit, familier-lanterne. */
    case Cosmetic = 'cosmetic';

    /** Une monture — utilitaire ou d'apparat. */
    case Mount = 'mount';

    /** Une porte : les cinq quartiers d'Exalte, et les liaisons derobees. */
    case Access = 'access';

    /** Un titre. Une vitrine sociale, et rien de plus. */
    case Title = 'title';

    /**
     * Une recompense qui ouvre une porte plutot que d'empiler de la puissance.
     *
     * Ecrit en negatif du patronage, et pas en enumerant les huit autres : le
     * jour ou une dixieme forme s'ajoute, elle est **laterale par defaut**, ce
     * qui est le bon defaut — l'oubli penche du cote de la doctrine.
     */
    public function isLateral(): bool
    {
        return $this !== self::Patronage;
    }

    /**
     * Cette forme peut-elle nommer une statistique de combat ?
     *
     * Une seule le peut. La question existe pour que l'invariant s'ecrive une
     * fois ici plutot que de se recopier dans chaque lecteur — *une regle
     * recopiee derive de son original en silence* (ARC-08a).
     */
    public function mayNameAStat(): bool
    {
        return $this === self::Patronage;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $form): string => $form->value, self::cases());
    }
}
