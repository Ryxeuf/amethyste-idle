<?php

namespace App\Tests\Unit\Helper;

use App\Entity\App\Inventory;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Enum\Purity;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\Helper\InventoryHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * La regle de pile, appliquee la ou elle se joue (ECO-21).
 *
 * Les objets ne s'empilent pas en base — chaque lot est une ligne — si bien que
 * « deux lots ne fusionnent que dans la meme bande » est vrai par construction.
 * Le vrai risque est ailleurs, et il est silencieux : une recette qui demande
 * « 3 minerais de cuivre » les prenait **dans l'ordre du sac**. Un joueur qui
 * gardait un lot parfait pour eveiller une materia le voyait fondre dans la
 * premiere epee venue, sans avertissement et sans recours.
 *
 * La consommation part donc du moins pur. C'est le seul ordre qui ne detruit
 * jamais une valeur que le joueur a mise de cote.
 */
class InventoryHelperPurityOrderTest extends TestCase
{
    public function testTheLeastPureLotIsConsumedFirst(): void
    {
        $bag = $this->bagWith([Purity::Parfait, Purity::Trouble, Purity::Pur, Purity::Clair]);

        $removed = $this->helper($bag)->removeItemBySlug('ore-copper', 2);

        self::assertSame(2, $removed);

        // Le sac garde son ordre d'insertion — seul l'ordre de **consommation**
        // change. Ce qui reste, ce sont donc les deux lots les plus purs, dans
        // l'ordre ou le joueur les avait.
        self::assertSame(
            [Purity::Parfait, Purity::Pur],
            array_values(array_map(
                static fn (PlayerItem $item): ?Purity => $item->getPurity(),
                $bag->getItems()->toArray(),
            )),
        );
    }

    /**
     * Le lot parfait est le dernier a partir, meme quand la recette vide
     * presque tout. C'est la propriete qui protege l'eveil d'une materia.
     */
    public function testAFlawlessLotSurvivesUntilNothingElseIsLeft(): void
    {
        $bag = $this->bagWith([Purity::Parfait, Purity::Trouble, Purity::Clair]);

        $this->helper($bag)->removeItemBySlug('ore-copper', 2);

        $remaining = array_values($bag->getItems()->toArray());
        self::assertCount(1, $remaining);
        self::assertSame(Purity::Parfait, $remaining[0]->getPurity());
    }

    /**
     * Hors perimetre, la fonction ne change rien : les lots sans bande gardent
     * leur ordre d'origine, et une recette d'herbes se comporte comme avant.
     */
    public function testMaterialsWithoutABandKeepTheirOriginalOrder(): void
    {
        $bag = $this->bagWith([null, null, null], 'herb-sage');

        $first = $bag->getItems()->toArray()[0];
        $removed = $this->helper($bag)->removeItemBySlug('herb-sage', 1);

        self::assertSame(1, $removed);
        self::assertNotContains($first, $bag->getItems()->toArray());
    }

    public function testAnotherSlugIsNeverTouched(): void
    {
        $bag = $this->bagWith([Purity::Trouble, Purity::Pur]);
        $this->addTo($bag, 'ore-tin', Purity::Trouble);

        $removed = $this->helper($bag)->removeItemBySlug('ore-copper', 5);

        self::assertSame(2, $removed);

        // `removeElement` laisse des trous de clefs : c'est `array_values` qui
        // rend la liste, pas l'indexation directe.
        $remaining = array_values($bag->getItems()->toArray());
        self::assertCount(1, $remaining);
        self::assertSame('ore-tin', $remaining[0]->getGenericItem()->getSlug());
    }

    /**
     * @param list<?Purity> $bands
     */
    private function bagWith(array $bands, string $slug = 'ore-copper'): Inventory
    {
        $bag = new Inventory();
        foreach ($bands as $band) {
            $this->addTo($bag, $slug, $band);
        }

        return $bag;
    }

    private function addTo(Inventory $bag, string $slug, ?Purity $band): void
    {
        $item = new Item();
        $item->setSlug($slug);

        $playerItem = new PlayerItem();
        $playerItem->setGenericItem($item);
        $playerItem->setPurity($band);

        $bag->addItem($playerItem);
    }

    private function helper(Inventory $bag): InventoryHelper
    {
        $playerHelper = $this->createMock(PlayerHelper::class);
        $playerHelper->method('getBagInventory')->willReturn($bag);

        return new InventoryHelper(
            $this->createMock(EntityManagerInterface::class),
            $playerHelper,
            $this->createMock(PlayerItemGenerator::class),
            new NullLogger(),
        );
    }
}
