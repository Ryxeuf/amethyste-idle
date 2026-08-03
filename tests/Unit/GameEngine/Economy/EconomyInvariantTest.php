<?php

namespace App\Tests\Unit\GameEngine\Economy;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\User;
use App\Enum\CraftOrderStatus;
use App\Enum\Purity;
use App\GameEngine\Auction\AuctionAntiExploit;
use App\GameEngine\Auction\AuctionSettlement;
use App\GameEngine\Crafting\CrafterReputationManager;
use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Crafting\CraftOrderManager;
use App\GameEngine\Economy\PurityChain;
use App\GameEngine\GameMaster\GameMasterPolicy;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Region\PlayerRegionResolver;
use App\Repository\CrafterReputationRepository;
use App\Repository\CraftOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Lois de l'economie joueur, tous canaux confondus (ECO-17).
 *
 * L'audit d'ECO-17 a trouve **211 tests unitaires** deja ecrits sur le domaine
 * economique, et les quatre axes du jalon couverts. Mais ils sont tous **par
 * canal** : l'hotel des ventes teste l'hotel des ventes, l'echoppe teste
 * l'echoppe. `AuctionSettlementTest` epingle huit scenarios chiffres a la main.
 *
 * Aucun n'enonce la **loi**. Or les trois canaux — hotel des ventes, echoppe
 * joueur, commande de craft — partagent une seule autorite de repartition,
 * `AuctionSettlement::compute()`. Une loi violee la l'est partout a la fois, et
 * un cas chiffre ne la verrait que s'il tombe pile dessus.
 *
 * Ces tests balaient **630 combinaisons** plutot que d'y piocher. Le balayage
 * est interne aux methodes et non porte par un fournisseur de donnees : la CI
 * tourne en `--testdox`, et 630 lignes par loi noieraient le rapport pour un
 * gain de lisibilite nul en cas d'echec — le message porte deja les entrees
 * fautives.
 */
class EconomyInvariantTest extends TestCase
{
    private const PRICES = [1, 7, 99, 100, 1_000, 13_337, 1_000_000];
    private const TAX_RATES = [0.0, 0.0001, 0.05, 0.15, 0.5, 1.0];
    private const REBATE_RATES = [0.0, 0.02, 0.10, 0.5, 1.0];

    /**
     * Les trois regimes : region sans maitre, region avec maitre et acheteur
     * etranger, region avec maitre et acheteur membre.
     */
    private const REGIMES = [[false, false], [true, false], [true, true]];

    /**
     * @param callable(AuctionSettlement, int, float, bool, bool, float): void $assert
     */
    private function sweep(callable $assert): void
    {
        $combinations = 0;

        foreach (self::PRICES as $price) {
            foreach (self::TAX_RATES as $taxRate) {
                foreach (self::REBATE_RATES as $rebateRate) {
                    foreach (self::REGIMES as [$hasRuler, $isMember]) {
                        ++$combinations;
                        $assert(
                            AuctionSettlement::compute($price, $taxRate, $hasRuler, $isMember, $rebateRate),
                            $price,
                            $taxRate,
                            $hasRuler,
                            $isMember,
                            $rebateRate,
                        );
                    }
                }
            }
        }

        // Sans cette borne, une constante videe par megarde rendrait chaque loi
        // verte sur zero combinaison.
        $this->assertSame(630, $combinations, 'Le balayage ne couvre plus l\'espace attendu.');
    }

    private function context(int $price, float $taxRate, bool $hasRuler, bool $isMember, float $rebateRate): string
    {
        return sprintf(
            'prix %d, taxe %.4f, ristourne %.2f, maitre %s, membre %s',
            $price,
            $taxRate,
            $rebateRate,
            $hasRuler ? 'oui' : 'non',
            $isMember ? 'oui' : 'non',
        );
    }

