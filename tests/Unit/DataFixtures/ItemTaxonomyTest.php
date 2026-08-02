<?php

namespace App\Tests\Unit\DataFixtures;

use App\Entity\Game\Item;
use PHPUnit\Framework\TestCase;

/**
 * OBJ-01 — la taxonomie alignee sur les 5 types du code (GAME_ITEMS §2).
 *
 * Le code porte 5 constantes, les donnees en portaient 12 : l'onglet
 * Materiaux, qui filtre sur `resource`, cachait 57 matieres sur 91. La
 * famille fine reste portee par le **prefixe de slug** — deja cle
 * d'`affinities.yaml` et de `purity.yaml` — jamais par un champ ni par un
 * type parallele.
 */
class ItemTaxonomyTest extends TestCase
{
    private const CANONICAL_TYPES = [
        Item::TYPE_STUFF,
        Item::TYPE_GEAR_PIECE,
        Item::TYPE_MATERIA,
        Item::TYPE_RESOURCE,
        Item::TYPE_TOOL,
    ];

    /**
     * Prefixes de slug des familles de matieres (les lignes
     * d'`affinities.yaml`, plus `herb-`, vestige de nommage des premieres
     * plantes). `leather-` n'y est pas tel quel : le prefixe est partage avec
     * le vestiaire (`leather-armor`...), c'est `gear_location` qui tranche.
     */
    private const MATTER_PREFIXES = ['ore-', 'plant-', 'herb-', 'fish-', 'wood-'];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * @return array<string, array{type: string, block: string}> par slug
     */
    private function phpFixtureEntries(): array
    {
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/ItemFixtures.php');
        preg_match_all("/'type' => '([a-z_]+)'/", $source, $types, \PREG_OFFSET_CAPTURE);

        $entries = [];
        foreach ($types[1] as $i => [$type, $offset]) {
            $end = isset($types[1][$i + 1]) ? $types[1][$i + 1][1] : \strlen($source);
            $block = substr($source, $offset, $end - $offset);
            if (preg_match("/'slug' => '([a-z0-9-]+)'/", $block, $slug)) {
                $entries[$slug[1]] = ['type' => $type, 'block' => $block];
            }
        }

        $this->assertNotEmpty($entries, 'Le test ne verifie rien si l\'extraction echoue.');

        return $entries;
    }

    /**
     * Les 5 constantes du code sont les seules valeurs en donnees — dans les
     * fixtures PHP comme dans les YAML d'objets.
     */
    public function testOnlyTheFiveCanonicalTypesExist(): void
    {
        $offenders = [];
        foreach ($this->phpFixtureEntries() as $slug => $entry) {
            if (!\in_array($entry['type'], self::CANONICAL_TYPES, true)) {
                $offenders[] = sprintf('%s (%s)', $slug, $entry['type']);
            }
        }

        foreach (glob($this->root() . '/fixtures/game/item/*.yaml') ?: [] as $file) {
            preg_match_all("/^\\s+type: '?\"?([a-z_]+)'?\"?$/m", (string) file_get_contents($file), $yamlTypes);
            foreach (array_unique($yamlTypes[1]) as $type) {
                if (!\in_array($type, self::CANONICAL_TYPES, true)) {
                    $offenders[] = sprintf('%s (%s)', basename($file), $type);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            sprintf('La taxonomie deborde des 5 constantes du code (OBJ-01) : %s.', implode(', ', $offenders)),
        );
    }

    /**
     * L'onglet Materiaux est complet : toute matiere d'une famille de recolte
     * est un `resource` — un autre type la ferait disparaitre du filtre
     * `isResource()` sans rien casser d'autre, silencieusement.
     */
    public function testEveryMatterFamilyLandsInTheMaterialsTab(): void
    {
        $offenders = [];
        foreach ($this->phpFixtureEntries() as $slug => $entry) {
            $isMatter = false;
            foreach (self::MATTER_PREFIXES as $prefix) {
                if (str_starts_with($slug, $prefix)) {
                    $isMatter = true;
                    break;
                }
            }
            // Le cuir partage son prefixe avec le vestiaire : sans
            // emplacement d'equipement, c'est une matiere.
            if (str_starts_with($slug, 'leather-') && !str_contains($entry['block'], "'gear_location'")) {
                $isMatter = true;
            }

            if ($isMatter && Item::TYPE_RESOURCE !== $entry['type']) {
                $offenders[] = sprintf('%s (%s)', $slug, $entry['type']);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            sprintf('Des matieres echappent a l\'onglet Materiaux (OBJ-01) : %s.', implode(', ', $offenders)),
        );
    }

    /**
     * Un objet de quete est un `stuff` **lie** — la liaison porte la
     * distinction (ECO-01), jamais un type propre.
     */
    public function testQuestItemsAreBoundStuff(): void
    {
        $checked = 0;
        foreach ($this->phpFixtureEntries() as $slug => $entry) {
            if (!str_starts_with($slug, 'quest-')) {
                continue;
            }
            ++$checked;

            $this->assertSame(Item::TYPE_STUFF, $entry['type'], sprintf('L\'objet de quete "%s" porte un type propre — la liaison suffit (OBJ-01).', $slug));
            $this->assertTrue(
                str_contains($entry['block'], "'boundToPlayer'") || str_contains($entry['block'], "'bindType'"),
                sprintf('L\'objet de quete "%s" n\'est pas lie : librement revendable, il cesse d\'etre un objet de quete.', $slug),
            );
        }

        $this->assertGreaterThan(0, $checked, 'Le test ne verifie rien si aucun objet de quete n\'existe.');
    }
}
