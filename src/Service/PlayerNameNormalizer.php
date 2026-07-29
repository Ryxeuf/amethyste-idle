<?php

namespace App\Service;

/**
 * ONB-06 — la forme sous laquelle deux noms sont « le meme nom ».
 *
 * `Player::name` portait une contrainte d'unicite, mais PostgreSQL compare des
 * octets : « Claire » et « claire » etaient deux personnages, et « Clairе »
 * ecrit avec un « е » cyrillique en etait un troisieme, indiscernable a l'œil.
 * Se faire passer pour quelqu'un ne demandait aucun effort.
 *
 * La normalisation produit une **forme de comparaison**, jamais un nom
 * d'affichage : le joueur garde exactement ce qu'il a tape. Elle est
 * volontairement deterministe et ecrite a la main plutot que confiee a
 * `Transliterator` — la table de translitteration d'ICU change d'une version a
 * l'autre, et un index unique ne peut pas dependre de la version d'une
 * bibliotheque installee sur la machine.
 */
class PlayerNameNormalizer
{
    /**
     * Caracteres qui se lisent comme une lettre latine sans en etre une.
     *
     * Cyrillique et grec d'abord — ce sont les seuls jeux dont les formes
     * coincident assez pour tromper a l'ecran. Les chiffres qui imitent une
     * lettre suivent : `0` pour `o`, `1` pour `l`.
     */
    private const CONFUSABLES = [
        // Cyrillique
        'а' => 'a', 'в' => 'b', 'е' => 'e', 'ѕ' => 's', 'і' => 'i', 'ј' => 'j',
        'к' => 'k', 'м' => 'm', 'н' => 'h', 'о' => 'o', 'р' => 'p', 'с' => 'c',
        'т' => 't', 'у' => 'y', 'х' => 'x', 'г' => 'r', 'ч' => '4', 'ь' => 'b',
        // Grec
        'α' => 'a', 'β' => 'b', 'ε' => 'e', 'ζ' => 'z', 'η' => 'n', 'ι' => 'i',
        'κ' => 'k', 'μ' => 'm', 'ν' => 'v', 'ο' => 'o', 'ρ' => 'p', 'τ' => 't',
        'υ' => 'u', 'χ' => 'x', 'γ' => 'y',
        // Chiffres et symboles qui imitent une lettre
        '0' => 'o', '1' => 'l', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't',
        '@' => 'a', '$' => 's', '!' => 'i', '|' => 'l',
    ];

    /**
     * Lettres accentuees ramenees a leur base.
     *
     * « Ébène » et « Ebene » sont le meme nom pour un lecteur ; ils doivent
     * l'etre pour l'index.
     */
    private const ACCENTS = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y',
        'œ' => 'oe', 'æ' => 'ae', 'ß' => 'ss',
    ];

    /**
     * Forme de comparaison d'un nom de personnage.
     *
     * Les espaces et traits d'union tombent : « Le Fanal », « Le-Fanal » et
     * « LeFanal » ne sont pas trois personnes.
     */
    public function normalize(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = strtr($name, self::ACCENTS);
        $name = strtr($name, self::CONFUSABLES);

        // Tout ce qui n'est ni lettre ni chiffre disparait de la comparaison :
        // c'est la seule facon d'empecher « Cl.aire » de doubler « Claire ».
        return (string) preg_replace('/[^a-z0-9]/u', '', $name);
    }
}
