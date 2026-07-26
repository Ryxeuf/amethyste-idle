<?php

namespace App\Tests\Unit\GameEngine\Shop;

use App\Entity\App\Player;
use App\Entity\App\PlayerShop;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Shop\ShopSearchService;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShopSearchServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private CraftingManager&MockObject $craftingManager;
    private ShopSearchService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->craftingManager = $this->createMock(CraftingManager::class);

        $this->service = new ShopSearchService($this->entityManager, $this->craftingManager);
    }

    /**
     * Le constructeur de requetes est chaine : chaque appel se renvoie
     * lui-meme, et le dernier maillon rend le resultat voulu.
     *
     * @param list<object> $result
     */
    private function stubQueryBuilder(array $result): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getResult')->willReturn($result);

        $qb = $this->createMock(QueryBuilder::class);
        foreach (['select', 'from', 'join', 'andWhere', 'setParameter', 'orderBy', 'setMaxResults'] as $method) {
            $qb->method($method)->willReturnSelf();
        }
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);
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

    public function testAnEmptyQueryReturnsNothing(): void
    {
        $this->entityManager->expects($this->never())->method('createQueryBuilder');

        $this->assertSame([], $this->service->findOnSale('   '));
        $this->assertSame([], $this->service->findCrafters(''));
    }

    /**
     * Le critere est le **niveau de metier** du tenancier : un artisan sous le
     * niveau requis n'est pas propose, meme s'il tient echoppe.
     */
    public function testOnlyCraftersAtTheRequiredLevelAreProposed(): void
    {
        $capable = $this->shopOf($this->player(1));
        $novice = $this->shopOf($this->player(2));

        $this->stubQueryBuilder([$this->recipe('forgeron', 5)]);

        $shopRepository = $this->createMock(EntityRepository::class);
        $shopRepository->method('findBy')->willReturn([$capable, $novice]);
        $this->entityManager->method('getRepository')->willReturn($shopRepository);

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

        $this->stubQueryBuilder([$this->recipe('forgeron', 1)]);

        $shopRepository = $this->createMock(EntityRepository::class);
        $shopRepository->method('findBy')->willReturn([$good, $better]);
        $this->entityManager->method('getRepository')->willReturn($shopRepository);

        $this->craftingManager->method('getCraftingLevel')
            ->willReturnCallback(static fn (Player $p) => 1 === $p->getId() ? 4 : 9);

        $matches = $this->service->findCrafters('epee');

        $this->assertSame([9, 4], array_column($matches, 'level'));
    }

    public function testNoRecipeMeansNoCrafterLookup(): void
    {
        $this->stubQueryBuilder([]);
        $this->entityManager->expects($this->never())->method('getRepository');

        $this->assertSame([], $this->service->findCrafters('objet inexistant'));
    }
}
