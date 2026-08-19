<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\BuildConditionEvaluator;
use App\GameEngine\Progression\CombatLeverDefinitionException;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\GameEngine\Progression\SkillCondition;
use App\GameEngine\Progression\SynergyCalculator;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * La condition de build, confrontee a l'equipement reel (ARC-16b).
 *
 * C'etait le blocage nomme d'ARC-16a : `SkillCondition` etait analysee,
 * valorisee et affichee, et personne ne repondait a « ce joueur porte-t-il une
 * dague ? ». La reponse vient de l'**echelle de port** — une piece est de la
 * famille dont elle exige un echelon —, jamais d'une table parallele.
 */
class BuildConditionEvaluatorTest extends TestCase
{
    public function testAFamilyConditionIsSatisfiedByAWornPieceOfThatFamily(): void
    {
        $player = $this->playerWearing([$this->piece('port-dagger', PlayerItem::GEAR_MAIN_WEAPON)]);

        self::assertTrue($this->evaluator()->isSatisfied($player, SkillCondition::parse('weapon:dagger')));
        self::assertFalse($this->evaluator()->isSatisfied($player, SkillCondition::parse('weapon:sword')));
    }

    /**
     * Une piece sans echelon n'a pas de famille : le kit de depart se porte
     * sans rien, et il ne satisfait rien — *une condition recompense un choix
     * de build, pas un kit offert*.
     */
    public function testAPieceWithoutAPortRungHasNoFamily(): void
    {
        $player = $this->playerWearing([$this->piece(null, PlayerItem::GEAR_CHEST)]);

        self::assertFalse($this->evaluator()->isSatisfied($player, SkillCondition::parse('armor:leather')));
    }

    public function testShieldOffhandAndDualWieldReadTheGearSlots(): void
    {
        $swordAndShield = $this->playerWearing([
            $this->piece('port-sword', PlayerItem::GEAR_MAIN_WEAPON),
            $this->piece('port-shield', PlayerItem::GEAR_SIDE_WEAPON),
        ]);
        $twoDaggers = $this->playerWearing([
            $this->piece('port-dagger', PlayerItem::GEAR_MAIN_WEAPON),
            $this->piece('port-dagger', PlayerItem::GEAR_SIDE_WEAPON),
        ]);
        $bareOffhand = $this->playerWearing([$this->piece('port-sword', PlayerItem::GEAR_MAIN_WEAPON)]);

        self::assertTrue($this->evaluator()->isSatisfied($swordAndShield, SkillCondition::parse('shield')));
        self::assertFalse($this->evaluator()->isSatisfied($swordAndShield, SkillCondition::parse('offhand_free')));
        // Un bouclier au bras gauche n'est pas une seconde lame : l'echelle
        // separe les lignes, et `dual_wield` ne compte que les armes.
        self::assertFalse($this->evaluator()->isSatisfied($swordAndShield, SkillCondition::parse('dual_wield')));

        self::assertTrue($this->evaluator()->isSatisfied($twoDaggers, SkillCondition::parse('dual_wield')));
        self::assertTrue($this->evaluator()->isSatisfied($bareOffhand, SkillCondition::parse('offhand_free')));
    }

    /**
     * Le lecteur de la forme `condition_widening` : quand l'accointance est
     * active, ce qui satisfait l'elargissement satisfait la condition.
     */
    public function testAnActiveWideningLetsTheOtherFamilyAnswer(): void
    {
        $player = $this->playerWearing([$this->piece('plate-armor-t2', PlayerItem::GEAR_CHEST)]);

        $widened = $this->evaluator(['armor:leather' => ['armor:plate']]);
        $bare = $this->evaluator();

        self::assertTrue($widened->isSatisfied($player, SkillCondition::parse('armor:leather')));
        self::assertFalse($bare->isSatisfied($player, SkillCondition::parse('armor:leather')));
    }

    /**
     * Une condition de combat ne se remplit pas a l'inventaire — la traiter ici
     * avec un « toujours vrai » silencieux serait le mensonge que la grammaire
     * d'ARC-12a existe pour fermer.
     */
    public function testACombatConditionIsRefusedNotGuessed(): void
    {
        $this->expectException(CombatLeverDefinitionException::class);

        $this->evaluator()->isSatisfied($this->playerWearing([]), SkillCondition::parse('target_marked'));
    }

    /**
     * @param array<string, list<string>> $widenings
     */
    private function evaluator(array $widenings = []): BuildConditionEvaluator
    {
        $synergyCalculator = $this->createMock(SynergyCalculator::class);
        $synergyCalculator->method('conditionWidenings')->willReturn($widenings);

        return new BuildConditionEvaluator(
            new EquipmentPortCatalog(\dirname(__DIR__, 4)),
            $synergyCalculator,
            new GearHelper($this->createMock(PlayerHelper::class)),
        );
    }

    private function piece(?string $portSkillSlug, int $gearBit): PlayerItem
    {
        $generic = new Item();
        $generic->setSlug('test-piece');

        if ($portSkillSlug !== null) {
            $skill = new Skill();
            $skill->setSlug($portSkillSlug);
            $generic->addRequirement($skill);
        }

        $item = new PlayerItem();
        $item->setGenericItem($generic);
        $item->setGear($gearBit);

        return $item;
    }

    /**
     * @param list<PlayerItem> $items
     */
    private function playerWearing(array $items): Player&MockObject
    {
        $inventory = $this->createMock(Inventory::class);
        $inventory->method('isBag')->willReturn(true);
        $inventory->method('getItems')->willReturn(new ArrayCollection($items));

        $player = $this->createMock(Player::class);
        $player->method('getInventories')->willReturn(new ArrayCollection([$inventory]));

        return $player;
    }
}
