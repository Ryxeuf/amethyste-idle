<?php

namespace App\Tests\Unit\GameEngine\Materia;

use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Enum\Element;
use App\Enum\MonsterRank;
use App\GameEngine\Materia\MateriaLootTable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * MAT-05 — le butin derive (GAME_MATERIA §4.2).
 *
 * La table n'est plus ecrite a la main : un monstre lache des materia de son
 * element, a un palier borne par son palier de monde. m1-m3 en voie normale,
 * m4 en rare (T4 seulement, jamais le tout-venant), m5 jamais.
 */
class MateriaLootTableTest extends TestCase
{
    /** @var array<string, Item> indexe "element:palier" */
    private array $catalog = [];

    private MateriaLootTable $table;

    protected function setUp(): void
    {
        $this->catalog = [];
        foreach (Element::cases() as $element) {
            if ($element === Element::None) {
                continue;
            }
            foreach ([1, 2, 3, 4, 5] as $palier) {
                $item = new Item();
                $item->setSlug(sprintf('m%d-%s-test', $palier, $element->value));
                $this->catalog[$element->value . ':' . $palier] = $item;
            }
        }

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturnCallback(function (array $criteria): array {
            $key = $criteria['element']->value . ':' . $criteria['level'];

            return isset($this->catalog[$key]) ? [$this->catalog[$key]] : [];
        });

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $this->table = new MateriaLootTable($em);
    }

    private function monster(Element $element, int $tier, MonsterRank $rank): Monster
    {
        $monster = new Monster();
        $monster->setElement($element);
        $monster->setTier($tier);
        $monster->setRank($rank);

        return $monster;
    }

    /**
     * Un monstre sans element — les mannequins — ne lache jamais de materia.
     */
    public function testNoneElementDropsNothing(): void
    {
        $monster = $this->monster(Element::None, 1, MonsterRank::Common);

        $this->assertNull($this->table->roll($monster, 1.0, 0));
    }

    /**
     * Le palier est borne par le tier : T1 → m1, T2 → m2, T3 et T4 → m3 en
     * voie normale.
     */
    public function testPalierIsBoundByTier(): void
    {
        foreach ([1 => 1, 2 => 2, 3 => 3, 4 => 3] as $tier => $expected) {
            $materia = $this->table->roll($this->monster(Element::Fire, $tier, MonsterRank::Common), 1.0, 0, 99, 0);

            $this->assertNotNull($materia);
            $this->assertSame(sprintf('m%d-fire-test', $expected), $materia->getSlug(), sprintf('T%d doit rendre m%d.', $tier, $expected));
        }
    }

    /**
     * L'element du butin est celui du monstre — couverture des huit flux.
     */
    public function testDropFollowsTheMonsterElement(): void
    {
        foreach (Element::cases() as $element) {
            if ($element === Element::None) {
                continue;
            }
            $materia = $this->table->roll($this->monster($element, 2, MonsterRank::Common), 1.0, 0, 99, 0);

            $this->assertNotNull($materia);
            $this->assertStringContainsString($element->value, $materia->getSlug());
        }
    }

    /**
     * m4 en rare : seul le T4 hors tout-venant y monte — jamais un commun,
     * jamais un palier inferieur, et m5 jamais.
     */
    public function testRareUpgradeIsReservedToHighTierThreats(): void
    {
        $boss = $this->table->roll($this->monster(Element::Dark, 4, MonsterRank::Boss), 1.0, 0, 0, 0);
        $this->assertSame('m4-dark-test', $boss?->getSlug(), 'Un boss T4 peut monter a m4 sur le jet rare.');

        $common = $this->table->roll($this->monster(Element::Dark, 4, MonsterRank::Common), 1.0, 0, 0, 0);
        $this->assertSame('m3-dark-test', $common?->getSlug(), 'Le tout-venant ne monte jamais a m4.');

        $eliteT3 = $this->table->roll($this->monster(Element::Dark, 3, MonsterRank::Elite), 1.0, 0, 0, 0);
        $this->assertSame('m3-dark-test', $eliteT3?->getSlug(), 'Sous le T4, le jet rare ne fait rien.');
    }

    /**
     * m5 jamais en butin : aucune combinaison tier × rang ne l'atteint —
     * le haut du catalogue passe par les coffres et les donjons (MAT-06).
     */
    public function testM5NeverDrops(): void
    {
        foreach ([0, 1, 2, 3, 4] as $tier) {
            foreach (MonsterRank::cases() as $rank) {
                $materia = $this->table->roll($this->monster(Element::Light, $tier, $rank), 1.0, 0, 0, 0);
                if ($materia === null) {
                    continue;
                }
                $this->assertLessThanOrEqual(4, (int) substr($materia->getSlug(), 1, 1), 'm5 ne tombe jamais en butin.');
            }
        }
    }