    /**
     * Conservation : rien n'apparait, rien ne disparait sauf par destruction.
     *
     * Ce que l'acheteur paie se retrouve integralement chez le vendeur, dans le
     * tresor de la guilde, ou detruit. C'est la loi qui relie l'economie
     * d'echange a la mesure de masse monetaire d'ECO-15 : sans elle, un canal
     * pourrait creer des Gils a chaque transaction et l'alerte d'inflation
     * remonterait un chiffre sans savoir d'ou il vient.
     */
    public function testWhatTheBuyerPaysIsFullyAccountedFor(): void
    {
        $this->sweep(function (AuctionSettlement $s, int $price, float $tax, bool $ruler, bool $member, float $rebate): void {
            $this->assertSame(
                $s->buyerCharge,
                $s->sellerRevenue + $s->treasuryAmount + $s->burnedAmount,
                'Des Gils apparaissent ou disparaissent sans etre comptes comme detruits — '
                . $this->context($price, $tax, $ruler, $member, $rebate),
            );
        });
    }

    /**
     * La destruction est le **seul** changement de masse monetaire.
     *
     * Corollaire direct d'ECO-15 : hors `burnedAmount`, une vente entre joueurs
     * deplace des Gils sans en creer ni en detruire un seul. C'est ce qui rend
     * la mesure de stock insensible a la velocite.
     */
    public function testOnlyBurnedGilsLeaveTheEconomy(): void
    {
        $this->sweep(function (AuctionSettlement $s, int $price, float $tax, bool $ruler, bool $member, float $rebate): void {
            $context = $this->context($price, $tax, $ruler, $member, $rebate);

            $this->assertSame(
                -$s->burnedAmount,
                $s->sellerRevenue + $s->treasuryAmount - $s->buyerCharge,
                'La variation de masse monetaire ne vaut pas l\'oppose de ce qui est detruit — ' . $context,
            );

            if ($s->burnedAmount > 0) {
                $this->assertFalse($ruler, 'Des Gils sont detruits alors qu\'une guilde controle la region — ' . $context);
            }
            if (!$ruler) {
                $this->assertSame(
                    $s->taxAmount,
                    $s->burnedAmount,
                    'Sans maitre, toute la taxe doit etre detruite — c\'est le gold sink du canal. ' . $context,
                );
            }
        });
    }

    /**
     * Le vendeur ne depend jamais de l'identite de l'acheteur.
     *
     * `AuctionSettlementTest` verifie ce point sur **un** couple de valeurs.
     * Sans balayage, une regression sur une plage de taux passerait.
     */
    public function testSellerRevenueIgnoresTheBuyer(): void
    {
        $this->sweep(function (AuctionSettlement $s, int $price, float $tax, bool $ruler, bool $member, float $rebate): void {
            $this->assertSame(
                AuctionSettlement::compute($price, $tax, $ruler, false, $rebate)->sellerRevenue,
                AuctionSettlement::compute($price, $tax, $ruler, true, $rebate)->sellerRevenue,
                'Le revenu du vendeur varie selon l\'appartenance de guilde de l\'acheteur : impossible a anticiper en fixant un prix. '
                . $this->context($price, $tax, $ruler, $member, $rebate),
            );
        });
    }

    /**
     * Aucune part n'est jamais negative.
     *
     * Un tresor negatif signifierait que la guilde finance la ristourne sur ses
     * reserves a chaque transaction — une fuite deguisee en avantage.
     */
    public function testNoShareIsEverNegative(): void
    {
        $this->sweep(function (AuctionSettlement $s, int $price, float $tax, bool $ruler, bool $member, float $rebate): void {
            $context = $this->context($price, $tax, $ruler, $member, $rebate);

            $this->assertGreaterThanOrEqual(0, $s->taxAmount, $context);
            $this->assertGreaterThanOrEqual(0, $s->memberRebate, $context);
            $this->assertGreaterThanOrEqual(0, $s->sellerRevenue, $context);
            $this->assertGreaterThanOrEqual(0, $s->buyerCharge, $context);
            $this->assertGreaterThanOrEqual(0, $s->burnedAmount, $context);
            $this->assertGreaterThanOrEqual(
                0,
                $s->treasuryAmount,
                'La guilde financerait la ristourne sur son tresor — ' . $context,
            );
        });
    }

