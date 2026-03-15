<?php

namespace App\Controller\Admin;

use App\Entity\App\DomainExperience;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\PlayerQuest;
use App\Entity\App\PlayerQuestCompleted;
use App\Entity\Game\Item;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/players', name: 'admin_player_')]
#[IsGranted('ROLE_ADMIN')]
class PlayerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminLogger $adminLogger,
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

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $total = (int) (clone $qb)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $players = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/player/index.html.twig', [
            'players' => $players,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Player $player): Response
    {
        $inventories = $this->em->getRepository(Inventory::class)->findBy(['player' => $player]);
        $activeQuests = $this->em->getRepository(PlayerQuest::class)->findBy(['player' => $player]);
        $completedQuests = $this->em->getRepository(PlayerQuestCompleted::class)->findBy(['player' => $player]);
        $domainXp = $this->em->getRepository(DomainExperience::class)->findBy(['player' => $player]);
        $items = $this->em->getRepository(Item::class)->createQueryBuilder('i')
            ->orderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/player/show.html.twig', [
            'player' => $player,
            'inventories' => $inventories,
            'activeQuests' => $activeQuests,
            'completedQuests' => $completedQuests,
            'domainXp' => $domainXp,
            'items' => $items,
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
            $this->adminLogger->log($user->isBanned() ? 'ban' : 'unban', 'Player', $player->getId(), $player->getName());
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
            $this->adminLogger->log('reset_position', 'Player', $player->getId(), $player->getName());
            $this->addFlash('success', 'Position du joueur "' . $player->getName() . '" reinitialisee.');
        }

        return $this->redirectToRoute('admin_player_show', ['id' => $player->getId()]);
    }

    #[Route('/{id}/change-role', name: 'change_role', methods: ['POST'])]
    public function changeRole(Request $request, Player $player): Response
    {
        if ($this->isCsrfTokenValid('role' . $player->getId(), $request->request->get('_token'))) {
            $role = $request->request->get('role');
            $validRoles = ['ROLE_USER', 'ROLE_PLAYER', 'ROLE_ADMIN'];

            if (in_array($role, $validRoles, true)) {
                $user = $player->getUser();
                $user->setRoles([$role]);
                $this->em->flush();
                $this->adminLogger->log('change_role', 'Player', $player->getId(), $player->getName(), ['role' => $role]);
                $this->addFlash('success', 'Role du joueur "' . $player->getName() . '" change en ' . $role . '.');
            }
        }

        return $this->redirectToRoute('admin_player_show', ['id' => $player->getId()]);
    }

    #[Route('/{id}/give-item', name: 'give_item', methods: ['POST'])]
    public function giveItem(Request $request, Player $player): Response
    {
        if ($this->isCsrfTokenValid('give_item' . $player->getId(), $request->request->get('_token'))) {
            $itemId = $request->request->getInt('item_id');
            $quantity = max(1, $request->request->getInt('quantity', 1));

            $item = $this->em->getRepository(Item::class)->find($itemId);
            if (!$item) {
                $this->addFlash('error', 'Item introuvable.');

                return $this->redirectToRoute('admin_player_show', ['id' => $player->getId()]);
            }

            $bag = null;
            foreach ($player->getInventories() as $inv) {
                if ($inv->isBag()) {
                    $bag = $inv;
                    break;
                }
            }

            if (!$bag) {
                $this->addFlash('error', 'Le joueur n\'a pas d\'inventaire.');

                return $this->redirectToRoute('admin_player_show', ['id' => $player->getId()]);
            }

            for ($i = 0; $i < $quantity; ++$i) {
                $playerItem = new PlayerItem();
                $playerItem->setGenericItem($item);
                $playerItem->setInventory($bag);
                $this->em->persist($playerItem);
            }

            $this->em->flush();
            $this->adminLogger->log('give_item', 'Player', $player->getId(), $player->getName(), ['item' => $item->getName(), 'quantity' => $quantity]);
            $this->addFlash('success', $quantity . 'x "' . $item->getName() . '" donne a "' . $player->getName() . '".');
        }

        return $this->redirectToRoute('admin_player_show', ['id' => $player->getId()]);
    }

    #[Route('/{id}/give-gils', name: 'give_gils', methods: ['POST'])]
    public function giveGils(Request $request, Player $player): Response
    {
        if ($this->isCsrfTokenValid('give_gils' . $player->getId(), $request->request->get('_token'))) {
            $amount = $request->request->getInt('amount', 0);
            if ($amount > 0) {
                $player->addGils($amount);
                $this->em->flush();
                $this->adminLogger->log('give_gils', 'Player', $player->getId(), $player->getName(), ['amount' => $amount]);
                $this->addFlash('success', number_format($amount, 0, ',', ' ') . ' gils donnes a "' . $player->getName() . '".');
            }
        }

        return $this->redirectToRoute('admin_player_show', ['id' => $player->getId()]);
    }
}
