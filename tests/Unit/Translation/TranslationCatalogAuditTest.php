<?php

namespace App\Tests\Unit\Translation;

use App\Translation\TranslationCatalogAudit;
use PHPUnit\Framework\TestCase;

/**
 * Garde-fou de la parite FR/EN (tache 135, Sprint 12).
 *
 * `scripts/audit-translations.php` existait depuis 135-2b, mais rien ne le
 * lancait : il fallait s'en souvenir. Une clef ajoutee en francais et oubliee en
 * anglais passait donc jusqu'en production, ou elle s'affichait telle quelle —
 * `game.inventory.paper_doll_label` au lieu d'un libelle.
 *
 * Ce test le lance a chaque build. C'est la meme lecon que le reste de la
 * campagne : un controle qu'on doit penser a declencher n'est pas un controle.
 */
class TranslationCatalogAuditTest extends TestCase
{
    private function audit(): TranslationCatalogAudit
    {
        return new TranslationCatalogAudit(\dirname(__DIR__, 3));
    }

    /**
     * Les deux catalogues definissent exactement les memes cles.
     */
    public function testCatalogsAreInParity(): void
    {
        $gaps = $this->audit()->parityGaps();

        $this->assertSame(
            [],
            $gaps['fr_only'],
            'Ces cles n\'existent qu\'en francais : un joueur en anglais verra la cle brute.',
        );
        $this->assertSame(
            [],
            $gaps['en_only'],
            'Ces cles n\'existent qu\'en anglais : un joueur en francais verra la cle brute.',
        );
    }

    /**
     * Toute cle citee par un ecran actif est definie quelque part.
     *
     * `templates/old_game/` est exclu : ces vues sont l'heritage d'avant le
     * pivot, plus servies par aucune route.
     */
    public function testEveryUsedKeyIsDefined(): void
    {
        $missing = $this->audit()->missingKeys(activeOnly: true);

        $this->assertSame(
            [],
            array_keys($missing),
            sprintf(
                'Ces cles sont appelees mais definies nulle part — Symfony affichera la cle a la place du texte : %s',
                implode(' ; ', array_map(
                    static fn (string $key, array $files): string => $key . ' (' . ($files[0] ?? '?') . ')',
                    array_keys($missing),
                    array_values($missing),
                )),
            ),
        );
    }

    /**
     * Le scan trouve bien quelque chose.
     *
     * Sans cette borne, une expression rationnelle cassee rendrait les deux
     * tests precedents verts sur un ensemble vide.
     */
    public function testScanFindsUsedKeys(): void
    {
        $this->assertGreaterThan(500, \count($this->audit()->usedKeys()));
        $this->assertGreaterThan(900, \count($this->audit()->keys('fr')));
    }
}
