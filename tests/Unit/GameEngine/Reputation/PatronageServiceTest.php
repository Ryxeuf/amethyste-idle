<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use App\GameEngine\Reputation\FactionTensionCatalog;
use App\GameEngine\Reputation\PatronageException;
use App\GameEngine\Reputation\PatronageService;
use App\GameEngine\Reputation\ReputationManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Les couleurs qu'on porte, une seule paire a la fois (FAC-01).
 *
 * GAME_WORLD § 6.4 c : « on porte les couleurs d'une seule faction a la fois
 * (changeable hors combat) ». Sans exclusivite, le patronage ne serait qu'un
 * quatrieme empilement de bonus — exactement ce que « un palier ouvre des
 * portes ; il n'empile jamais de la puissance » refuse.
 */
class PatronageServiceTest extends TestCase
{
    public function testCarryingColoursRequiresTheTier(): void
    {
        $player = new Player();
        $chevaliers = $this->faction('chevaliers');

        $service = $this->service($player, [$chevaliers->getSlug() => 500]);

        try {
            $service->choose($player, $chevaliers);
            self::fail('Un inconnu ne porte pas les couleurs d\'une maison.');
        } catch (PatronageException $e) {
            self::assertSame(PatronageException::REASON_TIER, $e->reason);
        }

        self::assertNull($player->getPatronFaction());
    }

    public function testReachingTheTierOpensThePatronage(): void
    {
        $player = new Player();
        $chevaliers = $this->faction('chevaliers');

        $this->service($player, [$chevaliers->getSlug() => 2000])->choose($player, $chevaliers);

        self::assertSame($chevaliers, $player->getPatronFaction());
    }

    /**
     * Porter d'autres couleurs retire les precedentes.
     *
     * C'est le cœur du jalon : deux patronages simultanes rendraient le choix
     * gratuit, et un joueur assidu finirait par porter les quatre.
     */
    public function testChoosingASecondFactionReplacesTheFirst(): void
    {
        $player = new Player();
        $chevaliers = $this->faction('chevaliers');
        $mages = $this->faction('mages');

        $service = $this->service($player, [
            $chevaliers->getSlug() => 6000,
            $mages->getSlug() => 6000,
        ]);

        $service->choose($player, $chevaliers);
        $service->choose($player, $mages);

        self::assertSame($mages, $player->getPatronFaction());
    }

    /**
     * Le neutre est une position, pas un vide.
     *
     * Sans retrait, on ne quitterait une faction qu'en en rejoignant une autre :
     * le joueur qui veut n'appartenir a personne n'aurait aucun moyen de le
     * dire.
     */
    public function testColoursCanBePutDown(): void
    {
        $player = new Player();
        $chevaliers = $this->faction('chevaliers');

        $service = $this->service($player, [$chevaliers->getSlug() => 6000]);
        $service->choose($player, $chevaliers);
        $service->clear($player);

        self::assertNull($player->getPatronFaction());
    }

    /**
     * Pas de changement de couleurs au milieu d'un combat.
     *
     * Les statistiques du patron entrent dans le calcul du geste : en changer
     * entre deux tours ferait bouger les points de vie maximum sans qu'aucun
     * coup n'ait ete porte.
     */
    public function testColoursCannotChangeDuringAFight(): void
    {
        $player = new Player();
        $player->setFight(new Fight());
        $chevaliers = $this->faction('chevaliers');

        try {
            $this->service($player, [$chevaliers->getSlug() => 6000])->choose($player, $chevaliers);
            self::fail('Le changement de patronage en combat doit etre refuse.');
        } catch (PatronageException $e) {
            self::assertSame(PatronageException::REASON_IN_COMBAT, $e->reason);
        }
    }

    public function testColoursCannotBePutDownDuringAFightEither(): void
    {
        $player = new Player();
        $player->setFight(new Fight());

        $this->expectException(PatronageException::class);

        $this->service($player, [])->clear($player);
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

    private function faction(string $slug): Faction
    {
        return (new Faction())->setSlug($slug)->setName($slug);
    }

    /**
     * @param array<string, int> $reputations reputation du joueur par slug
     */
    private function service(Player $player, array $reputations): PatronageService
    {
        $reputationManager = $this->createMock(ReputationManager::class);
        $reputationManager->method('getPlayerFaction')->willReturnCallback(
            function (Player $subject, Faction $faction) use ($reputations): ?PlayerFaction {
                if (!isset($reputations[$faction->getSlug()])) {
                    return null;
                }

                $playerFaction = new PlayerFaction();
                $playerFaction->setFaction($faction);
                $playerFaction->setReputation($reputations[$faction->getSlug()]);

                return $playerFaction;
            },
        );

        return new PatronageService(
            $this->createMock(EntityManagerInterface::class),
            new FactionTensionCatalog(\dirname(__DIR__, 4)),
            $reputationManager,
        );
    }
}
