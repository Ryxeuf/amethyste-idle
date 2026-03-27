<?php

namespace App\Controller\Game\Skill;

use App\Dto\Domain\DomainModel;
use App\Dto\Domain\PlayerDomain;
use App\Entity\Game\Domain;
use App\GameEngine\Progression\BuildPresetManager;
use App\GameEngine\Progression\SkillRespecManager;
use App\Helper\PlayerDomainHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use App\Transformer\PlayerSkillTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/skills', name: 'app_game_skills')]
class IndexController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerDomainHelper $playerDomainHelper,
        private readonly PlayerSkillTransformer $playerSkillDataTransformer,
        private readonly PlayerSkillHelper $skillHelper,
        private readonly PlayerHelper $playerHelper,
        private readonly SkillRespecManager $respecManager,
        private readonly BuildPresetManager $presetManager,
    ) {
    }

    public function __invoke(): Response
    {
        $domains = $this->entityManager->getRepository(Domain::class)->findAll();
        $domains = $this->playerDomainHelper->getDomains();

        $domainsModels = array_map($this->transformDomain(...), $domains);

        $player = $this->playerHelper->getPlayer();

        return $this->render('game/skills/index.html.twig', [
            'domains' => $domainsModels,
            'respecCost' => $player ? $this->respecManager->getRespecCost($player) : 0,
            'canRespec' => $player ? $this->respecManager->canRespec($player) : false,
            'playerGils' => $player ? $player->getGils() : 0,
            'skillCount' => $player ? $player->getSkills()->count() : 0,
            'totalUsedPoints' => $player ? $this->skillHelper->getTotalUsedPoints($player) : 0,
            'maxTotalPoints' => PlayerSkillHelper::MAX_TOTAL_SKILL_POINTS,
            'presets' => $player ? $this->presetManager->getPresets($player) : [],
            'canSavePreset' => $player ? $this->presetManager->canSave($player) : false,
            'maxPresets' => BuildPresetManager::MAX_PRESETS_PER_PLAYER,
        ]);
    }

    private function transformDomain(Domain $domain): DomainModel
    {
        $output = new PlayerDomain($domain);
        $domainExperience = $this->playerDomainHelper->getDomainExperience($domain);

        if ($domainExperience !== null) {
            foreach ($domainExperience->getDomain()->getSkills() as $skill) {
                if ($playerSkillOutput = $this->playerSkillDataTransformer->transform($skill)) {
                    $playerSkillOutput->acquired = $this->skillHelper->hasSkill($skill);
                    $playerSkillOutput->canBeAcquired = $this->skillHelper->canAcquireSkill($skill);

                    $output->skills[] = $playerSkillOutput;
                }
            }

            $output->availableExperience = $domainExperience->getAvailableExperience();
            $output->totalExperience = $domainExperience->getTotalExperience();
            $output->damage = $domainExperience->getDamage();
            $output->hit = $domainExperience->getHit();
            $output->critical = $domainExperience->getCritical();
        }

        return $output;
    }
}
