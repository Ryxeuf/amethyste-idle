<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use App\GameEngine\Reputation\ShadowsApproach;
use App\GameEngine\Reputation\ShadowsMarketCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * On ne la trouve pas : c'est elle qui vous trouve (FAC-06).
 *
 * Avant le seuil d'explorations nocturnes, la Confrerie n'existe pas pour ce
 * joueur — pas de ligne de reputation, pas de carte a l'ecran. Au seuil, la
 * ligne nait **a zero** : Neutre, jamais un gain — decouvrir n'est pas un
 * geste qui nourrit.
 */
class ShadowsApproachTest extends TestCase
{
    /** @var list<object> */
    private array $persisted = [];
    private ?PlayerFaction $line = null;
    private ?Faction $faction = null;

    protected function setUp(): void
    {
        $this->persisted = [];
        $this->line = null;
        $this->faction = (new Faction())->setSlug('ombres')->setName('Confrérie des Ruelles');
    }

    private function approach(): ShadowsApproach
    {
        $factionRepository = $this->createMock(EntityRepository::class);
        $factionRepository->method('findOneBy')->willReturnCallback(fn (): ?Faction => $this->faction);

        $lineRepository = $this->createMock(EntityRepository::class);
        $lineRepository->method('findOneBy')->willReturnCallback(fn (): ?PlayerFaction => $this->line);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnCallback(
            fn (string $class): EntityRepository => $class === Faction::class ? $factionRepository : $lineRepository,
        );
        $entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
            if ($entity instanceof PlayerFaction) {
                $this->line = $entity;
            }
        });

        return new ShadowsApproach($entityManager, new ShadowsMarketCatalog(\dirname(__DIR__, 4)));
    }

    public function testTheFactionAppearsAtTheThresholdAndAtNeutral(): void
    {
        $approach = $this->approach();
        $threshold = (new ShadowsMarketCatalog(\dirname(__DIR__, 4)))->nightExplorationsThreshold();
        $player = new Player();

        for ($night = 1; $night < $threshold; ++$night) {
            self::assertFalse($approach->recordNightExploration($player), 'Avant le seuil, la nuit ne presente personne.');
            self::assertNull($this->line, 'La faction reste invisible : pas de ligne de reputation.');
        }

        self::assertTrue($approach->recordNightExploration($player), 'Au seuil, le mot est glisse.');
        self::assertInstanceOf(PlayerFaction::class, $this->line);
        self::assertSame(0, $this->line->getReputation(), 'La ligne nait a zero — Neutre : decouvrir n\'est pas un geste qui nourrit.');
    }

    /**
     * Le premier contact fait, la nuit n'a plus rien a presenter : le
     * compteur cesse, et aucune seconde ligne n'apparait.
     */
    public function testAMetPlayerIsNeverApproachedAgain(): void
    {
        $approach = $this->approach();
        $player = new Player();
        $this->line = (new PlayerFaction())->setPlayer($player)->setFaction($this->faction);

        self::assertFalse($approach->recordNightExploration($player));
        self::assertSame(0, $player->getNightExplorations(), 'Le compteur ne sert qu\'avant le premier contact.');
        self::assertSame([], $this->persisted);
        self::assertTrue($approach->hasMet($player));
    }

    /**
     * La faction pas encore semee ne s'approche pas : le crochet est inerte,
     * pas casse — la meme doctrine que toutes les maisons.
     */
    public function testAnUnseededFactionApproachesNoOne(): void
    {
        $this->faction = null;
        $approach = $this->approach();

        self::assertFalse($approach->recordNightExploration(new Player()));
        self::assertFalse($approach->hasMet(new Player()));
    }
}
