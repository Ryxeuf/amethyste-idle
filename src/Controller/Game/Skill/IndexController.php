<?php

namespace App\Controller\Game\Skill;

use App\Entity\Game\Domain;
use App\Helper\PlayerDomainHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Dto\Domain\DomainModel;
use App\Dto\Domain\PlayerDomain;
use App\Transformer\PlayerSkillTransformer;
use App\Helper\PlayerSkillHelper;

#[Route('/game/skills', name: 'app_game_skills')]
class IndexController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly PlayerDomainHelper $playerDomainHelper, private readonly PlayerSkillTransformer $playerSkillDataTransformer, private readonly PlayerSkillHelper $skillHelper)
    {
    }
    
    public function __invoke(): Response
    {
        $domains = $this->entityManager->getRepository(Domain::class)->findAll();
        $domains = $this->playerDomainHelper->getDomains();

        $domainsModels = array_map($this->transformDomain(...), $domains);
        
        return $this->render('game/skills/index.html.twig', [
            'domains' => $domainsModels,
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