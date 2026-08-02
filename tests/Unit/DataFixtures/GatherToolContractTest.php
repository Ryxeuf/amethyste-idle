<?php

namespace App\Tests\Unit\DataFixtures;

use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * OBJ-05 — l'outil de recolte, et la garantie qui empeche que ce soit un mur.
 *
 * La recolte exige un outil du bon type (GAME_ITEMS §4.2), et « une recolte
 * n'echoue jamais » ne tient que si trois choses restent d'accord entre elles :
 * chaque profession de filon a un type d'outil connu, chaque type a son outil
 * de palier 1 en fixtures (celui que l'ouverture de l'arbre offre), et chaque
 * type a un emplacement d'equipement. La loi doit tenir des la relecture des
 * fichiers — c'est de la donnee, pas du comportement.
 */
class GatherToolContractTest extends TestCase
{
    private function projectDir(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * @return list<string> les professions declarees par les filons du monde
     */
    private function veinProfessions(): array
    {
        $professions = [];
        foreach (glob($this->projectDir() . '/config/game/zones/*.yaml') ?: [] as $file) {
            $world = Yaml::parseFile($file);
            foreach ($world['zones'] ?? [] as $zone) {
                foreach ($zone['gather'] ?? [] as $vein) {
                    if (isset($vein['profession'])) {
                        $professions[] = (string) $vein['profession'];
                    }
                }
            }
        }

        $this->assertNotEmpty($professions, 'Le contrat ne verifie rien si aucun filon n\'est lu.');

        return array_values(array_unique($professions));
    }

    /**
     * Chaque profession de filon exige un outil **connu** : une profession hors
     * de la table produirait un filon sans exigence — un trou silencieux dans
     * la regle, invisible jusqu'au jour ou quelqu'un s'en sert.
     */
    public function testEveryVeinProfessionHasAToolType(): void
    {
        foreach ($this->veinProfessions() as $profession) {
            $this->assertArrayHasKey(
                $profession,
                Item::GATHER_TOOL_TYPES,
                sprintf('La profession de filon "%s" n\'a pas de type d\'outil dans Item::GATHER_TOOL_TYPES.', $profession),
            );
        }
    }

    /**
     * Chaque type d'outil de recolte a son palier 1 en fixtures : c'est
     * l'exemplaire que l'ouverture de l'arbre offre. Sans lui, la garantie
     * anti-mur promet un objet qui n'existe pas.
     */
    public function testEveryGatherToolTypeHasABronzeTierItem(): void
    {
        $tools = Yaml::parseFile($this->projectDir() . '/fixtures/game/item/tool.yaml')[Item::class] ?? [];
        $this->assertNotEmpty($tools, 'Le contrat ne verifie rien si tool.yaml n\'est pas lu.');
        $bronzeByType = [];
        foreach ($tools as $tool) {
            if (($tool['toolTier'] ?? null) === Item::TOOL_TIER_BRONZE && isset($tool['toolType'])) {
                $bronzeByType[(string) $tool['toolType']] = true;
            }
        }

        foreach (Item::GATHER_TOOL_TYPES as $profession => $toolType) {
            $this->assertArrayHasKey(
                $toolType,
                $bronzeByType,
                sprintf('Le type d\'outil "%s" (profession %s) n\'a aucun exemplaire de palier 1 dans tool.yaml.', $toolType, $profession),
            );
        }
    }

    /**
     * Chaque type d'outil de recolte est equipable : un bit d'emplacement
     * existe. La hache avait un type (DOM-05) mais aucun bit — le bucheronnage
     * n'avait donc pas d'outil possible.
     */
    public function testEveryGatherToolTypeHasAGearSlot(): void
    {
        foreach (Item::GATHER_TOOL_TYPES as $toolType) {
            $this->assertArrayHasKey(
                $toolType,
                PlayerItem::TOOL_TYPE_TO_GEAR,
                sprintf('Le type d\'outil "%s" n\'a pas d\'emplacement d\'equipement (PlayerItem::TOOL_TYPE_TO_GEAR).', $toolType),
            );
        }
    }

    /**
     * Le palier module le rendement, jamais l'acces : le bareme couvre tous
     * les paliers declares, et son palier 1 vaut zero — l'outil offert est la
     * reference, pas un bonus.
     */
    public function testTheTierScaleCoversAllTiersAndStartsAtZero(): void
    {
        foreach ([Item::TOOL_TIER_BRONZE, Item::TOOL_TIER_IRON, Item::TOOL_TIER_STEEL, Item::TOOL_TIER_MITHRIL] as $tier) {
            $this->assertArrayHasKey($tier, Item::TOOL_TIER_YIELD_PERCENT);
        }

        $this->assertSame(0, Item::TOOL_TIER_YIELD_PERCENT[Item::TOOL_TIER_BRONZE], 'Le palier 1 est la reference : bonus nul.');

        $previous = -1;
        foreach (Item::TOOL_TIER_YIELD_PERCENT as $percent) {
            $this->assertGreaterThan($previous, $percent, 'Le bareme doit croitre avec le palier.');
            $previous = $percent;
        }
    }
}
