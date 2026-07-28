<?php

namespace App\Tests\Unit\GameEngine\Crafting;

use App\Entity\App\CraftOrder;
use App\Entity\App\Guild;
use App\Entity\App\GuildMember;
use App\Entity\App\Player;
use App\Entity\Game\Recipe;
use App\Enum\GuildRank;
use App\GameEngine\Crafting\CraftOrderManager;
use App\GameEngine\Crafting\GuildCraftOrderManager;
use App\GameEngine\Guild\GuildManager;
use App\Repository\CraftOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * La commande de la semaine (RET-03).
 *
 * Trois regles font la difference avec une commande ordinaire, et chacune
 * repond a une facon de rater le rendez-vous : une seule commande vivante a la
 * fois, un tresor qui peut payer, et une reserve stricte aux membres.
 *
 * La quatrieme propriete testee ici ne se voit pas dans le plan mais casserait
 * silencieusement : si `createOrder()` refuse apres que le tresor a paye, les
 * Gils doivent y retourner. Un echec de validation ne doit jamais devenir une
 * fuite de monnaie de guilde.
 */
class GuildCraftOrderManagerTest extends TestCase
{
    private Guild $guild;
    private ?GuildMember $membership = null;
    private int $activeThisWeek = 0;
    private bool $createRefuses = false;

    protected function setUp(): void
    {
        $this->guild = new Guild();
        $this->membership = null;
        $this->activeThisWeek = 0;
        $this->createRefuses = false;
    }

    public function testAnOfficerPostsTheWeeklyOrder(): void
    {
        $officer = $this->memberAt(GuildRank::Officer);

        $order = $this->manager()->createGuildOrder($officer, new Recipe(), [new \stdClass()], 500);

        self::assertSame($this->guild, $order->getGuild());
        self::assertTrue($order->isGuildOrder());
    }

    public function testTheLeaderCanPostToo(): void
    {
        $leader = $this->memberAt(GuildRank::Leader);

        self::assertTrue($this->manager()->canPost($leader, $this->guild));
    }

    public function testAPlainMemberCannotPost(): void
    {
        $member = $this->memberAt(GuildRank::Member);

        self::assertFalse($this->manager()->canPost($member, $this->guild));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/officier/');
        $this->manager()->createGuildOrder($member, new Recipe(), [new \stdClass()], 500);
    }

    public function testAPlayerWithoutAGuildCannotPost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/membre de guilde/');
        $this->manager()->createGuildOrder(new Player(), new Recipe(), [new \stdClass()], 500);
    }

    /**
     * C'est un rendez-vous, pas un tableau infini. Un tableau qui se remplit ne
     * se regarde plus.
     */
    public function testOnlyOneOrderLivesAtATime(): void
    {
        $officer = $this->memberAt(GuildRank::Officer);
        $this->activeThisWeek = GuildCraftOrderManager::WEEKLY_CAP;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/deja posee/');
        $this->manager()->createGuildOrder($officer, new Recipe(), [new \stdClass()], 500);
    }

    /**
     * Une commande que seul un officier riche peut poser n'est pas une commande
     * de guilde ; c'est un service qu'il rend.
     */
    public function testTheTreasuryCanPayTheCommission(): void
    {
        $officer = $this->memberAt(GuildRank::Officer);
        $this->guild->setGilsTreasury(2000);

        $this->manager()->createGuildOrder($officer, new Recipe(), [new \stdClass()], 500, true);

        self::assertSame(1500, $this->guild->getGilsTreasury());
    }

    public function testAnEmptyTreasuryRefusesBeforeAnythingIsEngaged(): void
    {
        $officer = $this->memberAt(GuildRank::Officer);
        $this->guild->setGilsTreasury(100);

        try {
            $this->manager()->createGuildOrder($officer, new Recipe(), [new \stdClass()], 500, true);
            self::fail('Le tresor vide aurait du refuser.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('tresor', $e->getMessage());
        }

        self::assertSame(100, $this->guild->getGilsTreasury());
    }

    /**
     * Le refus peut venir d'ailleurs — materiaux insuffisants, plafond
     * personnel. Le tresor doit alors retrouver ses Gils : un echec de
     * validation ne doit jamais devenir une fuite de monnaie de guilde.
     */
    public function testAFailedCreationGivesTheTreasuryItsGilsBack(): void
    {
        $officer = $this->memberAt(GuildRank::Officer);
        $this->guild->setGilsTreasury(2000);
        $this->createRefuses = true;

        try {
            $this->manager()->createGuildOrder($officer, new Recipe(), [new \stdClass()], 500, true);
            self::fail('La creation aurait du echouer.');
        } catch (\InvalidArgumentException) {
        }

        self::assertSame(2000, $this->guild->getGilsTreasury());
        self::assertSame(0, $officer->getGils());
    }

    /**
     * Un rendez-vous interne visible de tous n'est plus interne.
     */
    public function testOnlyAMemberCanClaimTheOrder(): void
    {
        $officer = $this->memberAt(GuildRank::Officer);
        $order = new CraftOrder();
        $order->setGuild($this->guild);

        $manager = $this->manager();
        self::assertTrue($manager->canClaim($officer, $order));
        self::assertFalse($manager->canClaim(new Player(), $order));
    }

    public function testAnOrdinaryOrderStaysClaimableByAnyone(): void
    {
        $this->memberAt(GuildRank::Officer);

        self::assertTrue($this->manager()->canClaim(new Player(), new CraftOrder()));
    }

    private function memberAt(GuildRank $rank): Player
    {
        $player = new Player();
        $this->membership = new GuildMember();
        $this->membership->setGuild($this->guild);
        $this->membership->setPlayer($player);
        $this->membership->setRank($rank);

        return $player;
    }

    private function manager(): GuildCraftOrderManager
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $orderManager = $this->createMock(CraftOrderManager::class);
        $orderManager->method('createOrder')->willReturnCallback(function (): CraftOrder {
            if ($this->createRefuses) {
                throw new \InvalidArgumentException('Materiaux insuffisants.');
            }

            return new CraftOrder();
        });

        $orderRepository = $this->createMock(CraftOrderRepository::class);
        $orderRepository->method('countActiveForGuildSince')->willReturnCallback(fn (): int => $this->activeThisWeek);

        $guildManager = $this->createMock(GuildManager::class);
        $guildManager->method('getPlayerMembership')->willReturnCallback(
            fn (Player $player): ?GuildMember => $this->membership?->getPlayer() === $player ? $this->membership : null,
        );
        $guildManager->method('getPlayerGuild')->willReturnCallback(
            fn (Player $player): ?Guild => $this->membership?->getPlayer() === $player ? $this->guild : null,
        );

        return new GuildCraftOrderManager($entityManager, $orderManager, $orderRepository, $guildManager);
    }
}
