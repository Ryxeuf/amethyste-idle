<?php

namespace App\Enum;

/**
 * Ce qu'un mannequin d'entrainement fait, et ne peut pas faire (ONB-11).
 *
 * Un mannequin n'est **pas un monstre affaibli pour les debutants** : c'est un
 * mannequin, et « tourne sur lui-meme » est ce que fait un mannequin. La
 * distinction n'est pas cosmetique — elle garantit que le monde ne raconte
 * jamais que ses monstres sont inoffensifs, et que le premier vrai monstre
 * gardera tout son mordant.
 *
 * `null` sur `Monster::$trainingMode` designe donc **un vrai monstre**, et c'est
 * la valeur de tout ce qui vit dans le monde.
 */
enum TrainingMode: string
{
    /**
     * Le premier mannequin : il tourne sur lui-meme, et **ne frappe jamais**.
     *
     * Perdre est impossible. C'est ce qui permet d'afficher toute l'interface —
     * l'ordre des tours, les points de vie, la fuite, les encarts de tutoriel —
     * sans qu'un joueur qui lit lentement se fasse tuer pendant qu'il lit.
     */
    case Inert = 'inert';

    /**
     * Le second : il riposte faiblement, et **ne peut pas tuer**.
     *
     * Le joueur doit voir sa barre descendre pour comprendre a quoi servent les
     * soins — mais l'apprentissage ne peut pas se solder par une mort. Les
     * degats sont plafonnes de sorte que la cible ne descende jamais sous
     * 1 point de vie.
     */
    case Capped = 'capped';

    /**
     * Le mannequin peut-il infliger le moindre degat ?
     */
    public function strikes(): bool
    {
        return $this === self::Capped;
    }

    /**
     * Libelle de l'action d'un mannequin qui ne frappe pas.
     */
    public function idleActionKey(): string
    {
        return 'game.fight.training_dummy.spins';
    }
}
