<?php

namespace App\Controller\Game;

use App\Entity\Game\Mount;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MountController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/game/mounts', name: 'app_game_mounts', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $mounts = $this->entityManager
            ->getRepository(Mount::class)
            ->findBy(['enabled' => true], ['requiredLevel' => 'ASC', 'gilCost' => 'ASC']);

        return $this->render('game/mount/index.html.twig', [
            'mounts' => $mounts,
            'obtentionLabels' => [
                Mount::OBTENTION_QUEST => 'game.mount.obtention.quest',
                Mount::OBTENTION_DROP => 'game.mount.obtention.drop',
                Mount::OBTENTION_PURCHASE => 'game.mount.obtention.purchase',
                Mount::OBTENTION_ACHIEVEMENT => 'game.mount.obtention.achievement',
            ],
        ]);
    }
}
