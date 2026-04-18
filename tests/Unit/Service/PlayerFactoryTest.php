<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\Game\Race;
use App\Entity\User;
use App\Service\Avatar\AvatarHashRecalculator;
use App\Service\PlayerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlayerFactoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private AvatarHashRecalculator&MockObject $recalculator;
    private PlayerFactory $factory;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->recalculator = $this->createMock(AvatarHashRecalculator::class);
        $this->factory = new PlayerFactory($this->entityManager, $this->recalculator);

        $mapRepository = $this->createMock(EntityRepository::class);
        $mapRepository->method('findOneBy')->willReturn(new Map());
        $this->entityManager->method('getRepository')->willReturn($mapRepository);
    }

    public function testCreatePlayerPersistsProvidedAppearance(): void
    {
        $this->recalculator->expects($this->once())->method('recalculate');

        $player = $this->factory->createPlayer(
            new User(),
            'Aldric',
            $this->makeRace(),
            [
                'body' => 'human_f_dark',
                'hair' => 'long_02',
                'hairColor' => '#c0392b',
            ],
        );

        $this->assertSame([
            'body' => 'human_f_dark',
            'hair' => 'long_02',
            'hairColor' => '#c0392b',
        ], $player->getAvatarAppearance());
        $this->assertTrue($player->hasAvatar());
    }

    public function testCreatePlayerUsesDefaultBodyWhenAppearanceMissing(): void
    {
        $this->recalculator->expects($this->once())->method('recalculate');

        $player = $this->factory->createPlayer(
            new User(),
            'Elara',
            $this->makeRace(),
            null,
        );

        $this->assertSame(['body' => 'human_m_light'], $player->getAvatarAppearance());
    }

    public function testCreatePlayerIgnoresEmptyOptionalFields(): void
    {
        $this->recalculator->expects($this->once())->method('recalculate');

        $player = $this->factory->createPlayer(
            new User(),
            'Thorin',
            $this->makeRace(),
            [
                'body' => 'human_m_light',
                'hair' => '',
                'hairColor' => null,
            ],
        );

        $this->assertSame(['body' => 'human_m_light'], $player->getAvatarAppearance());
    }

    public function testCreatePlayerTriggersHashRecalculation(): void
    {
        $this->recalculator
            ->expects($this->once())
            ->method('recalculate')
            ->with($this->isInstanceOf(Player::class));

        $this->factory->createPlayer(
            new User(),
            'Lyra',
            $this->makeRace(),
            ['body' => 'human_f_dark'],
        );
    }

    private function makeRace(): Race
    {
        $race = new Race();
        $race->setStatModifiers(['life' => 0, 'energy' => 0, 'speed' => 0, 'hit' => 0]);

        return $race;
    }
}
