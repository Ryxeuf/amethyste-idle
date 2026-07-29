<?php

namespace App\Tests\Unit\GameEngine\Crafting;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Enum\CraftSpecialization;
use App\GameEngine\Crafting\CraftBranchCatalog;
use App\GameEngine\Crafting\CraftSpecializationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Le choix d'une branche, arbre par arbre (DOM-04).
 *
 * **Ce que ce jalon a defait.** Le modele livre portait une specialisation
 * unique pour tout le personnage, et irreversible : devenir Forgeron fermait a
 * jamais la maitrise du Tanneur. C'est l'exclusivite *entre* arbres, que la
 * doctrine interdit — « interdire un arbre serait interdire un geste »
 * (GAME_DOMAINS § 1).
 *
 * Les deux invariants qui comptent sont symetriques, et il faut les deux : rien
 * n'empeche de se specialiser dans plusieurs metiers, rien ne permet de prendre
 * deux branches du meme.
 */
class CraftSpecializationServiceTest extends TestCase
{
    private const RESPEC_COST = 2500;

    public function testEveryCraftTreeCanBeSpecializedIn(): void
    {
        self::assertCount(7, $this->service()->getAvailableSpecializations());
    }

    /**
     * Rien n'empeche de se specialiser dans plusieurs metiers.
     */
    public function testSpecializingInOneTreeDoesNotCloseTheOthers(): void
    {
        $service = $this->service();
        $player = $this->player(['Forgeron' => 500, 'Tanneur' => 500]);

        self::assertTrue($service->choose($player, CraftSpecialization::Forgeron, 'weapons')['success']);
        self::assertTrue(
            $service->canChoose($player, CraftSpecialization::Tanneur)['ok'],
            'Se specialiser chez le forgeron a ferme l\'arbre du tanneur : c\'est l\'exclusivite entre arbres que la doctrine interdit.',
        );
        self::assertTrue($service->choose($player, CraftSpecialization::Tanneur, 'armour')['success']);

        self::assertTrue($player->isSpecializedIn('forgeron'));
        self::assertTrue($player->isSpecializedIn('tanneur'));
    }

    /**
     * Mais on ne prend qu'une branche par arbre.
     */
    public function testASecondBranchInTheSameTreeIsRefused(): void
    {
        $service = $this->service();
        $player = $this->player(['Forgeron' => 500]);

        $service->choose($player, CraftSpecialization::Forgeron, 'weapons');
        $second = $service->choose($player, CraftSpecialization::Forgeron, 'armour');

        self::assertFalse($second['success']);
        self::assertStringContainsString('respec', $second['message']);
        self::assertCount(1, $player->getCraftSpecializations());
    }

    /**
     * Le seuil se lit **dans l'arbre concerne**.
     *
     * Il l'etait sur le meilleur des quatre : un joueur qui atteignait le seuil
     * chez le forgeron pouvait se declarer alchimiste sans avoir jamais touche a
     * un mortier.
     */
    public function testTheThresholdIsReadInTheTreeBeingSpecialized(): void
    {
        $service = $this->service();
        $player = $this->player(['Forgeron' => 900, 'Alchimiste' => 10]);

        self::assertTrue($service->canChoose($player, CraftSpecialization::Forgeron)['ok']);
        self::assertFalse($service->canChoose($player, CraftSpecialization::Alchimiste)['ok']);
    }

    public function testXpInANonCraftDomainUnlocksNothing(): void
    {
        $service = $this->service();

        self::assertFalse($service->canChoose($this->player(['Guerrier' => 1000]), CraftSpecialization::Forgeron)['ok']);
    }

    public function testAnUnknownBranchIsRefused(): void
    {
        $service = $this->service();
        $player = $this->player(['Forgeron' => 500]);

        self::assertFalse($service->choose($player, CraftSpecialization::Forgeron, 'cuisine')['success']);
        self::assertCount(0, $player->getCraftSpecializations());
    }

    public function testTheQualityBonusFollowsTheTreeAndNotTheBranch(): void
    {
        $service = $this->service();
        $player = $this->player(['Joaillier' => 500]);
        $service->choose($player, CraftSpecialization::Joaillier, 'focus');

        self::assertSame(CraftSpecializationService::QUALITY_BONUS_CHANCE, $service->getQualityBonusFor($player, 'joaillier'));
        self::assertSame(0, $service->getQualityBonusFor($player, 'forgeron'));
    }

    // =====================================================================
    // Le respec : le seul du jeu qui se paie
    // =====================================================================

    public function testChangingBranchCostsGils(): void
    {
        $service = $this->service();
        $player = $this->player(['Forgeron' => 500]);
        $player->setGils(4000);
        $service->choose($player, CraftSpecialization::Forgeron, 'weapons');

        $result = $service->respec($player, CraftSpecialization::Forgeron, 'armour');

        self::assertTrue($result['success']);
        self::assertSame(4000 - self::RESPEC_COST, $player->getGils());
        self::assertSame('armour', $player->getCraftSpecializationFor('forgeron')?->getBranch());
    }

    /**
     * Le refus dit le prix.
     *
     * Un « impossible » sans chiffre laisserait croire a un verrou, alors que
     * c'est une depense.
     */
    public function testTheRefusalNamesWhatIsMissing(): void
    {
        $service = $this->service();
        $player = $this->player(['Forgeron' => 500]);
        $player->setGils(100);
        $service->choose($player, CraftSpecialization::Forgeron, 'weapons');

        $result = $service->respec($player, CraftSpecialization::Forgeron, 'armour');

        self::assertFalse($result['success']);
        self::assertStringContainsString((string) (self::RESPEC_COST - 100), $result['message']);
        self::assertSame(100, $player->getGils(), 'Les gils ont ete preleves malgre le refus.');
        self::assertSame('weapons', $player->getCraftSpecializationFor('forgeron')?->getBranch());
    }

    public function testRespeccingToTheSameBranchCostsNothingAndChangesNothing(): void
    {
        $service = $this->service();
        $player = $this->player(['Forgeron' => 500]);
        $player->setGils(4000);
        $service->choose($player, CraftSpecialization::Forgeron, 'weapons');

        $result = $service->respec($player, CraftSpecialization::Forgeron, 'weapons');

        self::assertFalse($result['success']);
        self::assertSame(4000, $player->getGils());
    }

    public function testRespeccingATreeWithoutABranchIsRefused(): void
    {
        self::assertFalse($this->service()->respec($this->player([]), CraftSpecialization::Forgeron, 'weapons')['success']);
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

    private function service(): CraftSpecializationService
    {
        return new CraftSpecializationService(
            $this->createMock(EntityManagerInterface::class),
            new CraftBranchCatalog(\dirname(__DIR__, 4)),
            self::RESPEC_COST,
        );
    }

    /**
     * @param array<string, int> $domainXp titre de domaine => XP totale
     */
    private function player(array $domainXp): Player
    {
        $player = new Player();

        $experiences = new ArrayCollection();
        foreach ($domainXp as $title => $xp) {
            $domain = new Domain();
            $domain->setTitle($title);

            $experience = new DomainExperience();
            $experience->setDomain($domain);
            $experience->setTotalExperience($xp);

            $experiences->add($experience);
        }

        $property = new \ReflectionProperty(Player::class, 'domainExperiences');
        $property->setAccessible(true);
        $property->setValue($player, $experiences);

        return $player;
    }
}
