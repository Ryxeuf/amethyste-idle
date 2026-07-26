<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerHouse;
use App\Entity\Game\Item;
use App\GameEngine\Housing\GardenService;
use App\GameEngine\Housing\HousingManager;
use App\Helper\PlayerHelper;
use App\Repository\GardenPlotRepository;
use App\Repository\PlayerHouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Demeure du joueur (tache 129, HOU-01).
 *
 * L'ecran sert les deux etats : la demeure quand on en possede une, l'offre de
 * terrain quand on se trouve dans une zone residentielle.
 */
#[Route('/game/house')]
#[IsGranted('ROLE_USER')]
class HousingController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly HousingManager $housingManager,
        private readonly PlayerHouseRepository $houseRepository,
        private readonly GardenService $gardenService,
        private readonly GardenPlotRepository $plotRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_game_house', methods: ['GET'])]
    public function index(): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        $zone = $player->getCurrentZone();
        $house = $this->housingManager->getHouse($player);

        return $this->render('game/housing/index.html.twig', [
            'player' => $player,
            'house' => $house,
            // HOU-02 : le jardin, pilier PBBG du housing.
            'plots' => null !== $house ? $this->gardenService->getPlots($house) : [],
            'plantableStock' => $this->gardenService->getPlantableStock($player),
            'zone' => $zone,
            'canBuyHere' => null !== $zone && $this->housingManager->isResidential($zone),
            'landPrice' => PlayerHouse::LAND_PRICE,
            // Le voisinage : visible des maintenant, visitable en HOU-03.
            'neighbours' => null !== $zone ? $this->houseRepository->findInZone($zone) : [],
        ]);
    }

    #[Route('/buy', name: 'app_game_house_buy', methods: ['POST'])]
    public function buy(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('house_buy', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_house');
        }

        $zone = $player->getCurrentZone();
        if (null === $zone) {
            $this->addFlash('error', 'Vous n\'etes nulle part.');

            return $this->redirectToRoute('app_game_house');
        }

        try {
            $house = $this->housingManager->buyLand($player, $zone, (string) $request->request->get('name', ''));
            $this->addFlash('success', sprintf('« %s » est a vous.', $house->getName()));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_house');
    }

    #[Route('/rename', name: 'app_game_house_rename', methods: ['POST'])]
    public function rename(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('house_rename', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_house');
        }

        $house = $this->housingManager->getHouse($player);
        if (!$house instanceof PlayerHouse) {
            $this->addFlash('error', 'Vous ne possedez pas de demeure.');

            return $this->redirectToRoute('app_game_house');
        }

        try {
            $this->housingManager->rename($player, $house, (string) $request->request->get('name', ''));
            $this->addFlash('success', 'Votre demeure a ete renommee.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_house');
    }

    #[Route('/garden/{id}/plant', name: 'app_game_house_plant', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function plant(int $id, Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('garden_plant_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_house');
        }

        $plot = $this->plotRepository->find($id);
        $crop = $this->entityManager->getRepository(Item::class)->findOneBy(['slug' => (string) $request->request->get('crop', '')]);

        if (null === $plot || !$crop instanceof Item) {
            $this->addFlash('error', 'Parcelle ou plante introuvable.');

            return $this->redirectToRoute('app_game_house');
        }

        try {
            $this->gardenService->plant($player, $plot, $crop);
            $this->addFlash('success', sprintf('%s mis(e) en terre.', $crop->getName()));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_house');
    }

    #[Route('/garden/{id}/harvest', name: 'app_game_house_harvest', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function harvest(int $id, Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('garden_harvest_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de securite invalide.');

            return $this->redirectToRoute('app_game_house');
        }

        $plot = $this->plotRepository->find($id);
        if (null === $plot) {
            $this->addFlash('error', 'Parcelle introuvable.');

            return $this->redirectToRoute('app_game_house');
        }

        try {
            $quantity = $this->gardenService->harvest($player, $plot);
            $this->addFlash('success', sprintf('Recolte : %d unite(s).', $quantity));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_house');
    }

    /**
     * Visite de la demeure d'un voisin (HOU-03).
     *
     * Vue **en lecture seule** : le visiteur voit le jardin pousser, il n'y
     * touche pas. C'est ce qui fait la difference entre montrer sa demeure et
     * la partager.
     */
    #[Route('/visit/{id}', name: 'app_game_house_visit', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function visit(int $id): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        $house = $this->houseRepository->find($id);
        if (!$house instanceof PlayerHouse) {
            $this->addFlash('error', 'Cette demeure n\'existe pas.');

            return $this->redirectToRoute('app_game_house');
        }

        try {
            $this->housingManager->assertCanVisit($player, $house);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_game_house');
        }

        return $this->render('game/housing/visit.html.twig', [
            'player' => $player,
            'house' => $house,
            'plots' => $this->gardenService->getPlots($house),
            'isOwn' => $house->getOwner()->getId() === $player->getId(),
        ]);
    }
}
