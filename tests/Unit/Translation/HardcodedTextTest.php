<?php

namespace App\Tests\Unit\Translation;

use App\Translation\HardcodedTextScanner;
use PHPUnit\Framework\TestCase;

/**
 * Garde-fou du texte code en dur dans les gabarits (tache 135, Sprint 12).
 *
 * L'audit de catalogue (`TranslationCatalogAuditTest`) verifie qu'une cle
 * appelee est definie. Il ne voit pas le defaut inverse, plus courant : du texte
 * ecrit directement dans le gabarit, qui n'appelle **aucune** cle. Le joueur en
 * anglais lit du francais, et rien ne s'en plaint — ni la CI, ni le rendu.
 *
 * Deux regles, de fermete differente :
 *
 * 1. Les **ecrans nes du pivot** sont propres aujourd'hui. Ils doivent le
 *    rester : tolerance zero.
 * 2. Le reste de l'interface porte une dette anterieure de 163 extraits sur 42
 *    gabarits. On la gele plutot que de la laisser grossir : un gabarit absent
 *    du plan de reference doit etre propre, un gabarit present ne doit pas
 *    empirer.
 *
 * Les nombres du plan de reference sont faits pour **baisser**. Chaque lot de
 * traduction livre en retire ; aucun ne doit en ajouter.
 */
class HardcodedTextTest extends TestCase
{
    /**
     * Dette i18n heritee, gelee au 2026-07-26.
     *
     * @var array<string, int>
     */
    private const BASELINE = [
        'templates/components/Counter.html.twig' => 1,
        'templates/components/FightTimeline.html.twig' => 2,
        'templates/demo/index.html.twig' => 1,
        'templates/game/auction/index.html.twig' => 9,
        'templates/game/auction/sell.html.twig' => 3,
        'templates/game/codex/index.html.twig' => 6,
        'templates/game/craft_order/artisans.html.twig' => 5,
        'templates/game/craft_order/index.html.twig' => 3,
        'templates/game/craft_order/mine.html.twig' => 2,
        'templates/game/craft_order/new.html.twig' => 8,
        'templates/game/craft_order/workshop.html.twig' => 4,
        'templates/game/crafting/_enchantment.html.twig' => 2,
        'templates/game/crafting/_experiment.html.twig' => 1,
        'templates/game/crafting/index.html.twig' => 5,
        'templates/game/fight/index.html.twig' => 1,
        'templates/game/game.html.twig' => 7,
        'templates/game/guild/challenges.html.twig' => 2,
        'templates/game/guild/index.html.twig' => 7,
        'templates/game/guild/influence.html.twig' => 5,
        'templates/game/guild/quests.html.twig' => 2,
        'templates/game/guild/ranking.html.twig' => 2,
        'templates/game/guild/upgrades.html.twig' => 4,
        'templates/game/guild/vault.html.twig' => 1,
        'templates/game/housing/index.html.twig' => 16,
        'templates/game/housing/visit.html.twig' => 1,
        'templates/game/inventory/bank/_list.html.twig' => 2,
        'templates/game/inventory/equipment/_materia_track.html.twig' => 1,
        'templates/game/inventory/materia/_list.html.twig' => 2,
        'templates/game/inventory/materials/_list.html.twig' => 2,
        'templates/game/player_shop/index.html.twig' => 3,
        'templates/game/player_shop/search.html.twig' => 5,
        'templates/game/player_shop/visit.html.twig' => 2,
        'templates/game/profile/show.html.twig' => 4,
        'templates/game/quest/_active_quest_card.html.twig' => 3,
        'templates/game/quest/index.html.twig' => 10,
        'templates/game/shop/index.html.twig' => 4,
        'templates/game/skills/index.html.twig' => 7,
        'templates/home/index.html.twig' => 1,
        'templates/maintenance.html.twig' => 2,
        'templates/security/login.html.twig' => 2,
    ];

    private function scanner(): HardcodedTextScanner
    {
        return new HardcodedTextScanner(\dirname(__DIR__, 3));
    }

    /**
     * Les ecrans du pivot n'ont aucun texte code en dur.
     */
    public function testPivotScreensAreFullyTranslated(): void
    {
        $scanner = $this->scanner();

        foreach (HardcodedTextScanner::PIVOT_TEMPLATES as $template) {
            $this->assertSame(
                [],
                $scanner->scanTemplate($template),
                sprintf(
                    'Texte francais code en dur dans %s : cet ecran est ne du pivot, il est integralement traduit, et doit le rester.',
                    $template,
                ),
            );
        }
    }

    /**
     * Aucun gabarit hors plan de reference ne contient de texte code en dur.
     */
    public function testNoNewTemplateIntroducesHardcodedText(): void
    {
        $offenders = [];
        foreach ($this->scanner()->scan() as $template => $hits) {
            if (!isset(self::BASELINE[$template])) {
                $offenders[$template] = $hits[0] ?? '';
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Ces gabarits introduisent du texte francais code en dur. Utiliser une cle de traduction plutot que d\'etendre la dette.',
        );
    }

    /**
     * Aucun gabarit deja endette n'empire.
     */
    public function testExistingDebtDoesNotGrow(): void
    {
        $found = $this->scanner()->scan();

        $grown = [];
        foreach (self::BASELINE as $template => $allowed) {
            $count = \count($found[$template] ?? []);
            if ($count > $allowed) {
                $grown[$template] = sprintf('%d extraits pour %d autorises', $count, $allowed);
            }
        }

        $this->assertSame([], $grown, 'La dette i18n de ces gabarits a augmente.');
    }

    /**
     * Le plan de reference ne cite que des gabarits existants.
     *
     * Sans cela, un gabarit supprime laisse une ligne morte qui autorise
     * silencieusement sa reintroduction sous le meme nom.
     */
    public function testBaselineHasNoStaleEntry(): void
    {
        $missing = array_values(array_filter(
            array_keys(self::BASELINE),
            static fn (string $template): bool => !is_file(\dirname(__DIR__, 3) . '/' . $template),
        ));

        $this->assertSame([], $missing, 'Ces gabarits du plan de reference n\'existent plus.');
    }

    /**
     * Le scan trouve bien quelque chose.
     *
     * Une heuristique cassee rendrait les trois tests precedents verts sur un
     * ensemble vide.
     */
    public function testScannerFindsKnownDebt(): void
    {
        $this->assertGreaterThan(20, \count($this->scanner()->scan()));
    }
}
