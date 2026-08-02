<?php

namespace App\Tests\Unit\GameEngine\World;

use App\Enum\Element;
use App\GameEngine\World\NamingLaw;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Aucun nom propre ne reutilise un nom de flux (ZON-39, GAME_WORLD § 1).
 *
 * La loi de nommage etait ecrite dans un document et **nulle part dans le
 * jeu** : trois libelles joueur portaient encore un nom d'element quinze jours
 * apres avoir ete declares renommes. Ce fichier est ce qui empeche le prochain
 * « Village de Lumiere » d'entrer par une fixture.
 *
 * Le perimetre est celui de la loi, et rien de plus : **zones, cartes, regions,
 * factions** — les noms propres que le joueur lit comme des lieux et des
 * groupes. Les items, sorts et monstres en sont exclus : « Epee de feu » decrit
 * un flux, il ne le confisque pas.
 *
 * Deux garde-fous contre le faux vert. Chaque extraction verifie qu'elle a
 * ramene au moins autant de libelles que la fixture en contient aujourd'hui —
 * une regex devenue caduque fait rougir la CI au lieu de valider le vide. Et
 * `testEveryElementIsCovered` amarre la liste des termes a l'enum `Element` :
 * un flux ajoute au jeu etend la loi sans qu'on ait a la relire.
 */
