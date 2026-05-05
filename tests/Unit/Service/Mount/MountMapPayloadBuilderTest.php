<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Mount;

use App\Entity\App\Player;
use App\Entity\Game\Mount;
use App\Service\Mount\MountMapPayloadBuilder;
use PHPUnit\Framework\TestCase;

class MountMapPayloadBuilderTest extends TestCase
{
    public function testBuildReturnsNullWhenPlayerHasNoActiveMount(): void
    {
        $builder = new MountMapPayloadBuilder();
        $player = new Player();

        $this->assertNull($builder->build($player));
    }

    public function testBuildReturnsPayloadFromActiveMount(): void
    {
        $mount = (new Mount())
            ->setSlug('horse_brown')
            ->setName('Cheval brun')
            ->setDescription('Une monture commune.')
            ->setIconPath('images/mounts/horse_brown.png')
            ->setSpriteSheet('images/mounts/horse_brown_sheet.png')
            ->setSpeedBonus(50);

        $player = new Player();
        $player->setActiveMount($mount);

        $payload = (new MountMapPayloadBuilder())->build($player);

        $this->assertSame([
            'slug' => 'horse_brown',
            'name' => 'Cheval brun',
            'iconPath' => 'images/mounts/horse_brown.png',
            'spriteSheet' => 'images/mounts/horse_brown_sheet.png',
            'speedBonus' => 50,
        ], $payload);
    }

    public function testBuildPropagatesNullableSpriteFields(): void
    {
        $mount = (new Mount())
            ->setSlug('chocobo_yellow')
            ->setName('Chocobo')
            ->setDescription('Vif et docile.')
            ->setSpeedBonus(75);

        $player = new Player();
        $player->setActiveMount($mount);

        $payload = (new MountMapPayloadBuilder())->build($player);

        $this->assertNotNull($payload);
        $this->assertNull($payload['iconPath']);
        $this->assertNull($payload['spriteSheet']);
        $this->assertSame(75, $payload['speedBonus']);
    }
}
