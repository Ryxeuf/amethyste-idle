<?php

namespace App\Controller\Game;

use App\Entity\Game\Item;
use App\Helper\PlayerHelper;
use App\Repository\PlayerResourceCatalogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ResourceCatalogController extends AbstractController
{
    private const RESOURCE_TYPES = ['herb', 'ore', 'plant', 'resource'];

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerResourceCatalogRepository $catalogRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/game/catalog', name: 'app_game_catalog', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $entries = $this->catalogRepository->findByPlayer($player);
        $totalResources = $this->entityManager->getRepository(Item::class)
            ->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.type IN (:types)')
            ->setParameter('types', self::RESOURCE_TYPES)
            ->getQuery()
            ->getSingleScalarResult();
        $totalCollected = $this->catalogRepository->getTotalCollected($player);

        return $this->render('game/catalog/index.html.twig', [
            'player' => $player,
            'entries' => $entries,
            'totalResources' => (int) $totalResources,
            'discoveredCount' => \count($entries),
            'totalCollected' => $totalCollected,
        ]);
    }
}
