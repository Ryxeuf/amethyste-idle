<?php

namespace App\Controller\Game\Skill;

use App\Entity\Game\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/skills/domain/{id}', name: 'app_game_domain_info')]
class DomainInfoController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(int $id): Response
    {
        $domain = $this->entityManager->getRepository(Domain::class)->find($id);

        if (!$domain) {
            throw $this->createNotFoundException('Domaine non trouvé');
        }

        return $this->render('game/skills/domain_info.html.twig', [
            'domain' => $domain,
        ]);
    }
}
