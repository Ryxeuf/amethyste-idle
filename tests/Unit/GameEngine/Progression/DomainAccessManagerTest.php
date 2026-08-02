<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\GameEngine\Notification\NotificationService;
use App\GameEngine\Progression\DomainAccessManager;
use App\GameEngine\Progression\EquipmentPortCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * ONB-08 — l'ouverture d'un arbre, et ce qu'elle ne fait pas.
 *
 * Les quatre conditions non negociables du cadrage se verifient a deux
 * endroits : la **forme des donnees** (les 36 parchemins, cf.
 * `DomainParchmentContractTest`) et le **comportement du service**, ici.
 */
class DomainAccessManagerTest extends TestCase
{
    private DomainAccessManager $manager;

    protected function setUp(): void
    {
        $this->manager = new DomainAccessManager(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(NotificationService::class),
            new NullLogger(),
            new EquipmentPortCatalog(\dirname(__DIR__, 4)),
        );
    }

    /**
     * ONB-09 — l'ouverture est un moment, donc elle s'annonce.
     *
     * Et **une seule fois** : un parchemin relu ne rejoue pas l'annonce, sans
     * quoi la notification cesserait de signifier « quelque chose vient de se
     * gagner ».
     */
    public function testOpeningAnnouncesItselfOnlyOnce(): void
    {
        $notifier = $this->createMock(NotificationService::class);
        $notifier->expects(self::once())->method('notify');

        $manager = new DomainAccessManager(
            $this->createMock(EntityManagerInterface::class),
            $notifier,
            new NullLogger(),
            new EquipmentPortCatalog(\dirname(__DIR__, 4)),
        );

        $player = new Player();
        $domain = $this->domain(1);

        $manager->open($player, $domain);
        $manager->open($player, $domain);
    }

    /**
     * Une annonce ratee n'annule pas l'ouverture.
     *
     * Le joueur a consomme son parchemin : perdre l'arbre parce que le hub
     * temps reel est injoignable serait un vol.
     *
     * Le personnage de ce test n'est **pas persiste**, et c'est volontaire :
     * c'est ce qui verifie que le rattrapage ne peut pas echouer a son tour.
     * `Player::$id` est une propriete typee non initialisee tant que Doctrine
     * n'a pas ecrit, et la lire depuis le `catch` levait une `Error` qui
     * annulait l'ouverture — un defaut qui ne survient que sur le chemin
     * d'echec, donc exactement celui qu'on ne voit jamais en jouant.
     */
    public function testAFailedAnnouncementStillOpensTheTree(): void
    {
        $notifier = $this->createMock(NotificationService::class);
        $notifier->method('notify')->willThrowException(new \RuntimeException('hub down'));

        $manager = new DomainAccessManager(
            $this->createMock(EntityManagerInterface::class),
            $notifier,
            new NullLogger(),
            new EquipmentPortCatalog(\dirname(__DIR__, 4)),
        );

        $player = new Player();
        $domain = $this->domain(1);

        self::assertTrue($manager->open($player, $domain));
        self::assertTrue($manager->isOpen($player, $domain));
    }

    public function testOpeningATreeMakesItOpen(): void
    {
        $player = new Player();
        $domain = $this->domain(1);

        self::assertFalse($this->manager->isOpen($player, $domain));
        self::assertTrue($this->manager->open($player, $domain));
        self::assertTrue($this->manager->isOpen($player, $domain));
    }

    /**
     * Condition 3 — l'ouverture est idempotente.
     *
     * Un parchemin relu ne cree pas de seconde ligne et ne rejoue pas
     * l'annonce : c'est le booleen de retour qui porte la difference.
     */
    public function testOpeningTwiceIsIdempotent(): void
    {
        $player = new Player();
        $domain = $this->domain(1);

        self::assertTrue($this->manager->open($player, $domain));
        self::assertFalse($this->manager->open($player, $domain));
        self::assertCount(1, $player->getDomainAccesses());
    }

    /**
     * Condition 2 — en posseder un n'en interdit aucun autre.
     */
    public function testOpeningATreeClosesNoOther(): void
    {
        $player = new Player();
        $first = $this->domain(1);
        $second = $this->domain(2);

        $this->manager->open($player, $first);
        $this->manager->open($player, $second);

        self::assertTrue($this->manager->isOpen($player, $first));
        self::assertTrue($this->manager->isOpen($player, $second));
        self::assertCount(2, $this->manager->openedDomains($player));
    }

