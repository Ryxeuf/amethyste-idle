<?php

namespace App\Tests\Unit\GameEngine\Economy;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Enum\Purity;
use App\GameEngine\Economy\PurityChain;
use App\GameEngine\Economy\PurityScope;
use App\Helper\InventoryHelper;
use PHPUnit\Framework\TestCase;

/**
 * Une chaine ne vaut pas mieux que son maillon le plus trouble (ECO-26).
 *
 * Trois proprietes portent le jalon, et la deuxieme est celle qu'on oublie :
 *
 * 1. **Le maillon faible decide** — un lingot n'est jamais plus pur que le pire
 *    de ses minerais.
 * 2. **Les matieres fongibles ne tirent rien vers le bas.** Une laniere de cuir
 *    n'a pas de bande ; si l'absence comptait comme « trouble », presque toute
 *    recette rendrait du trouble, puisque presque toutes melangent du fongible
 *    et du cristal. C'est la nuance qui separe une regle utile d'une regle qui
 *    detruit la purete au premier craft.
 * 3. **L'apercu nomme le coupable** — « ce lingot sera trouble » est une
 *    sanction, « a cause de ce cuivre-la » est une decision.
 */
class PurityChainTest extends TestCase
{
    /**
     * Le perimetre reel : la ligne du cristal, par prefixe `ore-`.
     */
    private function scope(): PurityScope
    {
        $scope = $this->createMock(PurityScope::class);
        $scope->method('coversSlug')->willReturnCallback(
            static fn (string $slug): bool => str_starts_with($slug, 'ore-'),
        );
        $scope->method('coversItem')->willReturnCallback(
            static fn (?Item $item): bool => $item !== null && str_starts_with($item->getSlug(), 'ore-'),
        );

        return $scope;
    }

    private function chain(): PurityChain
    {
        return new PurityChain($this->scope(), $this->inventoryHelper());
    }

    /**
     * Le vrai helper : la regle de pile (du moins pur au plus pur) fait partie
     * de ce qu'on verifie ici, la mocker reviendrait a ne rien tester.
     */
    private function inventoryHelper(): InventoryHelper
    {
        $reflection = new \ReflectionClass(InventoryHelper::class);

        return $reflection->newInstanceWithoutConstructor();
    }

    private function item(string $slug, string $name = 'Matiere'): Item
    {
        $item = new Item();
        $item->setSlug($slug);
        $item->setName($name);

        return $item;
    }

    private function lot(string $slug, ?Purity $purity, string $name = 'Matiere'): PlayerItem
    {
        $playerItem = new PlayerItem();
        $playerItem->setGenericItem($this->item($slug, $name));
        $playerItem->setPurity($purity);

        return $playerItem;
    }

    // =====================================================================
    // 1. Le maillon faible decide
    // =====================================================================

    public function testTheWeakestBandWins(): void
    {
        $weakest = $this->chain()->weakestOf([
            $this->lot('ore-copper', Purity::Parfait),
            $this->lot('ore-tin', Purity::Trouble),
            $this->lot('ore-copper', Purity::Pur),
        ]);

        self::assertSame(Purity::Trouble, $weakest);
    }

    public function testAUniformBatchKeepsItsBand(): void
    {
        $weakest = $this->chain()->weakestOf([
            $this->lot('ore-copper', Purity::Pur),
            $this->lot('ore-tin', Purity::Pur),
        ]);

        self::assertSame(Purity::Pur, $weakest);
    }

    public function testAnEmptyConsumptionYieldsNoBand(): void
    {
        self::assertNull($this->chain()->weakestOf([]));
    }

    // =====================================================================
    // 2. Les fongibles ne tirent rien vers le bas
    // =====================================================================

    /**
     * La propriete la plus facile a manquer. Si l'absence de bande comptait
     * comme « trouble », presque toute recette rendrait du trouble — la regle
     * detruirait la purete au lieu de la propager.
     */
    public function testFungibleInputsNeverDragTheResultDown(): void
    {
        $weakest = $this->chain()->weakestOf([
            $this->lot('ore-copper', Purity::Pur),
            $this->lot('leather-raw', null),
            $this->lot('plant-mint', null),
        ]);

        self::assertSame(Purity::Pur, $weakest);
    }

