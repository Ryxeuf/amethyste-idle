<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use App\GameEngine\Reputation\FactionVisibility;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * Invisible jusqu'au premier contact (FAC-06).
 *
 * La Confrerie des Ruelles n'apparait pas dans l'ecran des factions tant que
 * la rencontre n'a pas eu lieu — et la rencontre EST la ligne de reputation,
 * la meme doctrine que « jamais Hostile par defaut ». Les quatre maisons qui
 * s'affichent restent affichees pour tout le monde.
 */
class FactionVisibilityTest extends TestCase
{
    private ?PlayerFaction $line = null;

    private function visibility(): FactionVisibility
    {
        $lineRepository = $this->createMock(EntityRepository::class);
        $lineRepository->method('findOneBy')->willReturnCallback(fn (): ?PlayerFaction => $this->line);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($lineRepository);

        return new FactionVisibility($entityManager);
    }

    public function testTheBrotherhoodIsHiddenBeforeFirstContact(): void
    {
        $marchands = (new Faction())->setSlug('marchands')->setName('Guilde des Marchands');
        $ombres = (new Faction())->setSlug('ombres')->setName('Confrérie des Ruelles');

        $visible = $this->visibility()->visibleFor(new Player(), [$marchands, $ombres]);

        self::assertSame([$marchands], $visible, 'Pas de tableau de quetes, pas de recruteur : on ne la trouve pas.');
    }

    public function testTheBrotherhoodAppearsOnceMet(): void
    {
        $player = new Player();
        $ombres = (new Faction())->setSlug('ombres')->setName('Confrérie des Ruelles');
        $this->line = (new PlayerFaction())->setPlayer($player)->setFaction($ombres);

        $visible = $this->visibility()->visibleFor($player, [$ombres]);

        self::assertSame([$ombres], $visible, 'La rencontre faite, la carte s\'affiche — a Neutre.');
    }

    public function testTheOtherHousesAreAlwaysVisible(): void
    {
        $factions = [];
        foreach (['marchands', 'chevaliers', 'mages', 'fonderie'] as $slug) {
            $factions[] = (new Faction())->setSlug($slug)->setName($slug);
        }

        self::assertSame($factions, $this->visibility()->visibleFor(new Player(), $factions), 'Seule la Confrerie se cache : les autres maisons s\'affichent des le jour 1.');
    }
}