    /**
     * Condition 1 — aucun prerequis. Le service n'a aucun moyen d'en consulter
     * un : la signature ne recoit ni peuple, ni faction, ni progression.
     */
    public function testAnyTreeOpensForAnyoneWithoutPrerequisite(): void
    {
        $player = new Player();

        foreach ([1, 2, 3, 4, 5] as $id) {
            self::assertTrue($this->manager->open($player, $this->domain($id)));
        }
    }

    public function testAClosedTreeMakesItsNodesUnreachable(): void
    {
        $player = new Player();
        $domain = $this->domain(1);

        self::assertFalse($this->manager->isSkillReachable($player, $this->skill([$domain])));
    }

    /**
     * Un nœud **partage** entre plusieurs arbres suffit a un seul arbre ouvert.
     *
     * « Plusieurs chemins pour la meme chose » (ONB-20b) : exiger tous les
     * arbres d'un nœud partage le rendrait plus cher que les nœuds exclusifs,
     * ce qui est exactement l'inverse de l'intention.
     */
    public function testASharedNodeNeedsOnlyOneOfItsTreesOpen(): void
    {
        $player = new Player();
        $opened = $this->domain(1);
        $other = $this->domain(2);

        $this->manager->open($player, $opened);

        self::assertTrue($this->manager->isSkillReachable($player, $this->skill([$other, $opened])));
    }

    /**
     * La frontiere : une competence sans domaine reste libre pour tout le monde.
     *
     * C'est ce qui empeche la doctrine du parchemin de devenir « une parade de
     * verrous » — les verbes elementaires ne sont rattaches a aucun arbre.
     */
    public function testASkillWithoutADomainIsFreeForEveryone(): void
    {
        self::assertTrue($this->manager->isSkillReachable(new Player(), $this->skill([])));
    }

    /**
     * ONB-20b — ouvrir un arbre livre immediatement son kit de port.
     *
     * C'est ce qui garantit le plancher jour 1 : on ne donne jamais une arme
     * qu'on ne peut pas tenir. Le kit se lit dans le graphe reel — les
     * competences de l'arbre dont le slug est un echelon 1 — et non dans une
     * table de correspondance entre cles de fixtures et domaines.
     */
    public function testOpeningATreeDeliversItsPortKit(): void
    {
        $player = new Player();
        $domain = $this->domain(1);

        $port = $this->skill([$domain], 'port-axe');
        $paid = $this->skill([$domain], 'berserk-weapon-t2');
        $domain->addSkill($port);
        $domain->addSkill($paid);

        $this->manager->open($player, $domain);

        self::assertTrue($player->hasSkill($port), 'Le kit de port n\'a pas ete livre.');
        self::assertFalse($player->hasSkill($paid), 'Un echelon payant a ete offert avec le kit.');
    }

    /**
     * Le kit ne se livre qu'une fois, et n'echoue pas sur ce qui est deja su.
     */
    public function testThePortKitIsGrantedOnlyOnce(): void
    {
        $player = new Player();
        $domain = $this->domain(1);
        $domain->addSkill($this->skill([$domain], 'port-axe'));

        self::assertSame(1, $this->manager->grantPortKit($player, $domain));
        self::assertSame(0, $this->manager->grantPortKit($player, $domain));
    }

    // =====================================================================
    // OBJ-05 — ouvrir un arbre de recolte livre l'outil de palier 1
    // =====================================================================

    /**
     * La garantie anti-mur de GAME_ITEMS §4.3 : la recolte exige un outil,
     * donc l'outil de bronze arrive avec l'arbre — range au sac, equipe, et
     * l'emplacement d'outil ouvert. Le cout reel est le parchemin, jamais
     * l'outil.
     */
    public function testOpeningAGatheringTreeDeliversItsBronzeTool(): void
    {
        $manager = $this->managerWithBronzeTool($bronze);

        $player = new Player();
        $bag = new Inventory();
        $player->addInventory($bag);

        $domain = $this->domain(1);
        $domain->addSkill($this->toolUnlockSkill($domain, 'pickaxe'));

        $manager->open($player, $domain);

        self::assertCount(1, $bag->getItems());
        /** @var PlayerItem $granted */
        $granted = $bag->getItems()->first();
        self::assertSame($bronze, $granted->getGenericItem());
        self::assertSame(PlayerItem::TOOL_TYPE_TO_GEAR['pickaxe'], $granted->getGear(), 'L\'outil offert doit arriver equipe.');
        self::assertTrue($player->hasToolSlot('pickaxe'), 'L\'emplacement d\'outil doit s\'ouvrir avec l\'octroi.');
    }

