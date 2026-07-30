<?php

namespace App\GameEngine\World;

use App\Enum\Element;
use App\Enum\Purity;

/**
 * La loi de nommage du monde (GAME_WORLD § 1, actee le 2026-07-28).
 *
 * « Aucun nom propre — ville, faction, zone, region, PNJ majeur — ne reutilise
 * un nom d'element, d'hybride reserve ni de bande de purete. » La regle existe
 * parce qu'un joueur doit pouvoir entendre « Lumiere » et savoir, sans
 * hesitation, qu'on lui parle d'un flux et pas d'un lieu.
 *
 * Les termes interdits ne sont pas recopies : ils **derivent** des types qui
 * les definissent — `Element` pour les huit flux, `Purity` pour les quatre
 * bandes. Ajouter un flux au jeu etend la loi le jour meme, sans qu'on ait a y
 * penser. Seuls s'ajoutent a la main les deux cas que ces enums ne portent pas :
 * « Ombre », qui n'est le nom canonique de rien (l'element dark se dit
 * **Tenebres**), et les hybrides que le canon nomme deja.
 *
 * Ce que la loi ne vise pas : les **slugs de code**, herites et tolerantes par
 * le canon (`village-de-lumiere`, faction `ombres`), et les **noms communs**
 * — « dans l'ombre des gobelins » n'est pas un nom propre.
 */
final class NamingLaw
{
    /**
     * Termes que les enums ne portent pas mais que le canon bannit.
     *
     * « Ombre » est le mot que la loi remplace par « Tenebres » ; le laisser
     * passer reintroduirait par la fenetre ce que le renommage a sorti.
     */
    public const BANNED_ALIASES = ['Ombre'];

    /**
     * Hybrides reserves nommes par GAME_WORLD § 2.2.
     *
     * Ils vivent aujourd'hui dans `MateriaFusionManager` sous forme de slugs
     * anglais ; le canon, lui, les nomme en francais. Tant que la table de
     * fusion ne porte pas de libelle, cette liste est leur seul nom.
     */
    public const RESERVED_HYBRIDS = ['Blizzard', 'Magma', 'Éclipse'];

    /**
     * Traduction anglaise des huit flux, pour les libelles `_en`.
     *
     * `Element` ne porte que le libelle francais. La correspondance est donc
     * ecrite ici — et `NamingLawTest` verifie qu'elle couvre toutes les cases
     * de l'enum : un flux ajoute sans sa traduction fait rougir la CI.
     */
    public const ELEMENT_TERMS_EN = [
        'fire' => 'Fire',
        'water' => 'Water',
        'earth' => 'Earth',
        'air' => 'Air',
        'light' => 'Light',
        'dark' => 'Dark',
        'metal' => 'Metal',
        'beast' => 'Beast',
    ];

    /**
     * Termes interdits dans un nom propre francais.
     *
     * @return list<string>
     */
    public static function forbiddenTerms(): array
    {
        $terms = [];

        foreach (Element::cases() as $element) {
            if (Element::None === $element) {
                continue;
            }

            $terms[] = $element->label();
        }

        foreach (Purity::cases() as $purity) {
            $terms[] = ucfirst($purity->value);
        }

        return array_values(array_unique(array_merge($terms, self::BANNED_ALIASES, self::RESERVED_HYBRIDS)));
    }

    /**
     * Termes interdits dans un nom propre anglais.
     *
     * @return list<string>
     */
    public static function forbiddenTermsEn(): array
    {
        return array_values(array_unique(array_merge(
            array_values(self::ELEMENT_TERMS_EN),
            ['Shadow'],
            self::RESERVED_HYBRIDS,
        )));
    }

    /**
     * Le premier terme interdit trouve dans un libelle, ou null s'il est conforme.
     *
     * La comparaison porte sur des **mots entiers**, accents et casse mis a
     * plat : « Crete de Ventombre » reste legale (le canon tolere explicitement
     * les mots composes) la ou « Confrerie des Ombres » ne l'est pas. Le
     * pluriel compte comme le singulier — « Terres Sauvages » reutilise bien le
     * nom du flux Terre.
     *
     * @param list<string>|null $terms termes a chercher (defaut : la loi francaise)
     */
    public static function firstForbiddenTerm(string $label, ?array $terms = null): ?string
    {
        $haystack = self::flatten($label);

        foreach ($terms ?? self::forbiddenTerms() as $term) {
            $needle = preg_quote(self::flatten($term), '/');

            if (1 === preg_match('/(?<![a-z0-9])' . $needle . 's?(?![a-z0-9])/u', $haystack)) {
                return $term;
            }
        }

        return null;
    }

    /**
     * Minuscules sans diacritiques, pour comparer « Tenebres » et « Ténèbres ».
     *
     * La table est explicite plutot que confiee a `iconv('ASCII//TRANSLIT')`,
     * dont le resultat depend de la locale du systeme — une loi ne peut pas
     * dependre de la machine qui la lit.
     */
    private static function flatten(string $value): string
    {
        $replacements = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
            'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ÿ' => 'y', 'ñ' => 'n', 'œ' => 'oe', 'æ' => 'ae',
        ];

        return strtr(mb_strtolower($value), $replacements);
    }
}
