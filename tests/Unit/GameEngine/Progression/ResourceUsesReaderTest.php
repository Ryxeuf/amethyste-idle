<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\App\Pnj;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\GameEngine\Economy\PurityDefinitionLoader;
use App\GameEngine\Economy\PurityPricer;
use App\GameEngine\Progression\ResourceUsesReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * A quoi sert une matiere (ONB-07b, palier 2 du catalogue).
 *
 * Le badge « recettes » s'affichait sans que rien ne suive : le palier se
 * gagnait et ne montrait rien. Ce lecteur construit ce que le badge
 * promettait — et **rien de plus** : il lit des recettes et des marchands
 * qui existent deja, il ne cree aucune donnee et ne change aucun prix. C'est
 * la borne d'une capacite de peuple (A11), verifiee ici sur le seul service
 * que la capacite de l'Humain avance.
 */
class ResourceUsesReaderTest extends TestCase
{
    public function testItFindsEveryRecipeThatConsumesTheMaterial(): void
    {
        $uses = $this->reader()->forItem($this->item('ore-copper', 10));

        self::assertCount(2, $uses['recipes']);
        self::assertSame('Lingot de cuivre', $uses['recipes'][0]['recipe']->getResult()->getName());
        self::assertSame(3, $uses['recipes'][0]['quantity']);
        self::assertSame('Dague de cuivre', $uses['recipes'][1]['recipe']->getResult()->getName());
        self::assertSame(1, $uses['recipes'][1]['quantity']);
    }

    /**
     * Une matiere sans debouche n'est pas une erreur : c'est une reponse, et
     * l'ecran la dit plutot que de laisser un bloc vide.
     */
    public function testAMaterialWithoutOutletAnswersWithAnEmptyList(): void
    {
        $uses = $this->reader()->forItem($this->item('ore-orphan', 5));

        self::assertSame([], $uses['recipes']);
        self::assertSame([], $uses['sellers']);
    }

    /**
     * « Ou en trouver » se lit sur ce qu'un marchand **vend** : aucun PNJ ne
     * declare ce qu'il achete, puisque tous rachetent tout au taux commun.
     */
    public function testItNamesTheMerchantsThatSellTheMaterial(): void
    {
        $uses = $this->reader()->forItem($this->item('ore-copper', 10));

        self::assertCount(1, $uses['sellers']);
        self::assertSame('Forgeron des Mines', $uses['sellers'][0]->getName());
    }

    /**
     * Le rachat affiche est celui du taux commun (30 %), jamais une promesse
     * sur une bande a venir — la matiere n'est pas encore un lot.
     */
    public function testTheBuybackValueIsTheCommonRate(): void
    {
        self::assertSame(3, $this->reader()->forItem($this->item('ore-copper', 10))['buybackValue']);
        // Plancher a 1 : une matiere sans prix ne rend jamais zero.
        self::assertSame(1, $this->reader()->forItem($this->item('ore-cheap', 0))['buybackValue']);
    }

    /**
     * L'invariant de la capacite : le lecteur **lit**. Aucun de ses retours
     * n'est un rendement, un cout, un nombre d'actions ou un prix de vente —
     * la seule valeur monetaire qu'il expose est celle que le guichet affiche
     * deja a tout le monde.
     */
    public function testItOnlyReadsAndNeverProduces(): void
    {
        $item = $this->item('ore-copper', 10);
        $reader = $this->reader();

        $before = $item->getPrice();
        $uses = $reader->forItem($item);

        self::assertSame($before, $item->getPrice(), 'Lire les usages ne change pas la matiere.');
        self::assertSame(['recipes', 'sellers', 'buybackValue'], array_keys($uses));
    }

    private function reader(): ResourceUsesReader
    {
        $recipeRepository = $this->createMock(EntityRepository::class);
        $recipeRepository->method('findAll')->willReturn([
            $this->recipe('Lingot de cuivre', 'forgeron', [['slug' => 'ore-copper', 'quantity' => 3]]),
            $this->recipe('Dague de cuivre', 'forgeron', [
                ['slug' => 'ore-copper'],
                ['slug' => 'wood-oak', 'quantity' => 2],
            ]),
            // Une entree malformee ne doit pas casser l'ecran entier.
            $this->recipe('Recette abimee', 'forgeron', [['quantity' => 2]]),
        ]);

        $pnjRepository = $this->createMock(EntityRepository::class);
        $pnjRepository->method('findAll')->willReturn([
            $this->merchant('Forgeron des Mines', ['ore-copper', 'pickaxe-iron']),
            $this->merchant('Herboriste', ['plant-thyme']),
            $this->merchant('Passant', null),
        ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Recipe::class => $recipeRepository,
                Pnj::class => $pnjRepository,
                default => $this->createMock(EntityRepository::class),
            },
        );

        return new ResourceUsesReader(
            $entityManager,
            new PurityPricer(new PurityDefinitionLoader(\dirname(__DIR__, 4))),
        );
    }

    private function item(string $slug, int $price): Item
    {
        $item = new Item();
        $item->setSlug($slug);
        $item->setName($slug);
        $item->setPrice($price);

        return $item;
    }

    /**
     * @param list<array<string, mixed>> $ingredients
     */
    private function recipe(string $resultName, string $craft, array $ingredients): Recipe
    {
        $result = new Item();
        $result->setSlug(strtolower(str_replace(' ', '-', $resultName)));
        $result->setName($resultName);

        $recipe = new Recipe();
        $recipe->setCraft($craft);
        $recipe->setIngredients($ingredients);
        $recipe->setResult($result);

        return $recipe;
    }

    /**
     * @param list<string>|null $shopItems
     */
    private function merchant(string $name, ?array $shopItems): Pnj
    {
        $pnj = new Pnj();
        $pnj->setName($name);
        $pnj->setShopItems($shopItems);

        return $pnj;
    }
}
