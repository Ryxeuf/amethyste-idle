<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\Game\Race;
use App\Entity\User;
use App\GameEngine\Zone\PlayerZoneSynchronizer;
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
        $this->factory = new PlayerFactory($this->entityManager, $this->recalculator, $this->createMock(PlayerZoneSynchronizer::class));

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

    public function testCreatePlayerUsesRaceSpriteSheetAsDefaultBody(): void
    {
        $this->recalculator->expects($this->once())->method('recalculate');

        $race = $this->makeRace();
        $race->setSpriteSheet('human_v02');

        $player = $this->factory->createPlayer(
            new User(),
            'Brenor',
            $race,
            null,
        );

        $this->assertSame(['body' => 'human_v02'], $player->getAvatarAppearance());
    }

    public function testCreatePlayerExplicitBodyOverridesRaceSpriteSheet(): void
    {
        $this->recalculator->expects($this->once())->method('recalculate');

        $race = $this->makeRace();
        $race->setSpriteSheet('human_v03');

        $player = $this->factory->createPlayer(
            new User(),
            'Galadriel',
            $race,
            ['body' => 'human_v01'],
        );

        $this->assertSame(['body' => 'human_v01'], $player->getAvatarAppearance());
    }

    public function testCreatePlayerFallsBackToDefaultWhenRaceSpriteSheetIsBlank(): void
    {
        $this->recalculator->expects($this->once())->method('recalculate');

        $race = $this->makeRace();
        $race->setSpriteSheet('   ');

        $player = $this->factory->createPlayer(
            new User(),
            'Aragorn',
            $race,
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

    /**
     * ONB-07 — le peuple ne decide plus d'aucun chiffre.
     *
     * L'Orc naissait avec +8 vie sur une base de 20 : +40 % de survie face a
     * l'Humain, arbitre au pas 3 d'un tunnel par quelqu'un qui ne connait pas
     * encore le jeu.
     */
    public function testEveryPeopleStartsWithTheSameNumbers(): void
    {
        $reference = null;

        foreach (['human', 'elf', 'dwarf', 'orc'] as $slug) {
            $this->recalculator->method('recalculate');

            $player = $this->factory->createPlayer(
                new User(),
                'Nom-' . $slug,
                $this->makeRace($slug),
                ['body' => 'human_f_dark'],
            );

            $stats = [
                'life' => $player->getLife(),
                'maxLife' => $player->getMaxLife(),
                'energy' => $player->getEnergy(),
                'maxEnergy' => $player->getMaxEnergy(),
                'speed' => $player->getSpeed(),
                'hit' => $player->getHit(),
            ];

            $reference ??= $stats;
            $this->assertSame($reference, $stats, sprintf('Le peuple « %s » modifie des statistiques.', $slug));
        }
    }

    private function makeRace(string $slug = 'human'): Race
    {
        // ONB-07 : un peuple ne porte plus aucun chiffre.
        return (new Race())->setSlug($slug);
    }
}