    /**
     * La fourchette canonique tient : 4-10 % selon le rang, et le jet au-dela
     * de la chance ne rend rien.
     */
    public function testCanonicalDropWindow(): void
    {
        foreach (MateriaLootTable::DROP_CHANCE as $chance) {
            $this->assertGreaterThanOrEqual(4, $chance);
            $this->assertLessThanOrEqual(10, $chance);
        }

        $monster = $this->monster(Element::Water, 2, MonsterRank::Common);
        $this->assertNull($this->table->roll($monster, 1.0, 4), 'Un jet au-dela de 4 % ne rend rien pour un commun.');
        $this->assertNotNull($this->table->roll($monster, 1.0, 3), 'Un jet sous 4 % rend une materia.');
    }

    /**
     * MAT-06 — le coffre suit la zone : m3 en T1-T2, m4 en T3-T4, jamais m5,
     * et neuf coffres sur dix gardent leurs seuls gils.
     */
    public function testChestPalierFollowsTheZone(): void
    {
        $this->assertSame(3, MateriaLootTable::chestPalier(1));
        $this->assertSame(3, MateriaLootTable::chestPalier(2));
        $this->assertSame(4, MateriaLootTable::chestPalier(3));
        $this->assertSame(4, MateriaLootTable::chestPalier(4));

        $materia = $this->table->chestRoll(2, 0, 0, 0);
        $this->assertNotNull($materia);
        $this->assertStringStartsWith('m3-', $materia->getSlug());

        $this->assertNull($this->table->chestRoll(2, MateriaLootTable::CHEST_MATERIA_CHANCE), 'Au-dela de la chance, le coffre garde ses seuls gils.');
    }

    /**
     * DON-04 — la table de donjon est indexee sur le palier de la zone
     * (GAME_MATERIA §4.3) : T1 → m2, T2 → m3, T3 → m3-m4, T4 → m4-m5. Le
     * donjon T4 reste le seul canal du m5.
     */
    public function testDungeonLootIsIndexedOnTheTier(): void
    {
        $blocked = MateriaLootTable::DUNGEON_UPGRADE_CHANCE;

        $this->assertStringStartsWith('m2-', (string) $this->table->dungeonPick(1, $blocked, 0, 0)?->getSlug());
        $this->assertStringStartsWith('m3-', (string) $this->table->dungeonPick(2, $blocked, 0, 0)?->getSlug());
        $this->assertStringStartsWith('m3-', (string) $this->table->dungeonPick(3, $blocked, 0, 0)?->getSlug());
        $this->assertStringStartsWith('m4-', (string) $this->table->dungeonPick(4, $blocked, 0, 0)?->getSlug());

        // La montee en rare ne vaut qu'aux paliers hauts : T1-T2 ne montent
        // jamais, T3 monte a m4, T4 a m5.
        $this->assertStringStartsWith('m2-', (string) $this->table->dungeonPick(1, 0, 0, 0)?->getSlug());
        $this->assertStringStartsWith('m4-', (string) $this->table->dungeonPick(3, 0, 0, 0)?->getSlug());
        $this->assertStringStartsWith('m5-', (string) $this->table->dungeonPick(4, 0, 0, 0)?->getSlug());
    }

    /**
     * DON-04 — l'apercu et le tirage lisent la meme table : `dungeonPaliers`
     * est la seule verite des paliers servis.
     */
    public function testDungeonPaliersMatchTheCanon(): void
    {
        $this->assertSame([2], MateriaLootTable::dungeonPaliers(1));
        $this->assertSame([3], MateriaLootTable::dungeonPaliers(2));
        $this->assertSame([3, 4], MateriaLootTable::dungeonPaliers(3));
        $this->assertSame([4, 5], MateriaLootTable::dungeonPaliers(4));
    }

    /**
     * Un butin reussi ne s'evapore pas sur un trou de catalogue : si
     * l'element n'a rien au palier vise, on redescend d'un palier.
     */
    public function testFallsBackWhenTheTargetPalierIsEmpty(): void
    {
        unset($this->catalog['fire:3']);

        $materia = $this->table->roll($this->monster(Element::Fire, 3, MonsterRank::Common), 1.0, 0, 99, 0);

        $this->assertSame('m2-fire-test', $materia?->getSlug());
    }
}
