<?php

namespace App\GameEngine\Fight;

use App\Enum\Element;

/**
 * La marque de chaque element (ARC-13).
 *
 * GAME_ARCHETYPES § 1.1. **Trois pieces deja ecrites du systeme en dependent** :
 * le capstone d'assaut (« contre une cible qui porte votre marque »), le levier
 * `grip` (« les statuts appliques ») et la palette de controle (deux accords
 * d'`entrave`). Sans les marques, aucune des trois n'a d'objet — ce sont trois
 * mecaniques qui pointent vers un vide.
 *
 * > **Une marque, et une seule, par element.** Ce qui distingue une marque d'un
 * > effet de statut ordinaire n'est pas sa mecanique — c'est le fait qu'elle
 * > soit *celle de son element*, donc ce que les passifs d'un arbre savent
 * > reconnaitre.
 *
 * **La mark-ness vit ici, pas dans le type.** Le `type` d'un `StatusEffect` dit
 * ce qu'il **fait** (une brulure inflige des degats par tour, un desequilibre
 * modifie une statistique) ; ce catalogue dit lequel est **la marque de son
 * element**. Les deux questions sont distinctes, et les melanger obligerait la
 * Brulure a cesser d'etre un DOT pour devenir « une marque ».
 *
 * ## La loi de duree, et pourquoi elle existe
 *
 * *(Correction du § 9 quinquies.)* En duel, echanger un de ses tours contre un
 * tour adverse laisse les degats subis **rigoureusement identiques** — 101 dans
 * les quatre cas mesures. Une entrave d'un tour est donc **un nœud mort** : le
 * combat s'allonge exactement de ce qu'on a vole.
 *
 * > **Aucune entrave a un tour.** Une marque dure **au moins deux tours**, ou
 * > elle est portee par un geste de degat — auquel cas le tour n'a pas ete
 * > echange, il a servi deux fois.
 *
 * ## Elle se rafraichit, elle ne se cumule pas
 *
 * Reappliquer sa marque remet la duree a plein ; elle ne s'empile jamais avec
 * elle-meme. Deux marques **differentes** coexistent sans regle speciale — un
 * adversaire pris par un pyromancien et un necromancien porte les deux.
 */
final class ElementalMark
{
    /**
     * La duree minimale d'une marque, en tours de la rencontre.
     *
     * Deux, et le canon le demontre plutot qu'il ne le pose : a un tour,
     * l'arithmetique du duel rend l'entrave nulle.
     */
    public const MIN_DURATION = 2;

    /**
     * La marque de chaque element, par slug de `StatusEffect`.
     *
     * `None` n'y figure pas : ce n'est pas un element mais son absence, et une
     * action sans element ne qualifie aucun passif d'arbre (§ 9 quater — le
     * defaut qui avait eteint l'archer).
     *
     * @var array<string, string>
     */
    public const SLUGS = [
        'fire' => 'burn',
        'water' => 'soaked',
        'air' => 'off-balance',
        'earth' => 'weighed-down',
        'metal' => 'gash',
        'beast' => 'hunted',
        'light' => 'revealed',
        'dark' => 'blinded',
    ];

    /**
     * Le nom d'auteur de chaque marque, tel que le canon les nomme.
     *
     * @var array<string, string>
     */
    public const LABELS = [
        'burn' => 'Brulure',
        'soaked' => 'Trempe',
        'off-balance' => 'Desequilibre',
        'weighed-down' => 'Alourdi',
        'gash' => 'Entaille',
        'hunted' => 'Traque',
        'revealed' => 'Revele',
        'blinded' => 'Aveugle',
    ];

    /**
     * La marque de cet element, ou `null` pour `None`.
     */
    public static function forElement(Element $element): ?string
    {
        return self::SLUGS[$element->value] ?? null;
    }

    /**
     * Ce statut est-il la marque d'un element ?
     */
    public static function isMark(string $slug): bool
    {
        return \in_array($slug, self::SLUGS, true);
    }

    /**
     * L'element dont ce statut est la marque, s'il en est une.
     */
    public static function elementOf(string $slug): ?Element
    {
        $found = array_search($slug, self::SLUGS, true);

        return \is_string($found) ? Element::from($found) : null;
    }

    /**
     * Les huit elements qui portent une marque.
     *
     * @return list<Element>
     */
    public static function markedElements(): array
    {
        return array_values(array_filter(
            Element::cases(),
            static fn (Element $element): bool => null !== self::forElement($element),
        ));
    }

    /**
     * Cette duree tient-elle la loi ?
     *
     * Un geste de degat porte sa marque quelle que soit la duree : le tour n'a
     * pas ete echange, il a servi deux fois.
     */
    public static function durationIsLegal(int $duration, bool $carriedByDamage): bool
    {
        return $carriedByDamage || $duration >= self::MIN_DURATION;
    }
}
