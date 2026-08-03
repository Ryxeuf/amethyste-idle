<?php

namespace App\Controller\Game;

use App\Entity\Game\Item;
use App\GameEngine\Progression\ResourceUsesReader;
use App\GameEngine\Race\RaceCapability;
use App\GameEngine\Race\RaceCapabilityResolver;
use App\Helper\PlayerHelper;
use App\Repository\PlayerResourceCatalogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ResourceCatalogController extends AbstractController
{
    // OBJ-01 : la taxonomie est alignee sur les 5 constantes du code — les
    // valeurs heritees (`herb`, `ore`, `plant`...) n'existent plus en donnees.
    private const RESOURCE_TYPES = [Item::TYPE_RESOURCE];

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerResourceCatalogRepository $catalogRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ResourceUsesReader $usesReader,
        private readonly RaceCapabilityResolver $capabilities,
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

        // ONB-07b — Humain, « Les usages » : la capacite **avance la lecture**
        // d'un palier que tout le monde finit par atteindre (25 recoltes). Elle
        // ne cree aucune donnee, ne change aucun rendement et n'ouvre aucun
        // contenu : le Nain qui recolte 25 fois voit exactement la meme chose.
        $hasTheUses = $this->capabilities->playerHas($player, RaceCapability::TheUses);

        $uses = [];
        foreach ($entries as $entry) {
            if ($hasTheUses || $entry->hasRecipesRevealed()) {
                $uses[$entry->getItem()->getSlug()] = $this->usesReader->forItem($entry->getItem());
            }
        }

        return $this->render('game/catalog/index.html.twig', [
            'player' => $player,
            'entries' => $entries,
            'totalResources' => (int) $totalResources,
            'discoveredCount' => \count($entries),
            'totalCollected' => $totalCollected,
            'uses' => $uses,
            'usesByCapability' => $hasTheUses,
        ]);
    }
}
