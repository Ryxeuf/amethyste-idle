<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\Game\Spell;
use App\GameEngine\Fight\BareHandsAttack;
use App\GameEngine\Fight\Handler\PlayerAttackHandler;
use App\GameEngine\Item\ItemUsageResolver;
use App\Helper\FightHelper;
use App\Helper\GearHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * ONB-20a — l'attaque de base ne peut plus echouer faute d'arme.
 *
 * `getItem()` levait `EntityNotFoundException('Player attack impossible')` des
 * qu'aucune arme n'etait equipee.
 */
class PlayerAttackHandlerTest extends TestCase
{
    public function testAnUnarmedPlayerFallsBackToBareHands(): void
    {
        $bareHands = $this->createMock(BareHandsAttack::class);
        $bareHands->expects($this->once())->method('strike');

        $handler = $this->handler(weapon: null, bareHands: $bareHands);

        $this->assertTrue($handler->applyAction(new Fight(), new Player()));
    }

    /**
     * Une arme sans sort ne frappe pas : elle vaut une main vide.
     */
    public function testAWeaponWithoutASpellIsWorthAnEmptyHand(): void
    {
        $bareHands = $this->createMock(BareHandsAttack::class);
        $bareHands->expects($this->once())->method('strike');

        $handler = $this->handler(weapon: $this->weapon(spell: null), bareHands: $bareHands);

        $this->assertTrue($handler->applyAction(new Fight(), new Player()));
    }

    /**
     * Sans cible, on ne frappe rien — et surtout on ne leve pas.
     */
    public function testNoTargetMeansNoStrikeAndNoException(): void
    {
        $bareHands = $this->createMock(BareHandsAttack::class);
        $bareHands->expects($this->never())->method('strike');

        $handler = $this->handler(weapon: null, bareHands: $bareHands, withTarget: false);

        $this->assertFalse($handler->applyAction(new Fight(), new Player()));
    }

    private function weapon(?Spell $spell): PlayerItem
    {
        $item = new Item();
        $item->setSpell($spell);

        $playerItem = new PlayerItem();
        $playerItem->setGenericItem($item);

        return $playerItem;
    }

    private function handler(
        ?PlayerItem $weapon,
        BareHandsAttack $bareHands,
        bool $withTarget = true,
    ): PlayerAttackHandler {
        $gearHelper = $this->createMock(GearHelper::class);
        $gearHelper->method('getWeaponGear')->willReturn($weapon);

        $fightHelper = $this->createMock(FightHelper::class);
        $fightHelper->method('getTarget')->willReturn($withTarget ? new Player() : null);

        return new PlayerAttackHandler(
            $gearHelper,
            $this->createMock(EntityManagerInterface::class),
            $fightHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(ItemUsageResolver::class),
            $bareHands,
        );
    }
}
