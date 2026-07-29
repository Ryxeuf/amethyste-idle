<?php

namespace App\Tests\Unit\DataFixtures;

use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * La ligne du bois, la cinquieme recolte (ZON-34).
 *
 * Aucune ressource bois n'existait : la ligne entiere — armes de bois,
 * mobilier — etait **sans matiere**, et `wood-log`, une buche de decor sans
 * filon ni recette, en tenait lieu (GAME_ZONES § 3 bis).
 *
 * Trois lois de zone se rencontrent ici, et ce fichier les verrouille :
 *
 * 1. **Raretes inversees** : le hetre a deux sources et ne doit jamais etre un
 *    goulot ; les trois autres essences sont chacune l'exclusivite d'une zone.
 * 2. **La Crete et le Silence n'ont pas d'arbres.** Le vent et le gel
 *    l'interdisent — la fiction et la loi des biomes concordent, et une
 *    hetraie glissee la un jour de calibrage romprait les deux.
 * 3. **Le savoir est la porte** (ECO-24c) : les trois exclusivites se gagnent
 *    sur un nœud d'arbre, le hetre jamais.
 */
class WoodLineTest extends TestCase
{
    /**
     * Les quatre essences actees, et le palier de chacune.
     *
     * @var array<string, array{0: int, 1: int}> item => [capacite, repousse]
     */
    private const ESSENCES = [
        'wood-beech' => [72, 25200],      // T0 — la matiere de tout le monde
        'wood-whisperoak' => [32, 28800], // T2 — l'arbre de la zone-titre
        'wood-peat' => [24, 36000],       // T3 — noirci par l'eau morte
        'wood-petrified' => [22, 64800],  // T4 — l'age precedent
    ];

    /**
     * Zones ou le bois ne pousse pas, et pourquoi.
     *
     * @var array<string, string>
     */
    private const TREELESS = [
        'crete-de-ventombre' => 'le vent',
        'glacier-du-silence' => 'le gel',
        'pas-de-givre' => 'le gel',
        'mer-de-sel' => 'le sel',
        'village-de-lumiere' => 'la Voute',
        'quartier-des-jardins' => 'la Voute',
        'cite-ensevelie' => 'un donjon, pas une foret',
        'mines-profondes' => 'le fond des galeries',
    ];

    /**
     * @return array<string, list<array<string, mixed>>> zone => filons de bois
     */
    private function woodVeins(): array
    {
        $loader = new ZoneDefinitionLoader(\dirname(__DIR__, 3));

        $veins = [];
        foreach ($loader->loadFile($loader->defaultFile())['zones'] as $zone) {
            foreach ($zone['gather'] ?? [] as $resource) {
                if ('woodcutting' === $resource['profession']) {
                    $veins[$zone['slug']][] = $resource;
                }
            }
        }

        return $veins;
    }

    /**
     * Les quatre essences ont une source.
     *
     * Une essence declaree sans filon serait un item mort — le defaut que la
     * ligne du bois existe justement pour reparer.
     */
    public function testEveryEssenceIsActuallyHarvestableSomewhere(): void
    {
        $sourced = [];
        foreach ($this->woodVeins() as $veins) {
            foreach ($veins as $vein) {
                $sourced[$vein['item']] = true;
            }
        }

        ksort($sourced);

        self::assertSame(array_keys(self::ESSENCES), array_keys($sourced));
    }

    /**
     * Le hetre a **deux** sources ; les trois autres en ont une seule.
     *
     * C'est la loi des raretes inversees : le T0 d'une ligne ne doit jamais
     * etre un goulot, et ce qui est rare doit l'etre **par le lieu**.
     */
    public function testTheCommonEssenceHasTwoSourcesAndTheRestExactlyOne(): void
    {
        $sources = [];
        foreach ($this->woodVeins() as $slug => $veins) {
            foreach ($veins as $vein) {
                $sources[$vein['item']][] = $slug;
            }
        }

        self::assertCount(2, $sources['wood-beech']);
        self::assertCount(1, $sources['wood-whisperoak']);
        self::assertCount(1, $sources['wood-peat']);
        self::assertCount(1, $sources['wood-petrified']);
    }

