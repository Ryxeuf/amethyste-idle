<?php

namespace App\Service\Skill;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\BuildPresetManager;
use App\GameEngine\Progression\DomainAccessManager;
use App\GameEngine\Progression\SkillRespecManager;
use App\Helper\PlayerDomainHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;

/**
 * Construit le payload JSON des arbres de talent pour /api/v1/skills
 * (migration API-first, phase 3.1). Lecture seule : reprend les donnees
 * de l'ecran Twig game/skills (domaines, competences, respec, presets).
 */
class SkillTreePayloadBuilder
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerDomainHelper $playerDomainHelper,
        private readonly PlayerSkillHelper $skillHelper,
        private readonly SkillRespecManager $respecManager,
        private readonly BuildPresetManager $presetManager,
        private readonly DomainAccessManager $accessManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(?string $locale = null): array
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            throw new \LogicException('Aucun joueur courant pour construire le payload des arbres de talent.');
        }

        $buildStats = ['damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0, 'count' => 0];

        // ONB-09 — la meme regle que l'ecran Twig, et pour la meme raison :
        // aucun nœud d'un arbre ferme n'est expose, y compris en JSON. Un
        // gardien pose seulement sur le gabarit laisserait la porte de derriere
        // grande ouverte.
        $domains = [];
        foreach ($this->accessManager->openedDomains($player) as $domain) {
            $domains[] = $this->buildDomain($domain, $locale, $buildStats);
        }

        $presets = [];
        foreach ($this->presetManager->getPresets($player) as $preset) {
            $presets[] = [
                'id' => $preset->getId(),
                'name' => $preset->getName(),
                'skillSlugs' => $preset->getSkillSlugs(),
            ];
        }

        return [
            'domains' => $domains,
            'buildStats' => $buildStats,
            'respec' => [
                'cost' => $this->respecManager->getRespecCost($player),
                'canRespec' => $this->respecManager->canRespec($player),
            ],
            'points' => [
                'skillCount' => $player->getSkills()->count(),
                'totalUsedPoints' => $this->skillHelper->getTotalUsedPoints($player),
                'maxTotalPoints' => PlayerSkillHelper::MAX_TOTAL_SKILL_POINTS,
            ],
            'presets' => [
                'items' => $presets,
                'canSave' => $this->presetManager->canSave($player),
                'max' => BuildPresetManager::MAX_PRESETS_PER_PLAYER,
            ],
            'playerGils' => $player->getGils(),
        ];
    }

    /**
     * @param array<string, int> $buildStats
     *
     * @return array<string, mixed>
     */
    private function buildDomain(Domain $domain, ?string $locale, array &$buildStats): array
    {
        $domainExperience = $this->playerDomainHelper->getDomainExperience($domain);

        $skills = [];
        foreach ($domain->getSkills() as $skill) {
            $entry = $this->serializeSkill($skill, $locale);

            if ($entry['acquired']) {
                $buildStats['damage'] += (int) $entry['damage'];
                $buildStats['heal'] += (int) $entry['heal'];
                $buildStats['hit'] += (int) $entry['hit'];
                $buildStats['critical'] += (int) $entry['critical'];
                $buildStats['life'] += (int) $entry['life'];
                ++$buildStats['count'];
            }

            $skills[] = $entry;
        }

        return [
            'id' => $domain->getId(),
            'slug' => $domain->getSlug(),
            'title' => $domain->getLocalizedTitle($locale),
            'element' => $domain->getElement(),
            'availableExperience' => $domainExperience?->getAvailableExperience() ?? 0,
            'totalExperience' => $domainExperience?->getTotalExperience() ?? 0,
            'damage' => $domainExperience?->getDamage() ?? 0,
            'hit' => $domainExperience?->getHit() ?? 0,
            'critical' => $domainExperience?->getCritical() ?? 0,
            'skills' => $skills,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSkill(Skill $skill, ?string $locale): array
    {
        $requirementIds = [];
        foreach ($skill->getRequirements() as $requirement) {
            $requirementIds[] = $requirement->getId();
        }

        return [
            'id' => $skill->getId(),
            'slug' => $skill->getSlug(),
            'title' => $skill->getLocalizedTitle($locale),
            'description' => $skill->getDescription(),
            'requiredPoints' => $skill->getRequiredPoints(),
            'damage' => $skill->getDamage(),
            'heal' => $skill->getHeal(),
            'hit' => $skill->getHit(),
            'critical' => $skill->getCritical(),
            'life' => $skill->getLife(),
            'actions' => $skill->getActions(),
            'requirementIds' => $requirementIds,
            'acquired' => $this->skillHelper->hasSkill($skill),
            'canBeAcquired' => $this->skillHelper->canAcquireSkill($skill),
        ];
    }
}
