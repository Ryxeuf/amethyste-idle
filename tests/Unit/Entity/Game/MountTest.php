<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Mount;
use PHPUnit\Framework\TestCase;

class MountTest extends TestCase
{
    public function testCreateMountWithDefaultValues(): void
    {
        $mount = new Mount();
        $mount->setSlug('horse_brown');
        $mount->setName('Cheval brun');
        $mount->setDescription('Monture commune');

        $this->assertSame('horse_brown', $mount->getSlug());
        $this->assertSame('Cheval brun', $mount->getName());
        $this->assertSame('Monture commune', $mount->getDescription());
        $this->assertNull($mount->getSpriteSheet());
        $this->assertNull($mount->getIconPath());
        $this->assertSame(50, $mount->getSpeedBonus());
        $this->assertSame(Mount::OBTENTION_PURCHASE, $mount->getObtentionType());
        $this->assertNull($mount->getGilCost());
        $this->assertSame(1, $mount->getRequiredLevel());
        $this->assertTrue($mount->isEnabled());
        $this->assertSame('Cheval brun', (string) $mount);
    }

    public function testSetObtentionTypeRejectsUnknownType(): void
    {
        $mount = new Mount();

        $this->expectException(\InvalidArgumentException::class);
        $mount->setObtentionType('gift');
    }

    public function testSetObtentionTypeAcceptsAllValidTypes(): void
    {
        $mount = new Mount();

        foreach (Mount::getObtentionTypes() as $type) {
            $mount->setObtentionType($type);
            $this->assertSame($type, $mount->getObtentionType());
        }
    }

    public function testGetObtentionTypesReturnsExpectedList(): void
    {
        $this->assertSame(
            [
                Mount::OBTENTION_QUEST,
                Mount::OBTENTION_DROP,
                Mount::OBTENTION_PURCHASE,
                Mount::OBTENTION_ACHIEVEMENT,
            ],
            Mount::getObtentionTypes()
        );
    }

    public function testFluentSetters(): void
    {
        $mount = new Mount();

        $result = $mount->setSlug('chocobo_yellow')
            ->setName('Chocobo jaune')
            ->setDescription('Oiseau geant legendaire')
            ->setSpriteSheet('mount/chocobo_yellow.png')
            ->setIconPath('mount/icons/chocobo_yellow.png')
            ->setSpeedBonus(75)
            ->setObtentionType(Mount::OBTENTION_QUEST)
            ->setGilCost(null)
            ->setRequiredLevel(30)
            ->setEnabled(false);

        $this->assertInstanceOf(Mount::class, $result);
        $this->assertSame('chocobo_yellow', $mount->getSlug());
        $this->assertSame(75, $mount->getSpeedBonus());
        $this->assertSame(30, $mount->getRequiredLevel());
        $this->assertFalse($mount->isEnabled());
    }
}
