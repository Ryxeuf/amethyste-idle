<?php

namespace App\Enum;

/**
 * Les gestes qu'une quete peut constater (ONB-12a).
 *
 * GAME_ONBOARDING § 5.2 decrit l'acte I comme trois tours d'une meme boucle :
 * **parchemin → arbre → geste**. Les objectifs existants savent compter des
 * monstres, des objets, des zones et des PNJ ; ils ne savent rien dire du
 * troisieme temps. Un geste est un **acte ponctuel dont la preuve est l'acte
 * lui-meme** : on ne relit pas un etat pour deviner qu'il a eu lieu, on
 * l'observe au moment ou il se produit.
 *
 * C'est aussi ce qui les distingue des objectifs existants et justifie une
 * famille unique plutot que quatre : aucun ne porte de cible structuree (ni
 * `pnj_id`, ni `zone_slug`, ni conditions), tous se satisfont d'un nom de geste
 * et, au plus, d'une cible libre.
 *
 * **L'enumeration est le contrat.** Un geste ecrit a la main dans une fixture
 * mais qu'aucun appelant n'emet produit une quete qu'on ne peut pas terminer —
 * et, dans une chaine d'introduction, un joueur bloque des sa deuxieme heure.
 * `QuestGestureContractTest` verifie donc les deux sens : toute valeur declaree
 * existe ici, et tout cas declare ici est reellement emis quelque part.
 */
enum QuestGesture: string
{
    /**
     * Porter une piece — l'arme du tour 1.
     *
     * Prouve toute la boucle d'un coup : sans parchemin l'arbre reste ferme,
     * sans le nœud de port l'objet refuse de s'equiper (ONB-20b). Constater le
     * port, c'est constater les trois temps.
     */
    case EquipItem = 'equip_item';

    /**
     * Sertir une materia — l'accord du tour 2.
     *
     * Meme raisonnement : le sertissage exige le nœud d'accord, donc l'observer
     * suffit a savoir que l'arbre a ete travaille.
     */
    case SocketMateria = 'socket_materia';

    /**
     * Lancer un sort — le tour 2 mene a son terme.
     *
     * Compte au **lancer**, pas au coup au but : rater n'est pas ne pas avoir
     * appris, et une chaine d'introduction qui punit un jet de des enseigne la
     * mauvaise lecon.
     */
    case CastSpell = 'cast_spell';

    /**
     * Recolter — le tour 3, quel que soit le metier.
     *
     * L'etape 7 de l'acte I ne peut pas nommer ce qu'elle attend : le metier
     * vient d'etre choisi parmi cinq a l'etape 6. Un objectif `collect` designe
     * un objet, donc il choisirait a la place du joueur. Le geste, lui, se
     * contente de constater qu'on a recolte — et la cible, quand elle est
     * declaree, nomme le **metier**, jamais l'objet.
     */
    case Gather = 'gather';

    /**
     * Fabriquer — l'etablis, ou le premier geste qui ne coute pas d'energie.
     *
     * Meme raison que `Gather` : l'etape 8 fabrique « avec ce qu'on a
     * recolte », et ce qu'on a recolte depend du metier choisi deux etapes plus
     * tot. Nommer une recette reviendrait a choisir le metier a la place du
     * joueur.
     */
    case CraftItem = 'craft_item';

    /**
     * Voyager — la premiere attente reelle du jeu.
     *
     * L'etape 9 propose **trois destinations, aucune imposee**. Un objectif
     * d'exploration nomme une zone et les additionne ; le geste, lui, constate
     * qu'on est parti — ce qui est exactement la lecon.
     */
    case Travel = 'travel';

    /**
     * Lancer une expedition — quitter le jeu en le laissant travailler.
     */
    case StartExpedition = 'start_expedition';

    /**
     * Libelle par defaut d'un objectif de ce geste.
     *
     * Une fixture peut toujours ecrire le sien ; celui-ci evite qu'un objectif
     * s'affiche vide quand elle ne le fait pas.
     */
    public function labelKey(): string
    {
        return 'game.quest.gesture.' . $this->value;
    }
}
