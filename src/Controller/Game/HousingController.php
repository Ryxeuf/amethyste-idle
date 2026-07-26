<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerHouse;
use App\GameEngine\Housing\HousingManager;
use App\Helper\PlayerHelper;
use App\Repository\PlayerHouseRepository;
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

        return $this->render('game/housing/index.html.twig', [
            'player' => $player,
            'house' => $this->housingManager->getHouse($player),
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
}