    /**
     * La ristourne ne depasse jamais la taxe prelevee.
     */
    public function testRebateNeverExceedsTheTax(): void
    {
        $this->sweep(function (AuctionSettlement $s, int $price, float $tax, bool $ruler, bool $member, float $rebate): void {
            $this->assertLessThanOrEqual(
                $s->taxAmount,
                $s->memberRebate,
                'La guilde reverse plus qu\'elle n\'a preleve — ' . $this->context($price, $tax, $ruler, $member, $rebate),
            );
        });
    }

    /**
     * L'acheteur ne paie jamais plus que le prix affiche.
     *
     * Une ristourne ne peut que reduire ; rien dans le modele n'autorise une
     * majoration selon l'acheteur.
     */
    public function testTheBuyerNeverPaysMoreThanTheListedPrice(): void
    {
        $this->sweep(function (AuctionSettlement $s, int $price, float $tax, bool $ruler, bool $member, float $rebate): void {
            $context = $this->context($price, $tax, $ruler, $member, $rebate);

            $this->assertLessThanOrEqual($price, $s->buyerCharge, $context);
            $this->assertSame($price, $s->totalPrice, $context);
        });
    }

    /**
     * Un taux nul ne prend rien, ne detruit rien, ne rembourse rien.
     *
     * Le cas limite qui doit rester neutre : une region sans taxe ne doit pas
     * devenir un puits par arrondi.
     */
    public function testAZeroTaxRateIsPerfectlyNeutral(): void
    {
        foreach (self::REGIMES as [$hasRuler, $isMember]) {
            $settlement = AuctionSettlement::compute(1_000, 0.0, $hasRuler, $isMember, 0.10);

            $this->assertSame(0, $settlement->taxAmount);
            $this->assertSame(0, $settlement->burnedAmount);
            $this->assertSame(0, $settlement->memberRebate);
            $this->assertSame(1_000, $settlement->sellerRevenue);
            $this->assertSame(1_000, $settlement->buyerCharge);
        }
    }

    /**
     * Un taux negatif ne cree pas de Gils.
     *
     * `compute()` borne les deux taux a zero. Sans cette borne, une region mal
     * configuree **verserait** de l'argent a l'acheteur a chaque vente.
     */
    public function testANegativeRateCannotMintGils(): void
    {
        $settlement = AuctionSettlement::compute(1_000, -0.5, true, true, -0.5);

        $this->assertSame(0, $settlement->taxAmount);
        $this->assertSame(0, $settlement->memberRebate);
        $this->assertSame(1_000, $settlement->sellerRevenue);
        $this->assertSame(1_000, $settlement->buyerCharge);
        $this->assertSame(0, $settlement->burnedAmount);
    }