    /**
     * L'octroi comble un manque, il ne remplit pas un entrepot : un joueur qui
     * possede deja un outil du type n'en recoit pas un second.
     */
    public function testTheToolIsNotGrantedTwiceNorWhenOneIsAlreadyOwned(): void
    {
        $manager = $this->managerWithBronzeTool($bronze);

        $player = new Player();
        $bag = new Inventory();
        $player->addInventory($bag);

        $owned = new PlayerItem();
        $owned->setGenericItem($bronze);
        $bag->addItem($owned);
        $owned->setInventory($bag);

        $domain = $this->domain(1);
        $domain->addSkill($this->toolUnlockSkill($domain, 'pickaxe'));

        self::assertSame(0, $manager->grantGatherToolKit($player, $domain));
        self::assertCount(1, $bag->getItems());
    }

    /**
     * Seule la recolte porte la garantie : l'outil d'artisanat reste un achat
     * (GAME_ITEMS §4.3 parle de l'arbre de **recolte**), et un arbre sans nœud
     * `tool_slot.unlock` ne livre rien.
     */
    public function testACraftToolIsNeverGrantedAtOpening(): void
    {
        $manager = $this->managerWithBronzeTool($bronze);

        $player = new Player();
        $player->addInventory(new Inventory());

        $craftDomain = $this->domain(1);
        $craftDomain->addSkill($this->toolUnlockSkill($craftDomain, 'hammer'));

        self::assertSame(0, $manager->grantGatherToolKit($player, $craftDomain));
        self::assertSame(0, $manager->grantGatherToolKit($player, $this->domain(2)));
    }

    /**
     * Manager dont le depot d'objets sait rendre un outil de bronze du type
     * demande. L'outil est expose par reference pour les assertions.
     */
    private function managerWithBronzeTool(?Item &$bronze): DomainAccessManager
    {
        $bronze = new Item();
        $bronze->setName('Outil de bronze');
        $bronze->setSlug('tool-bronze');
        $bronze->setType(Item::TYPE_TOOL);
        $bronze->setToolTier(Item::TOOL_TIER_BRONZE);

        $itemRepository = $this->createMock(EntityRepository::class);
        $itemRepository->method('findOneBy')->willReturnCallback(function (array $criteria) use (&$bronze): ?Item {
            $bronze->setToolType($criteria['toolType']);

            return $bronze;
        });

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnMap([
            [Item::class, $itemRepository],
        ]);

        return new DomainAccessManager(
            $entityManager,
            $this->createMock(NotificationService::class),
            new NullLogger(),
            new EquipmentPortCatalog(\dirname(__DIR__, 4)),
        );
    }

    private function toolUnlockSkill(Domain $domain, string $slot): Skill
    {
        $skill = $this->skill([$domain], 'entry-' . $slot);
        $skill->setActions([['action' => 'tool_slot.unlock', 'slot' => $slot]]);

        return $skill;
    }

    /**
     * @param Domain[] $domains
     */
    private function skill(array $domains, string $slug = 'node'): Skill
    {
        $skill = new Skill();
        $skill->setSlug($slug);
        $skill->setTitle('Nœud');
        $skill->setDescription('.');
        $skill->setRequiredPoints(0);

        foreach ($domains as $domain) {
            $skill->addDomain($domain);
        }

        return $skill;
    }

    private function domain(int $id): Domain
    {
        $domain = new Domain();
        $domain->setTitle("Domaine $id");
        $domain->setRandomSeed(1);
        $domain->setGraphHeight(5);

        $property = new \ReflectionProperty($domain, 'id');
        $property->setValue($domain, $id);

        return $domain;
    }
}
