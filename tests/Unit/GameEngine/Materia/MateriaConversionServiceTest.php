<?php

namespace App\Tests\Unit\GameEngine\Materia;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Entity\Game\CodexEntry;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use App\Enum\Element;
use App\Event\Game\MateriaReadEvent;
use App\GameEngine\Codex\CodexUnlockService;
use App\GameEngine\Materia\MateriaConversionException;
use App\GameEngine\Materia\MateriaConversionService;
use App\GameEngine\Progression\ActOneMateriaGranter;
use App\GameEngine\Reputation\HostileConsequenceResolver;
use App\GameEngine\Reputation\ReputationManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Fondre ou lire — la conversion a deux destinataires (FAC-04b).
 *
 * GAME_WORLD § 12.2 : le joueur presse fond (gils + essence, le geste
 * disparait), le joueur qui pense au serveur lit (Codex + reputation +
 * accord, jamais repris). Ce test verifie que chaque destinataire recoit ce
 * qui lui revient, que la lecture se refuse aux Hostiles du Cercle — et
 * jamais la fonte — et que le crochet du Repertoire part bien.
 */
class MateriaConversionServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ReputationManager&MockObject $reputationManager;
    private CodexUnlockService&MockObject $codexUnlockService;
    private HostileConsequenceResolver&MockObject $hostileConsequences;
    private ActOneMateriaGranter&MockObject $accordGranter;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private MateriaConversionService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->reputationManager = $this->createMock(ReputationManager::class);
        $this->codexUnlockService = $this->createMock(CodexUnlockService::class);
        $this->hostileConsequences = $this->createMock(HostileConsequenceResolver::class);
        $this->accordGranter = $this->createMock(ActOneMateriaGranter::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->service = new MateriaConversionService(
            $this->entityManager,
            $this->reputationManager,
            $this->codexUnlockService,
            $this->hostileConsequences,
            $this->accordGranter,
            $this->eventDispatcher,
        );
    }

    public function testMeltPaysGilsAndEssenceAndFeedsTheFoundry(): void
    {
        $player = new Player();
        $materia = $this->materia(price: 130, level: 2);
        $this->withDomains([]);

        $this->entityManager->expects($this->once())->method('remove')->with($materia);
        $this->reputationManager->expects($this->once())
            ->method('grantGestureReputation')
            ->with($player, 'materia_melt');

        $result = $this->service->melt($player, $materia);

        self::assertSame(['gils' => 39, 'essence' => 2], $result);
        self::assertSame(39, $player->getGils());
        self::assertSame(2, $player->getEssence(), 'L\'essence suit le palier de la materia.');
    }

    public function testASocketedMateriaCannotBeConverted(): void
    {
        $materia = $this->materia(price: 130, level: 1, socketed: true);

        $this->entityManager->expects($this->never())->method('remove');
        $this->expectException(MateriaConversionException::class);

        $this->service->melt(new Player(), $materia);
    }

    public function testANonMateriaCannotBeConverted(): void
    {
        $item = $this->createMock(PlayerItem::class);
        $item->method('isMateria')->willReturn(false);

        $this->expectException(MateriaConversionException::class);

        $this->service->melt(new Player(), $item);
    }

    /**
     * La lecture inscrit : le Codex par l'element du flux, la reputation du
     * Cercle par la route de geste, l'accord dans **l'arbre qui enseigne** la
     * materia (jamais derive de l'element — doctrine d'ActOneMateriaGranter),
     * et le versement au Repertoire par l'evenement.
     */
    public function testReadInscribesCodexAccordReputationAndNotifiesTheRepertoire(): void
    {
        $player = new Player();
        $materia = $this->materia(price: 130, level: 2, spellSlug: 'fireball');
        $domain = $this->domainTeaching('fireball', 'Pyromancien');
        $this->withDomains([$domain]);

        $this->codexUnlockService->expects($this->once())
            ->method('unlockByTrigger')
            ->with($player, CodexEntry::UNLOCK_MATERIA_READ, 'fire')
            ->willReturn(1);
        $this->accordGranter->expects($this->once())
            ->method('grantAccordPoints')
            ->with($player, $domain, 4);
        $this->reputationManager->expects($this->once())
            ->method('grantGestureReputation')
            ->with($player, 'materia_read');
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(MateriaReadEvent::class), MateriaReadEvent::NAME);
        $this->entityManager->expects($this->once())->method('remove')->with($materia);

        $result = $this->service->read($player, $materia);

        self::assertSame(['codexUnlocked' => 1, 'accordDomain' => 'Pyromancien', 'accordPoints' => 4], $result);
    }

    /**
     * FAC-03, materia_reading_refused : le Cercle ne lit pas pour un Hostile —
     * et rien n'est consomme. Il reste la fonte : le garde-fou du plan (« il
     * reste la fonte, ou le stock ») est le test suivant.
     */
    public function testReadingIsRefusedToCircleHostilesWithoutConsumingAnything(): void
    {
        $this->hostileConsequences->method('isMateriaReadingRefused')->willReturn(true);
        $materia = $this->materia(price: 130, level: 1);

        $this->entityManager->expects($this->never())->method('remove');
        $this->expectException(MateriaConversionException::class);

        $this->service->read(new Player(), $materia);
    }

    public function testACircleHostileCanStillMelt(): void
    {
        $this->hostileConsequences->method('isMateriaReadingRefused')->willReturn(true);
        $player = new Player();
        $this->withDomains([]);

        $result = $this->service->melt($player, $this->materia(price: 130, level: 1));

        self::assertSame(39, $result['gils'], 'Hostile au Cercle, la fonte reste ouverte : jamais la boucle cœur.');
    }

    /**
     * Une materia qu'aucun arbre n'enseigne (cas limite : MAT-07 a raccroche
     * les orphelines) se lit quand meme — sans accord, jamais sans Codex.
     */
    public function testReadWithoutATeachingDomainStillInscribes(): void
    {
        $player = new Player();
        $materia = $this->materia(price: 130, level: 1, spellSlug: 'unknown-spell');
        $this->withDomains([]);

        $this->accordGranter->expects($this->never())->method('grantAccordPoints');
        $this->codexUnlockService->expects($this->once())->method('unlockByTrigger')->willReturn(0);

        $result = $this->service->read($player, $materia);

        self::assertSame(0, $result['accordPoints']);
        self::assertNull($result['accordDomain']);
    }

    /**
     * Chaque flux lisible a sa page : les huit elements (hors `none`) ont une
     * entree de Codex `materia_read`. Sans elle, la premiere lecture d'un flux
     * n'inscrirait rien — un silence, jamais une erreur.
     */
    public function testEveryElementHasItsReadingCodexEntry(): void
    {
        $fixtures = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/CodexEntryFixtures.php');

        foreach (Element::cases() as $element) {
            if (Element::None === $element) {
                continue;
            }
            self::assertSame(1, preg_match(
                sprintf("/'unlockType' => CodexEntry::UNLOCK_MATERIA_READ,\n\\s+'unlockKey' => '%s',/", $element->value),
                $fixtures,
            ), sprintf('Le flux "%s" n\'a pas de page de lecture au Codex.', $element->value));
        }
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

    private function materia(int $price, int $level, bool $socketed = false, string $spellSlug = 'fireball'): PlayerItem&MockObject
    {
        $spell = $this->createMock(Spell::class);
        $spell->method('getSlug')->willReturn($spellSlug);

        $item = $this->createMock(Item::class);
        $item->method('getPrice')->willReturn($price);
        $item->method('getLevel')->willReturn($level);
        $item->method('getElement')->willReturn(Element::Fire);
        $item->method('getSpell')->willReturn($spell);

        $materia = $this->createMock(PlayerItem::class);
        $materia->method('isMateria')->willReturn(true);
        $materia->method('getGenericItem')->willReturn($item);
        $materia->method('getSlotSet')->willReturn($socketed ? $this->createMock(Slot::class) : null);

        return $materia;
    }

    private function domainTeaching(string $spellSlug, string $name): Domain
    {
        $skill = new Skill();
        $skill->setRequiredPoints(0);
        $skill->setActions(['materia' => ['unlock' => $spellSlug]]);

        $domain = new Domain();
        $domain->setTitle($name);
        $domain->addSkill($skill);

        return $domain;
    }

    /**
     * @param list<Domain> $domains
     */
    private function withDomains(array $domains): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn($domains);
        $this->entityManager->method('getRepository')->with(Domain::class)->willReturn($repository);
    }
}