    /**
     * Chaque essence suit le profil de son palier.
     */
    public function testEveryEssenceFollowsItsTierProfile(): void
    {
        foreach ($this->woodVeins() as $slug => $veins) {
            foreach ($veins as $vein) {
                [$capacity, $respawn] = self::ESSENCES[$vein['item']];

                self::assertSame($capacity, $vein['capacity'], sprintf('%s / %s', $slug, $vein['slug']));
                self::assertSame($respawn, $vein['respawn_seconds'], sprintf('%s / %s', $slug, $vein['slug']));
            }
        }
    }

    /**
     * **Le hetre n'est jamais gate, les trois exclusivites le sont toujours.**
     *
     * Le savoir est la porte (ECO-24c) : gater le T0 fermerait la ligne a qui
     * n'a pas encore d'arbre, et ne pas gater les autres ferait de leur rarete
     * une affaire de trajet plutot que de metier.
     */
    public function testTheCommonEssenceIsOpenAndTheExclusivesAreEarned(): void
    {
        foreach ($this->woodVeins() as $slug => $veins) {
            foreach ($veins as $vein) {
                if ('wood-beech' === $vein['item']) {
                    self::assertArrayNotHasKey('requires_skill', $vein, sprintf('Le hetre est gate en %s.', $slug));

                    continue;
                }

                self::assertArrayHasKey('requires_skill', $vein, sprintf('%s n\'est pas gate en %s.', $vein['item'], $slug));
                self::assertStringStartsWith('lumber-', $vein['requires_skill']);
            }
        }
    }

    /**
     * Les competences citees par les filons existent dans l'arbre du bucheron.
     *
     * Un filon qui cite un slug inexistant se referme **definitivement** : le
     * gate refuse, et aucun nœud n'ouvre jamais la porte.
     */
    public function testEveryGateNamesARealLumberjackSkill(): void
    {
        $skills = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/Game/SkillFixtures.php');

        foreach ($this->woodVeins() as $veins) {
            foreach ($veins as $vein) {
                if (!isset($vein['requires_skill'])) {
                    continue;
                }

                self::assertStringContainsString(
                    sprintf("'slug' => '%s'", $vein['requires_skill']),
                    $skills,
                    sprintf('Le filon "%s" cite une competence qui n\'existe pas.', $vein['slug']),
                );
            }
        }
    }

    /**
     * **La Crete et le Silence n'ont pas d'arbres.**
     *
     * Le vent et le gel l'interdisent : la fiction et la loi des biomes
     * concordent, et le bois reste une affaire de plaines, de sous-bois et de
     * fossiles. Une hetraie glissee la un jour de calibrage romprait les deux.
     */
    public function testNoTreeGrowsWhereTheFictionForbidsIt(): void
    {
        foreach (array_keys($this->woodVeins()) as $slug) {
            self::assertArrayNotHasKey(
                $slug,
                self::TREELESS,
                sprintf('Du bois pousse en %s, ou %s l\'interdit.', $slug, self::TREELESS[$slug] ?? '?'),
            );
        }
    }

    /**
     * Le domaine du bucheron existe, et son arbre est rattache a lui.
     */
    public function testTheLumberjackDomainAndItsTreeExist(): void
    {
        $root = \dirname(__DIR__, 3);

        self::assertStringContainsString(
            "'lumberjack' => ['title' => 'Bûcheron'",
            (string) file_get_contents($root . '/src/DataFixtures/DomainFixtures.php'),
        );

        $skills = (string) file_get_contents($root . '/src/DataFixtures/Game/SkillFixtures.php');
        self::assertStringContainsString('getLumberjackSkills()', $skills);
        self::assertStringContainsString("\$d = 'lumberjack';", $skills);
    }
}