    /**
     * Un lot **dans** le perimetre mais sans bande ne compte pas davantage :
     * c'est le cas de tout ce qui a ete recolte avant ECO-22, et rétro-attribuer
     * une purete a ces lots reviendrait a inventer un passe au joueur.
     */
    public function testAnUnbandedCrystalLotIsIgnoredToo(): void
    {
        $weakest = $this->chain()->weakestOf([
            $this->lot('ore-copper', Purity::Clair),
            $this->lot('ore-tin', null),
        ]);

        self::assertSame(Purity::Clair, $weakest);
    }

    public function testACraftWithoutAnyCrystalYieldsNoBand(): void
    {
        $weakest = $this->chain()->weakestOf([
            $this->lot('leather-raw', null),
            $this->lot('plant-mint', null),
        ]);

        self::assertNull($weakest);
    }

    public function testCombineIsNullTolerantInBothDirections(): void
    {
        $chain = $this->chain();

        self::assertSame(Purity::Clair, $chain->combine(null, Purity::Clair));
        self::assertSame(Purity::Clair, $chain->combine(Purity::Clair, null));
        self::assertNull($chain->combine(null, null));
        self::assertSame(Purity::Trouble, $chain->combine(Purity::Parfait, Purity::Trouble));
    }

    // =====================================================================
    // 3. L'apercu nomme le coupable
    // =====================================================================

    public function testThePreviewNamesTheWeakLink(): void
    {
        $player = $this->playerWith([
            $this->lot('ore-copper', Purity::Parfait, 'Minerai de cuivre'),
            $this->lot('ore-copper', Purity::Parfait, 'Minerai de cuivre'),
            $this->lot('ore-tin', Purity::Trouble, 'Minerai d\'etain'),
            $this->lot('ore-tin', Purity::Pur, 'Minerai d\'etain'),
        ]);

        $preview = $this->chain()->preview($player, $this->recipe([
            ['slug' => 'ore-copper', 'quantity' => 2],
            ['slug' => 'ore-tin', 'quantity' => 1],
        ]));

        self::assertNotNull($preview);
        self::assertSame(Purity::Trouble, $preview->band);
        self::assertSame('Minerai d\'etain', $preview->weakLinkName);
    }

    /**
     * L'apercu regarde les lots que le craft prendra **reellement** — les moins
     * purs d'abord (regle de pile, ECO-21) — et non les plus beaux du sac.
     * Regarder les plus beaux promettrait une bande que la fonte ne rendra pas.
     */
    public function testThePreviewLooksAtTheLotsTheCraftWillActuallyTake(): void
    {
        $player = $this->playerWith([
            $this->lot('ore-copper', Purity::Parfait, 'Minerai de cuivre'),
            $this->lot('ore-copper', Purity::Trouble, 'Minerai de cuivre'),
        ]);

        $preview = $this->chain()->preview($player, $this->recipe([
            ['slug' => 'ore-copper', 'quantity' => 1],
        ]));

        self::assertNotNull($preview);
        self::assertSame(Purity::Trouble, $preview->band, 'Le craft prendra le lot le moins pur : l\'apercu doit le dire.');
    }

    public function testNoPreviewWhenNoIngredientCarriesABand(): void
    {
        $player = $this->playerWith([$this->lot('leather-raw', null, 'Cuir brut')]);

        $preview = $this->chain()->preview($player, $this->recipe([
            ['slug' => 'leather-raw', 'quantity' => 1],
        ]));

        self::assertNull($preview);
    }

    // =====================================================================
    // Fixtures
    // =====================================================================

    /**
     * @param list<PlayerItem> $lots
     */
    private function playerWith(array $lots): Player
    {
        $bag = new Inventory();
        $bag->setType(Inventory::TYPE_BAG);
        foreach ($lots as $lot) {
            $bag->addItem($lot);
        }

        $player = new Player();
        $player->addInventory($bag);

        return $player;
    }

    /**
     * @param list<array{slug: string, quantity: int}> $ingredients
     */
    private function recipe(array $ingredients): Recipe
    {
        $recipe = new Recipe();
        $recipe->setIngredients($ingredients);

        return $recipe;
    }
}
