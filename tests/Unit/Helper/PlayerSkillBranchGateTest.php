<?php

namespace App\Tests\Unit\Helper;

use App\Entity\App\Player;
use App\Entity\App\PlayerCraftSpecialization;
use App\Entity\Game\Skill;
use App\Enum\CraftSpecialization;
use App\GameEngine\Progression\DomainAccessManager;
use App\Helper\PlayerDomainHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Un nœud terminal appartient a une branche (DOM-06).
 *
 * C'est ici que le choix pris a l'etabli (DOM-04) devient visible **dans
 * l'arbre**. Sans ce garde, la specialisation resterait un bonus de qualite dont
 * rien, dans ce que le joueur apprend, ne porterait la trace — et les nœuds de
 * branche s'apprendraient tous les deux, ce qui viderait le renoncement.
 *
 * Le refus a une propriete que les quatre autres n'ont pas : **il ne se leve pas
 * en jouant**. Il se leve en renoncant — par le respec de branche, qui se paie.
 * Le motif doit donc le dire, sinon le joueur cherchera un prerequis inexistant.
 */
class PlayerSkillBranchGateTest extends TestCase
{
    public function testANodeOfTheChosenBranchIsLearnable(): void
    {
        $player = $this->playerWithBranch('cuisinier', 'feast');

        self::assertNull($this->helper($player)->refusalFor($this->branchNode('cuisinier', 'feast')));
    }

    public function testANodeOfTheOtherBranchIsRefused(): void
    {
        $player = $this->playerWithBranch('cuisinier', 'feast');

        self::assertSame(
            PlayerSkillHelper::REFUSAL_OTHER_BRANCH,
            $this->helper($player)->refusalFor($this->branchNode('cuisinier', 'provisions')),
        );
    }

    /**
     * Sans branche prise, aucun des deux nœuds terminaux n'est accessible.
     *
     * Les laisser passer reviendrait a offrir les deux a qui ne choisit pas —
     * exactement l'inverse de ce qu'une branche terminale signifie.
     */
    public function testWithoutABranchNeitherTerminalNodeOpens(): void
    {
        $helper = $this->helper($this->playerWithBranch(null, null));

        self::assertSame(PlayerSkillHelper::REFUSAL_OTHER_BRANCH, $helper->refusalFor($this->branchNode('cuisinier', 'feast')));
        self::assertSame(PlayerSkillHelper::REFUSAL_OTHER_BRANCH, $helper->refusalFor($this->branchNode('cuisinier', 'provisions')));
    }

    /**
     * La branche d'un autre arbre ne debloque rien ici.
     */
    public function testABranchTakenInAnotherTreeDoesNotOpenThisOne(): void
    {
        $player = $this->playerWithBranch('tailleur', 'workwear');

        self::assertSame(
            PlayerSkillHelper::REFUSAL_OTHER_BRANCH,
            $this->helper($player)->refusalFor($this->branchNode('cuisinier', 'feast')),
        );
    }

    /**
     * L'immense majorite de l'arbre reste commune.
     *
     * Un nœud sans declaration de branche appartient a tout le monde : seule la
     * poignee de nœuds terminaux se choisit.
     */
    public function testANodeWithoutABranchBelongsToEveryone(): void
    {
        $node = $this->createMock(Skill::class);
        $node->method('getActions')->willReturn([['action' => 'craft', 'recipes' => ['recipe-bread']]]);
        $node->method('getRequiredPoints')->willReturn(0);
        $node->method('getDomains')->willReturn(new ArrayCollection([]));
        $node->method('getRequirements')->willReturn(new ArrayCollection([]));

        self::assertNull($this->helper($this->playerWithBranch(null, null))->refusalFor($node));
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

    private function helper(Player $player): PlayerSkillHelper
    {
        $playerHelper = $this->createMock(PlayerHelper::class);
        $playerHelper->method('getPlayer')->willReturn($player);

        // ONB-08 : ce test isole la borne de branche. L'ouverture d'arbre est
        // donc accordee, sans quoi chaque cas echouerait sur `domain_closed`
        // avant d'atteindre ce qu'il verifie.
        $domainAccess = $this->createMock(DomainAccessManager::class);
        $domainAccess->method('isSkillReachable')->willReturn(true);

        return new PlayerSkillHelper($playerHelper, $this->createMock(PlayerDomainHelper::class), $domainAccess);
    }

    private function branchNode(string $craft, string $branch): Skill&MockObject
    {
        $node = $this->createMock(Skill::class);
        $node->method('getActions')->willReturn([['action' => 'specialization.branch', 'craft' => $craft, 'branch' => $branch]]);
        // Gratuit : ce test isole la borne de branche, et un cout ferait echouer
        // sur le motif « pas assez d'experience » avant de l'atteindre.
        $node->method('getRequiredPoints')->willReturn(0);
        $node->method('getDomains')->willReturn(new ArrayCollection([]));
        $node->method('getRequirements')->willReturn(new ArrayCollection([]));

        return $node;
    }

    private function playerWithBranch(?string $craft, ?string $branch): Player
    {
        $player = new Player();

        if ($craft !== null && $branch !== null) {
            $player->addCraftSpecialization(
                new PlayerCraftSpecialization($player, CraftSpecialization::from($craft), $branch),
            );
        }

        return $player;
    }
}