    /**
     * ECO-28 — la loi du canal de service : **un objet en escrow de service
     * conserve son proprietaire de liaison, quel que soit le chemin de
     * sortie**.
     *
     * Les trois sorties (livree, annulee, expiree) sont balayees contre les
     * trois etats de liaison possibles (non lie, lie au commanditaire, lie a
     * un tiers — l'etat herite d'un cadeau d'avant ECO-01). Neuf chemins, et
     * sur aucun `boundToPlayerId` ne bouge : la commande de service est le
     * seul canal ou un objet lie circule, et il ne doit jamais en profiter
     * pour le delier ni le relier.
     */
    public function testAServiceEscrowNeverTouchesTheBindingOwner(): void
    {
        $combinations = 0;

        foreach ([CraftOrderStatus::Fulfilled, CraftOrderStatus::Cancelled, CraftOrderStatus::Expired] as $exit) {
            foreach ([null, 1, 42] as $boundTo) {
                $manager = $this->serviceManager();
                $requester = $this->servicePlayer(1, 1_000);
                $piece = $this->servicePiece($requester, $boundTo);
                $this->serviceCrystals($requester);

                $order = $manager->createServiceOrder($requester, $piece, 100);

                if (CraftOrderStatus::Fulfilled === $exit) {
                    $crafter = $this->servicePlayer(2, 0);
                    $order->setCrafter($crafter);
                    $order->setStatus(CraftOrderStatus::Claimed);
                    $order->setReadyAt(new \DateTimeImmutable('-1 minute'));
                    $manager->fulfillOrder($crafter, $order);
                } elseif (CraftOrderStatus::Cancelled === $exit) {
                    $manager->cancelOrder($requester, $order);
                } else {
                    $manager->releaseEscrow($order, CraftOrderStatus::Expired);
                }

                $context = sprintf('sortie=%s, liaison=%s', $exit->value, $boundTo ?? 'aucune');
                $this->assertSame($exit, $order->getStatus(), $context);
                $this->assertSame($boundTo, $piece->getBoundToPlayerId(), sprintf('La liaison a bouge (%s).', $context));
                $this->assertNotNull($piece->getInventory(), sprintf('La piece n\'est pas revenue (%s).', $context));
                $this->assertSame($requester, $piece->getInventory()->getPlayer(), sprintf('La piece est revenue chez le mauvais joueur (%s).', $context));
                ++$combinations;
            }
        }

        $this->assertSame(9, $combinations, 'Le balayage doit couvrir les neuf chemins de sortie.');
    }

    private function serviceManager(): CraftOrderManager
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $orderRepository = $this->createMock(CraftOrderRepository::class);
        $orderRepository->method('countActiveByRequester')->willReturn(0);
        $craftingManager = $this->createMock(CraftingManager::class);
        $craftingManager->method('getCraftingLevel')->willReturn(99);
        $purityChain = $this->createMock(PurityChain::class);
        $purityChain->method('weakestOf')->willReturn(null);

        return new CraftOrderManager(
            $em,
            $orderRepository,
            new PlayerRegionResolver(),
            $craftingManager,
            $this->createMock(AuctionAntiExploit::class),
            new CrafterReputationManager($em, $this->createMock(CrafterReputationRepository::class)),
            $this->createMock(TownControlManager::class),
            $this->createMock(GuildManager::class),
            $this->createMock(PlayerItemGenerator::class),
            new NullLogger(),
            $purityChain,
            new GameMasterPolicy(),
        );
    }

    private function servicePlayer(int $id, int $gils): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);
        $player->setGils($gils);

        $user = new User();
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, $id);
        $player->setUser($user);

        $bag = new Inventory();
        $bag->setType(Inventory::TYPE_BAG);
        $bag->setSize(20);
        $bag->setPlayer($player);
        (new \ReflectionProperty(Player::class, 'inventories'))->setValue($player, new ArrayCollection([$bag]));

        return $player;
    }

    private function servicePiece(Player $owner, ?int $boundTo): PlayerItem
    {
        $item = new Item();
        $item->setName('Plastron de fer');
        $item->setSlug('iron-chestplate');
        $item->setType(Item::TYPE_GEAR_PIECE);
        $item->setMateriaSlots(2);

        $piece = new PlayerItem();
        (new \ReflectionProperty(PlayerItem::class, 'id'))->setValue($piece, 101);
        $piece->setGenericItem($item);
        $piece->setInventory($owner->getInventories()->first());
        $piece->setGear(0);
        $piece->setBoundToPlayerId($boundTo);

        return $piece;
    }

    private function serviceCrystals(Player $owner): void
    {
        $bag = $owner->getInventories()->first();
        for ($i = 0; $i < CraftOrderManager::SERVICE_CRYSTAL_COST; ++$i) {
            $item = new Item();
            $item->setName('Amethystite');
            $item->setSlug('ore-amethyst-crystal');
            $item->setType(Item::TYPE_RESOURCE);

            $crystal = new PlayerItem();
            $crystal->setGenericItem($item);
            $crystal->setInventory($bag);
            $crystal->setPurity(Purity::Pur);
            // Le cote inverse : la collection du sac est ce que le manager
            // itere pour collecter les amethystites.
            $bag->addItem($crystal);
        }
    }
}
