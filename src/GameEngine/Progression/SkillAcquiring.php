<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\GameEngine\Player\PlayerActionHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use Doctrine\ORM\EntityManagerInterface;

class SkillAcquiring
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerSkillHelper $skillHelper,
        private readonly CrossDomainSkillResolver $crossDomainSkillResolver,
        private readonly PlayerActionHelper $playerActionHelper,
    ) {
    }

    /**
     * Apprend une competence, ou dit pourquoi elle ne peut pas l'etre.
     *
     * Retournait `void` : un refus etait silencieux, et l'appelant felicitait le
     * joueur pour une acquisition qui n'avait pas eu lieu.
     */
    public function acquireSkill(Skill $skill): SkillAcquisitionResult
    {
        $refusal = $this->skillHelper->refusalFor($skill);
        if (null !== $refusal) {
            return SkillAcquisitionResult::refused($refusal);
        }

        $player = $this->playerHelper->getPlayer();
        $player->addSkill($skill);

        // XP 100% à chaque domaine de la compétence
        $this->crossDomainSkillResolver->grantXpToAllDomains($player, $skill);

        // ARC-20b — **le Socle remplace `Skill::life`.** Le bonus plat etait
        // cumulatif et ecrit en dur dans `Player::maxLife` : plat, il est
        // ineequilibrable (la lecon d'ARC-03a) ; cumulatif, il donnerait
        // **+3 200 PV** au joueur qui a mene les 32 arbres. La barre se lit
        // desormais comme un **maximum** (`VitalityTier`), et un nœud de Socle
        // n'ecrit rien — il ouvre un palier.
        //
        // On remonte la barre au moment ou le palier change, en conservant les
        // degats deja subis : *apprendre ne soigne pas*, mais cela ne doit pas
        // non plus laisser un personnage au-dessus de son propre maximum.
        $this->raiseVitality($player);

        // Synchroniser les emplacements d'outils débloqués
        $this->playerActionHelper->syncToolSlots();

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return SkillAcquisitionResult::acquired();
    }

    /**
     * Relever la barre au palier atteint, sans soigner (ARC-20b).
     *
     * ***Apprendre ne soigne pas*** : les degats deja subis restent subis, et
     * seul le maximum monte. La vie courante ne suit que d'autant que la barre
     * a grandi — ce qui evite l'autre defaut, un personnage laisse au-dessus de
     * son propre maximum quand le maximum, lui, ne bouge pas.
     */
    private function raiseVitality(Player $player): void
    {
        $bar = VitalityTier::barOf($player);
        $gain = max(0, $bar - $player->getMaxLife());

        if ($gain === 0) {
            $player->setLife(min($player->getLife(), $bar));

            return;
        }

        $player->setMaxLife($bar);
        $player->setLife(min($bar, $player->getLife() + $gain));
    }
}
