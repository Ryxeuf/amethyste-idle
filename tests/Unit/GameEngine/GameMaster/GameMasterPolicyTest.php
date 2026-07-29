<?php

namespace App\Tests\Unit\GameEngine\GameMaster;

use App\Entity\App\Player;
use App\GameEngine\GameMaster\GameMasterPolicy;
use App\GameEngine\GameMaster\GameMasterRestrictionException;
use PHPUnit\Framework\TestCase;

/**
 * La politique MJ se resume a une phrase — « il n'a aucun poids sur le monde » —
 * et ces tests verifient que chacune de ses trois familles de reponses la tient.
 */
class GameMasterPolicyTest extends TestCase
{
    private GameMasterPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new GameMasterPolicy();
    }

    private function player(bool $gameMaster = false, bool $incognito = false): Player
    {
        return (new Player())
            ->setGameMaster($gameMaster)
            ->setGameMasterIncognito($incognito);
    }

    public function testAnOrdinaryPlayerIsUntouchedByEveryRule(): void
    {
        $player = $this->player();

        $this->assertFalse($this->policy->isGameMaster($player));
        $this->assertTrue($this->policy->canTrade($player));
        $this->assertFalse($this->policy->revealsHiddenInformation($player));
        $this->assertFalse($this->policy->bypassesAccessGates($player));
        $this->assertTrue($this->policy->countsTowardWorldActivity($player));
        $this->assertTrue($this->policy->appearsInRankings($player));
    }

    public function testAGameMasterIsClosedToTradeAndOpenToInformation(): void
    {
        $gm = $this->player(true);

        $this->assertFalse($this->policy->canTrade($gm));
        $this->assertTrue($this->policy->revealsHiddenInformation($gm));
        $this->assertTrue($this->policy->bypassesAccessGates($gm));
        $this->assertFalse($this->policy->countsTowardWorldActivity($gm));
        $this->assertFalse($this->policy->appearsInRankings($gm));
    }

    public function testAssertMayTradeRefusesAGameMasterAndSaysWhy(): void
    {
        $this->expectException(GameMasterRestrictionException::class);
        $this->expectExceptionMessage(GameMasterPolicy::REASON_TRADE);

        $this->policy->assertMayTrade($this->player(true));
    }

    /**
     * L'exception doit rester rattrapable par les `catch (\InvalidArgumentException)`
     * deja poses sur les ecrans de commerce : sans cela, la restriction se
     * lirait comme une erreur 500.
     */
    public function testTheRestrictionIsCaughtByExistingTradeHandlers(): void
    {
        try {
            $this->policy->assertMayTrade($this->player(true));
            $this->fail('La restriction aurait du etre levee.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertInstanceOf(GameMasterRestrictionException::class, $exception);
        }
    }

    public function testAssertMayTradeLetsAnOrdinaryPlayerThrough(): void
    {
        $this->policy->assertMayTrade($this->player());
        $this->policy->assertMayTrade(null);

        $this->addToAssertionCount(2);
    }

    public function testAVisibleGameMasterIsSeenByEveryone(): void
    {
        $gm = $this->player(true);

        $this->assertTrue($this->policy->isVisibleTo($gm, $this->player()));
        $this->assertTrue($this->policy->isVisibleTo($gm, null));
    }

    public function testAnIncognitoGameMasterIsHiddenFromOrdinaryPlayers(): void
    {
        $gm = $this->player(true, true);

        $this->assertFalse($this->policy->isVisibleTo($gm, $this->player()));
        $this->assertFalse($this->policy->isVisibleTo($gm, null));
    }

    /**
     * Deux exceptions, et elles sont necessaires : se voir disparaitre de sa
     * propre liste ferait douter du mode, et deux animateurs sur la meme soiree
     * doivent pouvoir se situer.
     */
    public function testAnIncognitoGameMasterStillSeesHimselfAndOtherGameMasters(): void
    {
        $gm = $this->player(true, true);

        $this->assertTrue($this->policy->isVisibleTo($gm, $gm));
        $this->assertTrue($this->policy->isVisibleTo($gm, $this->player(true)));
    }

    /**
     * L'incognito ne s'attrape pas sans le statut : un joueur ordinaire dont le
     * drapeau serait reste a vrai reste visible.
     */
    public function testIncognitoWithoutTheStatusHidesNobody(): void
    {
        $notAGameMaster = $this->player(false, true);

        $this->assertTrue($this->policy->isVisibleTo($notAGameMaster, $this->player()));
    }

    public function testVisibleToFiltersTheListForItsViewer(): void
    {
        $ordinary = $this->player();
        $visibleGm = $this->player(true);
        $hiddenGm = $this->player(true, true);

        $seenByPlayer = $this->policy->visibleTo([$ordinary, $visibleGm, $hiddenGm], $ordinary);
        $this->assertSame([$ordinary, $visibleGm], $seenByPlayer);

        $seenByGameMaster = $this->policy->visibleTo([$ordinary, $visibleGm, $hiddenGm], $visibleGm);
        $this->assertCount(3, $seenByGameMaster);
    }
}