class NamingLawTest extends TestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 4);
    }

    // =====================================================================
    // Ce que la loi dit d'un libelle
    // =====================================================================

    /**
     * Les mots composes restent legaux — le canon les tolere nommement.
     */
    public function testCompoundWordsAreLegal(): void
    {
        self::assertNull(NamingLaw::firstForbiddenTerm('Crête de Ventombre'));
        self::assertNull(NamingLaw::firstForbiddenTerm('Le Fanal'));
        self::assertNull(NamingLaw::firstForbiddenTerm('Sanctuaire de la Voûte'));
    }

    /**
     * Un mot entier ne passe pas, accentue ou non.
     */
    public function testWholeWordsAreRefused(): void
    {
        self::assertSame('Lumière', NamingLaw::firstForbiddenTerm('Village de Lumière'));
        self::assertSame('Ombre', NamingLaw::firstForbiddenTerm('Confrérie des Ombres'));
        self::assertSame('Ténèbres', NamingLaw::firstForbiddenTerm('Porte des Tenebres'));
        self::assertSame('Terre', NamingLaw::firstForbiddenTerm('Terres Sauvages'));
    }

    /**
     * Les bandes de purete sont des noms reserves au meme titre que les flux.
     */
    public function testPurityBandsAreReserved(): void
    {
        self::assertSame('Parfait', NamingLaw::firstForbiddenTerm('Le Parfait Repos'));
        self::assertSame('Trouble', NamingLaw::firstForbiddenTerm('Baie Trouble'));
    }

    /**
     * La loi anglaise vise les memes flux, avec « Shadow » en plus.
     */
    public function testEnglishLabelsAreCoveredToo(): void
    {
        $en = NamingLaw::forbiddenTermsEn();

        self::assertSame('Light', NamingLaw::firstForbiddenTerm('Village of Light', $en));
        self::assertSame('Shadow', NamingLaw::firstForbiddenTerm('Brotherhood of Shadows', $en));
        self::assertNull(NamingLaw::firstForbiddenTerm('Shadowind Ridge', $en));
        self::assertNull(NamingLaw::firstForbiddenTerm('The Beacon', $en));
    }

    /**
     * Un flux ajoute a l'enum etend la loi, en francais comme en anglais.
     */
    public function testEveryElementIsCovered(): void
    {
        $fr = NamingLaw::forbiddenTerms();

        foreach (Element::cases() as $element) {
            if (Element::None === $element) {
                continue;
            }

            self::assertContains($element->label(), $fr, sprintf('Le flux « %s » doit etre un terme interdit.', $element->label()));
            self::assertArrayHasKey($element->value, NamingLaw::ELEMENT_TERMS_EN, sprintf('Le flux « %s » n\'a pas de traduction anglaise.', $element->value));
        }
    }

    // =====================================================================
    // Ce que la loi dit du jeu livre
    // =====================================================================

    /**
     * Aucune zone declaree ne porte un nom de flux, ni en francais ni en anglais.
     */
    public function testNoZoneLabelUsesAnElementName(): void
    {
        $files = glob($this->root() . '/config/game/zones/*.yaml') ?: [];
        self::assertNotEmpty($files, 'Aucun fichier de zone : la loi ne verifierait rien.');

        $checked = 0;
        $en = NamingLaw::forbiddenTermsEn();

        foreach ($files as $file) {
            $zones = Yaml::parseFile($file)['zones'] ?? [];

            foreach ($zones as $slug => $zone) {
                if (!\is_array($zone)) {
                    continue;
                }

                if (\is_string($zone['name'] ?? null)) {
                    ++$checked;
                    self::assertNull(NamingLaw::firstForbiddenTerm($zone['name']), sprintf('Zone « %s » : le libelle « %s » reutilise un nom de flux.', $slug, $zone['name']));
                }

                if (\is_string($zone['name_en'] ?? null)) {
                    self::assertNull(NamingLaw::firstForbiddenTerm($zone['name_en'], $en), sprintf('Zone « %s » : le libelle anglais « %s » reutilise un nom de flux.', $slug, $zone['name_en']));
                }
            }
        }

        self::assertGreaterThanOrEqual(10, $checked, 'Moins de zones lues que le monde n\'en declare : l\'extraction est cassee.');
    }

    /**
     * Aucune carte ne porte un nom de flux.
     */
    public function testNoMapLabelUsesAnElementName(): void
    {
        $this->assertFixtureLabelsAreLegal('src/DataFixtures/MapFixtures.php', 8);
    }

    /**
     * Aucune region ne porte un nom de flux.
     */
    public function testNoRegionLabelUsesAnElementName(): void
    {
        $this->assertFixtureLabelsAreLegal('src/DataFixtures/RegionFixtures.php', 3);
    }

    /**
     * Aucune faction ne porte un nom de flux.
     */
    public function testNoFactionLabelUsesAnElementName(): void
    {
        $this->assertFixtureLabelsAreLegal('src/DataFixtures/Game/FactionFixtures.php', 5);
    }

    /**
     * Verifie les libelles FR et EN d'une fixture, en refusant l'extraction vide.
     */
    private function assertFixtureLabelsAreLegal(string $relativePath, int $expectedAtLeast): void
    {
        $source = file_get_contents($this->root() . '/' . $relativePath);
        self::assertIsString($source, sprintf('%s est illisible.', $relativePath));

        // Les deux facons dont ces fixtures nomment : un setter (Map, Region) ou
        // une entree de tableau (Faction).
        preg_match_all("/(?:->setName\(|'name' => )'((?:[^'\\\\]|\\\\.)+)'/", $source, $french);
        preg_match_all("/(?:setNameTranslations\(\[|'name_translations' => \[)'en' => '((?:[^'\\\\]|\\\\.)+)'/", $source, $english);

        self::assertGreaterThanOrEqual($expectedAtLeast, \count($french[1]), sprintf('%s : %d libelle(s) extrait(s), au moins %d attendu(s) — l\'extraction est cassee.', $relativePath, \count($french[1]), $expectedAtLeast));

        $en = NamingLaw::forbiddenTermsEn();

        foreach ($french[1] as $label) {
            $label = str_replace("\\'", "'", $label);
            self::assertNull(NamingLaw::firstForbiddenTerm($label), sprintf('%s : « %s » reutilise un nom de flux.', $relativePath, $label));
        }

        foreach ($english[1] as $label) {
            $label = str_replace("\\'", "'", $label);
            self::assertNull(NamingLaw::firstForbiddenTerm($label, $en), sprintf('%s : « %s » reutilise un nom de flux.', $relativePath, $label));
        }
    }
}
