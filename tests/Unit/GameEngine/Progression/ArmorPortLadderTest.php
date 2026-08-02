<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\GameEngine\Progression\EquipmentPortCatalog;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * ONB-20b-b — l'echelle de port des armures et du bouclier.
 *
 * Les armes ont leur echelle depuis ONB-20b-a ; les lignes d'armure (tissu,
 * cuir, plaque) et le bouclier recoivent la leur. Les lois specifiques :
 * l'echelon 1 reste gratuit et l'echelle mord aux paliers 2-3 — **le palier 1
 * des pieces est libre**, le kit de depart se porte sans rien (la doctrine
 * gate l'evolution, jamais l'arrivee) — et chaque piece evoluee d'une ligne
 * exige l'echelon de son palier.
 *
 * Les lois communes (echelon 1 gratuit, jamais un seul arbre, jamais un seul
 * element, aucune statistique sur un echelon, slug retrouvable) sont deja
 * tenues par `EquipmentPortLadderTest`, qui parcourt **toutes** les familles.
 */
class ArmorPortLadderTest extends TestCase
{
    private function projectDir(): string
    {
        return \dirname(__DIR__, 4);
    }

    private function catalog(): EquipmentPortCatalog
    {
        return new EquipmentPortCatalog($this->projectDir());
    }

    /**
     * @return array<string, array<string, mixed>> familles de la ligne armure
     */
    private function armorFamilies(): array
    {
        return array_filter(
            $this->catalog()->families(),
            static fn (array $family): bool => 'armor' === $family['line'],
        );
    }

    /**
     * Les quatre lignes sont declarees : trois profils d'armure
     * (GAME_ARCHETYPES — le cuir evite, la plaque absorbe, le tissu paie en
     * emplacements) et le bouclier.
     */
    public function testTheFourArmorLinesExist(): void
    {
        $keys = array_keys($this->armorFamilies());
        sort($keys);

        self::assertSame(['cloth', 'leather', 'plate', 'shield'], $keys);
    }

    /**
     * Le palier 1 reste libre : aucune piece de depart ni de palier 1 d'une
     * ligne d'armure ne porte de prerequis. Exiger un port sur le kit de
     * depart enfermerait un personnage tout juste cree — exactement le mur
     * qu'ONB-20a a abattu pour les mains nues.
     */
    public function testTierOnePiecesStayFree(): void
    {
        $php = (string) file_get_contents($this->projectDir() . '/src/DataFixtures/ItemFixtures.php');
        foreach (['starter-helmet', 'starter-chest', 'starter-legs', 'starter-boots', 'starter-gloves', 'starter-shield'] as $slug) {
            self::assertSame(1, preg_match(
                sprintf("/'slug' => '%s',(.*?)\n            \],/s", preg_quote($slug, '/')),
                $php,
                $match,
            ), sprintf('La piece %s a disparu.', $slug));
            self::assertStringNotContainsString(
                "'requirements'",
                $match[1],
                sprintf('La piece de depart %s exige un port : le kit de creation devient importable.', $slug),
            );
        }

        $yaml = Yaml::parseFile($this->projectDir() . '/fixtures/game/item/gear_item.yaml')['App\Entity\Game\Item'];
        foreach (['linen_hood (extends item)', 'linen_robe (extends item)', 'linen_gloves (extends item)', 'leather_gloves (extends item)', 'leather_belt (extends item)'] as $reference) {
            self::assertArrayNotHasKey(
                'requirements',
                $yaml[$reference],
                sprintf('%s est une piece de palier 1 : elle reste libre.', $reference),
            );
        }
    }

    /**
     * Chaque piece evoluee d'une ligne exige l'echelon de son palier — sans
     * quoi l'echelle serait declaree mais inerte, le defaut exact que la loi
     * des armes a deja rencontre.
     */
    public function testEveryEvolvedPieceRequiresItsRung(): void
    {
        $yaml = Yaml::parseFile($this->projectDir() . '/fixtures/game/item/gear_item.yaml')['App\Entity\Game\Item'];

        $expected = [
            'cloth_armor_t2' => ['fine_linen_hood', 'fine_linen_robe', 'fine_linen_gloves'],
            'cloth_armor_t3' => ['shadowsilk_hood', 'shadowsilk_robe', 'archivist_mantle', 'archivist_robe'],
            'leather_armor_t2' => ['hardened_vest', 'hardened_boots', 'hardened_gloves', 'hardened_belt', 'hardened_shoulders', 'hardened_pants', 'exotic_leather_vest'],
            'leather_armor_t3' => ['dragon_vest', 'dragon_boots', 'enchanted_vest'],
            'plate_armor_t2' => ['iron_chestplate', 'iron_greaves', 'iron_boots', 'iron_gauntlets', 'iron_pauldrons', 'iron_belt_reinforced', 'iron_chainmail'],
            'plate_armor_t3' => ['mithril_helm', 'mithril_cuirass', 'mithril_greaves', 'mithril_sabatons', 'mithril_gauntlets', 'mithril_pauldrons', 'mithril_girdle', 'steel_chainmail', 'steel_plate', 'heavy_steel_plate'],
        ];

        foreach ($expected as $rung => $references) {
            foreach ($references as $reference) {
                $entry = $yaml[$reference . ' (extends item)'] ?? null;
                self::assertIsArray($entry, sprintf('La piece %s a disparu de gear_item.yaml.', $reference));
                self::assertSame(
                    ['@' . $rung],
                    $entry['requirements'] ?? null,
                    sprintf('La piece %s n\'exige pas son echelon %s.', $reference, $rung),
                );
            }
        }

        $php = (string) file_get_contents($this->projectDir() . '/src/DataFixtures/ItemFixtures.php');
        foreach (['t2-shield' => 'shield_armor_t2', 'iron-shield' => 'shield_armor_t2', 't3-shield' => 'shield_armor_t3', 'golem-heart-shield' => 'shield_armor_t3'] as $slug => $rung) {
            self::assertSame(1, preg_match(
                sprintf("/'slug' => '%s',(.*?)\n            \],/s", preg_quote($slug, '/')),
                $php,
                $match,
            ), sprintf('Le bouclier %s a disparu.', $slug));
            self::assertStringContainsString(
                sprintf("'requirements' => ['%s']", $rung),
                $match[1],
                sprintf('Le bouclier %s n\'exige pas son echelon %s.', $slug, $rung),
            );
        }
    }

    /**
     * Les echelons superieurs d'armure sont chaines et payes aux memes points
     * que ceux des armes : l'echelle est une, quelle que soit la ligne.
     */
    public function testUpperRungsAreGeneratedChainedAndPriced(): void
    {
        $skills = (string) file_get_contents($this->projectDir() . '/src/DataFixtures/Game/SkillFixtures.php');
        self::assertStringContainsString('getArmorPortUpperRungs', $skills, 'Le generateur des echelons d\'armure a disparu.');

        foreach ($this->armorFamilies() as $key => $family) {
            self::assertSame('port_' . $key, $family['rung1']['reference']);
            self::assertStringStartsWith($key === 'shield' ? 'shield_armor' : $key . '_armor', $family['rung2']);
        }
    }
}
