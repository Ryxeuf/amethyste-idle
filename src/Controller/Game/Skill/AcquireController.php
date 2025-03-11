<?php

namespace App\Controller\Game\Skill;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/skills/acquire', name: 'app_game_skill_acquire', methods: ['POST'])]
class AcquireController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    public function __invoke(Request $request): Response
    {
        $skillId = $request->request->get('skill_id');
        $domainId = $request->request->get('domain_id');
        
        $skill = $this->entityManager->getRepository(Skill::class)->find($skillId);
        $domain = $this->entityManager->getRepository(Domain::class)->find($domainId);
        
        if (!$skill || !$domain) {
            $this->addFlash('error', 'Compétence ou domaine non trouvé');
            return $this->redirectToRoute('app_game_skills');
        }
        
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a suffisamment d'expérience dans le domaine
        if ($domain->getAvailableExperience() < $skill->getRequiredPoints()) {
            $this->addFlash('error', 'Vous n\'avez pas assez d\'expérience pour acquérir cette compétence');
            return $this->redirectToRoute('app_game_domain_info', ['id' => $domainId]);
        }
        
        // Vérifier si toutes les compétences requises sont acquises
        foreach ($skill->getRequirements() as $requirement) {
            if (!$requirement->isAcquired()) {
                $this->addFlash('error', 'Vous devez d\'abord acquérir toutes les compétences requises');
                return $this->redirectToRoute('app_game_domain_info', ['id' => $domainId]);
            }
        }
        
        // Acquérir la compétence
        $skill->setAcquired(true);
        
        // Réduire l'expérience disponible
        $domain->setAvailableExperience($domain->getAvailableExperience() - $skill->getRequiredPoints());
        
        // Appliquer les bonus de la compétence au joueur si nécessaire
        // ...
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Compétence acquise avec succès !');
        
        return $this->redirectToRoute('app_game_domain_info', ['id' => $domainId]);
    }
} 