<?php

namespace App\Controller\Game;

use App\Entity\App\Player;
use App\Entity\User;
use App\Form\CharacterCreateType;
use App\Helper\PlayerHelper;
use App\Service\PlayerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/game/character')]
#[IsGranted('ROLE_USER')]
class CharacterController extends AbstractController
{
    public function __construct(
        private readonly PlayerFactory $playerFactory,
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%app.max_players_per_user%')] private readonly int $maxPlayersPerUser,
    ) {
    }

    #[Route('/create', name: 'app_character_create')]
    public function create(Request $request): Response
    {
        $maxPlayersPerUser = $this->maxPlayersPerUser;
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlayers()->count() >= $maxPlayersPerUser) {
            return $this->render('game/character/limit_reached.html.twig', [
                'maxPlayers' => $maxPlayersPerUser,
            ]);
        }

        $form = $this->createForm(CharacterCreateType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $name */
            $name = trim((string) $form->get('name')->getData());
            /** @var \App\Entity\Game\Race $race */
            $race = $form->get('race')->getData();

            $existingPlayer = $this->entityManager->getRepository(Player::class)
                ->findOneBy(['name' => $name]);

            if ($existingPlayer !== null) {
                $this->addFlash('error', 'Ce nom de personnage est déjà pris.');

                return $this->render('game/character/create.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $player = $this->playerFactory->createPlayer($user, $name, $race);
            $this->playerHelper->setActivePlayer($player);

            return $this->redirectToRoute('app_game');
        }

        return $this->render('game/character/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/select', name: 'app_character_select')]
    public function select(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $players = $user->getPlayers();

        if ($players->count() <= 1) {
            return $this->redirectToRoute('app_game');
        }

        if ($request->isMethod('POST')) {
            $playerId = $request->request->getInt('player_id');
            $player = $this->entityManager->getRepository(Player::class)->find($playerId);

            if ($player instanceof Player && $player->getUser() === $user) {
                $this->playerHelper->setActivePlayer($player);

                return $this->redirectToRoute('app_game');
            }

            $this->addFlash('error', 'Personnage invalide.');
        }

        return $this->render('game/character/select.html.twig', [
            'players' => $players,
        ]);
    }
}
