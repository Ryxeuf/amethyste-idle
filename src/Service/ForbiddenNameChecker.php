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

    public function isForbidden(string $name): bool
    {
        $normalized = $this->normalize($name);

        foreach (self::RESERVED_NAMES as $reserved) {
            if ($normalized === $reserved) {
                return true;
            }
        }

        foreach (self::FORBIDDEN_PATTERNS as $pattern) {
            if (str_contains($normalized, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $name): string
    {
        $name = mb_strtolower(trim($name));
        // Remove spaces and hyphens for pattern matching (e.g. "f u c k" → "fuck")
        $name = str_replace([' ', '-'], '', $name);
        // Normalize common leet-speak substitutions
        $name = strtr($name, [
            '0' => 'o',
            '1' => 'i',
            '3' => 'e',
            '4' => 'a',
            '5' => 's',
            '7' => 't',
            '8' => 'b',
            '@' => 'a',
            '$' => 's',
        ]);

        return $name;
    }
}
