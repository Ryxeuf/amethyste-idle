<?php

namespace App\Controller\Admin;

use App\Entity\App\Player;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/players', name: 'admin_player_')]
class PlayerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $qb = $this->em->getRepository(Player::class)->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u');

        if ($search) {
            $qb->where('LOWER(p.name) LIKE LOWER(:q) OR LOWER(u.email) LIKE LOWER(:q) OR LOWER(u.username) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $qb->orderBy('p.name', 'ASC');
        $players = $qb->getQuery()->getResult();

        return $this->render('admin/player/index.html.twig', [
            'players' => $players,
            'search' => $search,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Player $player): Response
    {
        return $this->render('admin/player/show.html.twig', [
            'player' => $player,
        ]);
    }

    #[Route('/{id}/ban', name: 'ban', methods: ['POST'])]
    public function ban(Request $request, Player $player): Response
    {
        if ($this->isCsrfTokenValid('ban' . $player->getId(), $request->request->get('_token'))) {
            $user = $player->getUser();
            $user->setIsBanned(!$user->isBanned());
            $this->em->flush();

            $status = $user->isBanned() ? 'banni' : 'debanni';
            $this->addFlash('success', 'Joueur "' . $player->getName() . '" ' . $status . ' avec succes.');
        }

        return $this->redirectToRoute('admin_player_show', ['id' => $player->getId()]);
    }

    #[Route('/{id}/reset-position', name: 'reset_position', methods: ['POST'])]
    public function resetPosition(Request $request, Player $player): Response
    {
        if ($this->isCsrfTokenValid('reset' . $player->getId(), $request->request->get('_token'))) {
            $player->setCoordinates('5.5');
            $player->setLastCoordinates('5.5');
            $this->em->flush();
            $this->addFlash('success', 'Position du joueur "' . $player->getName() . '" reinitialisee.');
        }

        return $this->redirectToRoute('admin_player_show', ['id' => $player->getId()]);
    }
}
