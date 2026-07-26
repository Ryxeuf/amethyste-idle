<?php

namespace App\Tests\Unit\GameEngine\Shop;

use App\Entity\App\Player;
use App\Entity\App\PlayerShop;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Shop\ShopSearchService;
use App\Repository\PlayerShopRepository;
use App\Repository\RecipeRepository;
use App\Repository\ShopListingRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShopSearchServiceTest extends TestCase
{
    private ShopListingRepository&MockObject $listingRepository;
    private RecipeRepository&MockObject $recipeRepository;
    private PlayerShopRepository&MockObject $shopRepository;
    private CraftingManager&MockObject $craftingManager;
    private ShopSearchService $service;

    protected function setUp(): void
    {
        $this->listingRepository = $this->createMock(ShopListingRepository::class);
        $this->recipeRepository = $this->createMock(RecipeRepository::class);
        $this->shopRepository = $this->createMock(PlayerShopRepository::class);
        $this->craftingManager = $this->createMock(CraftingManager::class);

        $this->service = new ShopSearchService(
            $this->listingRepository,
            $this->recipeRepository,
            $this->shopRepository,
            $this->craftingManager,
        );
    }

    private function shopOf(Player $owner): PlayerShop
    {
        $shop = new PlayerShop();
        $shop->setOwner($owner);
        $shop->setZone((new Zone())->setSlug('village-de-lumiere')->setName('Village'));
        $shop->setName('La bonne enclume');

        return $shop;
    }

    private function player(int $id): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);
        $player->setName('Artisan ' . $id);

        return $player;
    }

    private function recipe(string $craft, int $requiredLevel): Recipe
    {
        $recipe = new Recipe();
        $recipe->setName('Epee courte');
        $recipe->setSlug('epee-courte');
        $recipe->setCraft($craft);
        $recipe->setRequiredLevel($requiredLevel);
        $recipe->setResult((new Item())->setName('Epee courte'));

        return $recipe;
    }

    public function testAnEmptyQueryNeverTouchesTheDatabase(): void
    {
        $this->listingRepository->expects($this->never())->method('searchOnSale');
        $this->recipeRepository->expects($this->never())->method('searchByName');

        $this->assertSame([], $this->service->findOnSale('   '));
        $this->assertSame([], $this->service->findCrafters(''));
    }

    public function testOnSaleDelegatesToTheListingRepository(): void
    {
        $listing = $this->createMock(\App\Entity\App\ShopListing::class);
        $this->listingRepository->expects($this->once())
            ->method('searchOnSale')
            ->with('epee', ShopSearchService::RESULT_LIMIT)
            ->willReturn([$listing]);

        $this->assertSame([$listing], $this->service->findOnSale('  epee  '));
    }

    /**
     * Le critere est le **niveau de metier** du tenancier : un artisan sous le
     * niveau requis n'est pas propose, meme s'il tient echoppe.
     */
    public function testOnlyCraftersAtTheRequiredLevelAreProposed(): void
    {
        $capable = $this->shopOf($this->player(1));
        $novice = $this->shopOf($this->player(2));

        $this->recipeRepository->method('searchByName')->willReturn([$this->recipe('forgeron', 5)]);
        $this->shopRepository->method('findBy')->willReturn([$capable, $novice]);

        $this->craftingManager->method('getCraftingLevel')
            ->willReturnCallback(static fn (Player $p) => 1 === $p->getId() ? 7 : 2);

        $matches = $this->service->findCrafters('epee');

        $this->assertCount(1, $matches);
        $this->assertSame($capable, $matches[0]['shop']);
        $this->assertSame(7, $matches[0]['level']);
    }

    /**
     * A commande egale, on s'adresse au plus competent.
     */
    public function testCraftersAreSortedByLevelDescending(): void
    {
        $good = $this->shopOf($this->player(1));
        $better = $this->shopOf($this->player(2));

        $this->recipeRepository->method('searchByName')->willReturn([$this->recipe('forgeron', 1)]);
        $this->shopRepository->method('findBy')->willReturn([$good, $better]);

        $this->craftingManager->method('getCraftingLevel')
            ->willReturnCallback(static fn (Player $p) => 1 === $p->getId() ? 4 : 9);

        $matches = $this->service->findCrafters('epee');

        $this->assertSame([9, 4], array_column($matches, 'level'));
    }

    /**
     * Sans recette correspondante, inutile de lire la liste des echoppes.
     */
    public function testNoRecipeMeansNoShopLookup(): void
    {
        $this->recipeRepository->method('searchByName')->willReturn([]);
        $this->shopRepository->expects($this->never())->method('findBy');

        $this->assertSame([], $this->service->findCrafters('objet inexistant'));
    }
}
