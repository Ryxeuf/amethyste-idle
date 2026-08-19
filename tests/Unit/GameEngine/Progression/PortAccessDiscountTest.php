<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\GameEngine\Progression\PortAccessDiscount;
use App\GameEngine\Progression\SkillCostScale;
use App\GameEngine\Progression\SynergyCalculator;
use PHPUnit\Framework\TestCase;

/**
 * La remise d'acces d'une accointance (ARC-16b).
 *
 * `access_discount` : *« l'echelon 3 de port de l'arc coute un palier de
 * moins »*. La remise est fixe par la regle — un barreau sur `SkillCostScale`,
 * jamais un nombre en donnees — et ne touche que l'echelon 3.
 */
class PortAccessDiscountTest extends TestCase
{
    public function testTheRungThreeOfADiscountedFamilyCostsOneRungLess(): void
    {
        // archer_weapon_t3 est l'echelon 3 de la famille bow, au barreau 25.
        $rungThree = $this->skill('archer-weapon-t3', SkillCostScale::TIER_2);

        self::assertSame(SkillCostScale::TIER_1, $this->discount(['bow'])->effectiveRequiredPointsOf($this->player(), $rungThree));
        self::assertSame(SkillCostScale::TIER_2, $this->discount([])->effectiveRequiredPointsOf($this->player(), $rungThree));
    }

    /**
     * Seul le dernier barreau se remise : l'entree est gratuite par regle, et
     * l'echelon 2 remise ferait de l'accointance un raccourci de milieu
     * d'echelle que le canon n'ecrit pas.
     */
    public function testOnlyTheThirdRungIsDiscounted(): void
    {
        $rungTwo = $this->skill('archer-weapon-t2', SkillCostScale::TIER_1);
        $ordinary = $this->skill('fireball-mastery', SkillCostScale::TIER_2);

        self::assertSame(SkillCostScale::TIER_1, $this->discount(['bow'])->effectiveRequiredPointsOf($this->player(), $rungTwo));
        self::assertSame(SkillCostScale::TIER_2, $this->discount(['bow'])->effectiveRequiredPointsOf($this->player(), $ordinary));
    }

    /**
     * Le barreau d'en dessous se lit sur l'echelle, et l'echelle seule : un
     * cout hors barreau est rendu tel quel — le remiser inventerait un barreau
     * que personne n'a decide —, et sous l'entree il n'y a rien.
     */
    public function testRungBelowWalksTheLadderAndRefusesToInvent(): void
    {
        self::assertSame(SkillCostScale::TIER_3, SkillCostScale::rungBelow(SkillCostScale::CAPSTONE));
        self::assertSame(SkillCostScale::TIER_1, SkillCostScale::rungBelow(SkillCostScale::TIER_2));
        self::assertSame(SkillCostScale::ENTRY, SkillCostScale::rungBelow(SkillCostScale::TIER_1));
        self::assertSame(SkillCostScale::ENTRY, SkillCostScale::rungBelow(SkillCostScale::ENTRY));
        self::assertSame(37, SkillCostScale::rungBelow(37));
    }

    /**
     * @param list<string> $families
     */
    private function discount(array $families): PortAccessDiscount
    {
        $synergyCalculator = $this->createMock(SynergyCalculator::class);
        $synergyCalculator->method('accessDiscountFamilies')->willReturn($families);

        return new PortAccessDiscount(new EquipmentPortCatalog(\dirname(__DIR__, 4)), $synergyCalculator);
    }

    private function skill(string $slug, int $points): Skill
    {
        $skill = new Skill();
        $skill->setSlug($slug);
        $skill->setRequiredPoints($points);

        return $skill;
    }

    private function player(): Player
    {
        return $this->createMock(Player::class);
    }
}
