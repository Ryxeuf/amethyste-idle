<?php

namespace App\Tests\Unit\Helper;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\DomainAccessManager;
use App\GameEngine\Progression\PortAccessDiscount;
use App\Helper\PlayerDomainHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlayerSkillHelperTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private PlayerDomainHelper&MockObject $playerDomainHelper;
    private DomainAccessManager&MockObject $domainAccessManager;
    private PlayerSkillHelper $helper;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->playerDomainHelper = $this->createMock(PlayerDomainHelper::class);
        // ONB-08 : par defaut l'arbre est ouvert. Ces cas parlent des points,
        // des prerequis — pas de la porte d'entree, qui a son
        // propre test plus bas.
        $this->domainAccessManager = $this->createMock(DomainAccessManager::class);
        $this->domainAccessManager->method('isSkillReachable')->willReturn(true);
        $this->helper = new PlayerSkillHelper($this->playerHelper, $this->playerDomainHelper, $this->domainAccessManager, $this->fullPriceDiscount());
    }

    /**
     * ONB-08 — un arbre ferme n'accorde aucun nœud.
     *
     * Le refus passe **avant** le compte des points : repondre « pas assez
     * d'experience » a quelqu'un qui n'est pas entre dans l'arbre l'enverrait
     * chercher des points qu'il a deja.
     */
    public function testAClosedTreeGrantsNoNode(): void
    {
        $domain = $this->createDomain(1);
        $skill = $this->createSkill('fireball', 0, [$domain]);

        $player = $this->createPlayerWithUsedExperience([0]);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerDomainHelper->method('getAvailableDomainExperience')->willReturn(1000);

        $closed = $this->createMock(DomainAccessManager::class);
        $closed->method('isSkillReachable')->willReturn(false);
        $helper = new PlayerSkillHelper($this->playerHelper, $this->playerDomainHelper, $closed, $this->fullPriceDiscount());

        $this->assertSame(PlayerSkillHelper::REFUSAL_DOMAIN_CLOSED, $helper->refusalFor($skill));
        $this->assertFalse($helper->canAcquireSkill($skill));
    }

    public function testGetTotalUsedPointsSumsAllDomains(): void
    {
        $player = $this->createPlayerWithUsedExperience([100, 150, 50]);

        $this->assertSame(300, $this->helper->getTotalUsedPoints($player));
    }

    public function testGetTotalUsedPointsReturnsZeroWithNoDomains(): void
    {
        $player = $this->createPlayerWithUsedExperience([]);

        $this->assertSame(0, $this->helper->getTotalUsedPoints($player));
    }

    public function testCanAcquireSkillWithEnoughDomainExperience(): void
    {
        $domain = $this->createDomain(1);
        $skill = $this->createSkill('fireball', 50, [$domain]);

        // Ce qui decide, c'est l'experience disponible **dans le domaine** du
        // nœud (100 pour 50 demandes) — jamais un total (ARC-10).
        $player = $this->createPlayerWithUsedExperience([200, 200]);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerDomainHelper->method('getAvailableDomainExperience')->willReturn(100);

        $this->assertTrue($this->helper->canAcquireSkill($skill));
    }

    public function testAlreadyHeavilyInvestedPlayerKeepsLearning(): void
    {
        $domain = $this->createDomain(1);
        $skill = $this->createSkill('fireball', 50, [$domain]);

        // 450 points deja investis ailleurs : sans plafond global, cela ne
        // pese sur rien (ARC-10).
        $player = $this->createPlayerWithUsedExperience([250, 200]);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerDomainHelper->method('getAvailableDomainExperience')->willReturn(100);

        $this->assertTrue($this->helper->canAcquireSkill($skill));
    }

    public function testCanAcquireSkillAlreadyAcquiredReturnsFalse(): void
    {
        $domain = $this->createDomain(1);
        $skill = $this->createSkill('fireball', 10, [$domain]);

        $player = $this->createPlayerWithUsedExperience([0], [$skill]);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->assertFalse($this->helper->canAcquireSkill($skill));
        $this->assertSame(PlayerSkillHelper::REFUSAL_ALREADY_ACQUIRED, $this->helper->refusalFor($skill));
    }

    /**
     * Les prerequis etaient compares par `array_intersect` sur des entites, donc
     * **par leur titre**. Deux competences homonymes issues d'arbres differents
     * comptaient pour deux correspondances d'un meme prerequis, l'egalite des
     * cardinalites devenait fausse, et la competence restait bloquee — sans
     * qu'aucun message ne le dise.
     */
    public function testRequirementIsMetDespiteHomonymousSkillsInOtherTrees(): void
    {
        $domain = $this->createDomain(1);
        $prerequisite = $this->createSkill('concentration-soin', 0, [$domain], 'Concentration');
        $homonym = $this->createSkill('concentration-feu', 0, [$this->createDomain(2)], 'Concentration');

        $skill = $this->createSkill('main-guerisseuse', 10, [$domain]);
        $skill->addRequirement($prerequisite);

        $player = $this->createPlayerWithUsedExperience([0], [$prerequisite, $homonym]);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerDomainHelper->method('getAvailableDomainExperience')->willReturn(100);

        $this->assertNull($this->helper->refusalFor($skill));
    }

    public function testMissingRequirementIsReported(): void
    {
        $domain = $this->createDomain(1);
        $prerequisite = $this->createSkill('soin-mineur', 0, [$domain]);

        $skill = $this->createSkill('main-guerisseuse', 10, [$domain]);
        $skill->addRequirement($prerequisite);

        $player = $this->createPlayerWithUsedExperience([0]);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerDomainHelper->method('getAvailableDomainExperience')->willReturn(100);

        $this->assertSame(PlayerSkillHelper::REFUSAL_MISSING_REQUIREMENTS, $this->helper->refusalFor($skill));
    }

    public function testNotEnoughExperienceIsReported(): void
    {
        $domain = $this->createDomain(1);
        $skill = $this->createSkill('fireball', 50, [$domain]);

        $player = $this->createPlayerWithUsedExperience([0]);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerDomainHelper->method('getAvailableDomainExperience')->willReturn(10);

        $this->assertSame(PlayerSkillHelper::REFUSAL_NOT_ENOUGH_XP, $this->helper->refusalFor($skill));
    }

    /**
     * Une competence gratuite sans domaine rattache n'entrait dans aucune
     * iteration : elle etait refusee faute d'avoir pu prouver quoi que ce soit.
     */
    public function testFreeSkillWithoutDomainIsAcquirable(): void
    {
        $skill = $this->createSkill('don-inne', 0, []);

        $player = $this->createPlayerWithUsedExperience([0]);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->assertNull($this->helper->refusalFor($skill));
    }

    public function testNoActivePlayerIsReported(): void
    {
        $skill = $this->createSkill('fireball', 0, []);
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $this->assertSame(PlayerSkillHelper::REFUSAL_NO_PLAYER, $this->helper->refusalFor($skill));
        $this->assertFalse($this->helper->canAcquireSkill($skill));
    }

    /**
     * ARC-10 — aucun refus ne depend d'un total tous domaines confondus.
     *
     * Le plafond de 500 points contredisait la premiere ligne de la doctrine
     * (« le savoir n'est jamais borne », GAME_DOMAINS § 1), et la mesure l'a
     * rendu intenable : un seul arbre en consommait 465. Le test verifie ce
     * qui compte — un joueur qui a deja beaucoup investi ailleurs apprend
     * quand meme, des lors qu'il a l'experience **dans le domaine du nœud**.
     */
    public function testNoRefusalDependsOnATotalAcrossDomains(): void
    {
        $domain = $this->createDomain(1);
        $skill = $this->createSkill('fireball', 10, [$domain]);

        // Un personnage qui a deja investi 900 points ailleurs — bien au-dela
        // de l'ancien plafond — et qui a l'experience voulue dans ce domaine.
        $player = $this->createPlayerWithUsedExperience([500, 400]);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerDomainHelper->method('getAvailableDomainExperience')->willReturn(100);

        $this->assertNull($this->helper->refusalFor($skill), 'Le total tous domaines confondus ne refuse plus rien.');
        $this->assertTrue($this->helper->canAcquireSkill($skill));
    }

    /**
     * Le motif de refus lui-meme a disparu : une constante laissee derriere
     * finirait par etre relue, et sa cle de traduction par etre reaffichee.
     */
    public function testTheGlobalCapRefusalNoLongerExists(): void
    {
        $this->assertFalse(
            \defined(PlayerSkillHelper::class . '::MAX_TOTAL_SKILL_POINTS'),
            'Le plafond global est supprime (ARC-10).',
        );
        $this->assertFalse(\defined(PlayerSkillHelper::class . '::REFUSAL_GLOBAL_CAP'));

        foreach (['fr', 'en'] as $locale) {
            $catalog = (string) file_get_contents(\dirname(__DIR__, 3) . '/translations/messages.' . $locale . '.json');
            $this->assertStringNotContainsString('global_cap', $catalog);
        }
    }

    /**
     * @param int[]   $usedExperiences
     * @param Skill[] $skills          competences deja acquises
     */
    private function createPlayerWithUsedExperience(array $usedExperiences, array $skills = []): Player&MockObject
    {
        $domainExps = new ArrayCollection();
        foreach ($usedExperiences as $i => $used) {
            $de = new DomainExperience();
            $de->setTotalExperience(1000);
            $de->setUsedExperience($used);
            $domain = $this->createDomain($i + 1);
            $de->setDomain($domain);
            $domainExps->add($de);
        }

        $owned = new ArrayCollection($skills);

        $player = $this->createMock(Player::class);
        $player->method('getDomainExperiences')->willReturn($domainExps);
        $player->method('getSkills')->willReturn($owned);
        // Le double reproduit le contrat de l'entite : possession par identifiant,
        // avec repli sur l'identite pour une competence non persistee.
        $player->method('hasSkill')->willReturnCallback(
            static function (Skill $needle) use ($owned): bool {
                foreach ($owned as $skill) {
                    if ($skill === $needle) {
                        return true;
                    }
                }

                return false;
            },
        );

        return $player;
    }

    /**
     * @param Domain[] $domains
     */
    private function createSkill(string $slug, int $requiredPoints, array $domains, ?string $title = null): Skill
    {
        $skill = new Skill();
        $skill->setSlug($slug);
        $skill->setTitle($title ?? "Skill $slug");
        $skill->setDescription('Description');
        $skill->setRequiredPoints($requiredPoints);
        $skill->setDamage(0);
        $skill->setHeal(0);
        $skill->setHit(0);
        $skill->setCritical(0);
        $skill->setLife(0);

        foreach ($domains as $domain) {
            $skill->addDomain($domain);
        }

        return $skill;
    }

    private function createDomain(int $id): Domain
    {
        $domain = new Domain();
        $domain->setTitle("Domain $id");
        $domain->setRandomSeed(1);
        $domain->setGraphHeight(5);

        $reflection = new \ReflectionClass($domain);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($domain, $id);

        return $domain;
    }

    /**
     * ARC-16b : ces tests ne parlent pas d'accointance — le cout effectif est
     * le cout nominal.
     */
    private function fullPriceDiscount(): PortAccessDiscount
    {
        $discount = $this->createMock(PortAccessDiscount::class);
        $discount->method('effectiveRequiredPointsOf')->willReturnCallback(
            fn ($player, $skill) => $skill->getRequiredPoints(),
        );

        return $discount;
    }
}
