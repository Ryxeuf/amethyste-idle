<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\DomainAccessManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

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
        $this->manager = new DomainAccessManager($this->createMock(EntityManagerInterface::class));
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
     * @param Domain[] $domains
     */
    private function skill(array $domains): Skill
    {
        $skill = new Skill();
        $skill->setSlug('node');
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
