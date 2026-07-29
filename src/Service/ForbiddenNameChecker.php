<?php

namespace App\Service;

class ForbiddenNameChecker
{
    /**
     * Patterns that must not appear anywhere in the normalized name.
     * Covers system impersonation, slurs, and offensive terms (FR + EN).
     */
    private const FORBIDDEN_PATTERNS = [
        // System / impersonation
        'admin', 'moderateur', 'moderator', 'gamemaster', 'systeme', 'system',
        'support', 'staff', 'developer', 'webmaster',
        // French offensive
        'connard', 'connasse', 'salaud', 'salope', 'putain', 'pute', 'merde',
        'enculer', 'encule', 'batard', 'bâtard', 'bordel', 'foutre', 'nique',
        'ntm', 'fdp', 'tg', 'pd',
        // English offensive
        'fuck', 'shit', 'bitch', 'asshole', 'bastard', 'dick', 'cunt',
        'nigger', 'nigga', 'faggot', 'retard', 'whore', 'slut',
        // Discrimination
        'nazi', 'hitler', 'holocaust', 'genocide', 'terroris',
    ];

    /**
     * Exact reserved names (after normalization).
     */
    private const RESERVED_NAMES = [
        'gm', 'mj', 'pnj', 'npc', 'bot', 'test', 'null', 'undefined',
    ];

    /**
     * Substitutions de style « leet », lues avant la normalisation.
     *
     * Elles ne peuvent pas vivre dans `PlayerNameNormalizer` : l'unicite doit
     * trancher une fois pour toutes (`1` y vaut `l`), alors que la detection
     * doit attraper **toutes** les lectures possibles (`1` peut se lire `i`).
     */
    private const LEET = [
        '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's',
        '7' => 't', '8' => 'b', '@' => 'a', '$' => 's',
    ];

    public function __construct(private readonly PlayerNameNormalizer $nameNormalizer)
    {
    }

    public function isForbidden(string $name): bool
    {
        foreach ($this->readings($name) as $reading) {
            foreach (self::RESERVED_NAMES as $reserved) {
                if ($reading === $reserved) {
                    return true;
                }
            }

            foreach (self::FORBIDDEN_PATTERNS as $pattern) {
                if (str_contains($reading, $pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * ONB-06 : le filtre s'applique aux **formes de comparaison**, au pluriel.
     *
     * Avant, il lisait une chaine latine : « аdmin » ecrit avec un « а »
     * cyrillique passait au travers, alors que l'œil y lisait « admin ». La
     * liste interdite n'a pas change ; c'est ce qu'on lui donne a lire.
     *
     * Deux lectures, parce qu'un chiffre est ambigu : la forme d'unicite, et
     * la meme apres substitutions leet. Un nom est refuse si **l'une** des deux
     * touche.
     *
     * @return list<string>
     */
    private function readings(string $name): array
    {
        $direct = $this->nameNormalizer->normalize($name);
        $leet = $this->nameNormalizer->normalize(strtr(mb_strtolower($name), self::LEET));

        return $direct === $leet ? [$direct] : [$direct, $leet];
    }
}
