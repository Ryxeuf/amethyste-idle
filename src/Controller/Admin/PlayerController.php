<?php

namespace App\Controller\Admin;

use App\Entity\App\DomainExperience;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\PlayerQuest;
use App\Entity\App\PlayerQuestCompleted;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Form\Admin\PlayerPositionType;
use App\GameEngine\Realtime\Map\MovedPlayerHandler;
use App\Helper\CellHelper;
use App\Helper\MapCellValidator;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/players', name: 'admin_player_')]
#[IsGranted('ROLE_MODERATOR')]
class PlayerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminLogger $adminLogger,
        private readonly MovedPlayerHandler $movedPlayerHandler,
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
        $total = (int) (clone $qb)->select('COUNT(p.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
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

    #[Route('/{id}/move', name: 'move', requirements: ['id' => '\d+'])]
    public function move(Request $request, Player $player): Response
    {
        $form = $this->createForm(PlayerPositionType::class, $player, ['show_map_field' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $coordsStr = trim($player->getCoordinates());
            $map = $player->getMap();

            if (!preg_match('/^\d+\.\d+$/', $coordsStr)) {
                $form->get('coordinates')->addError(new FormError('Format attendu : x.y (entiers, ex. 10.15).'));
            } elseif ($map === null) {
                $form->get('map')->addError(new FormError('Choisissez une carte.'));
            } else {
                [$sx, $sy] = explode('.', $coordsStr, 2);
                $x = (int) $sx;
                $y = (int) $sy;
                $normalized = CellHelper::stringifyCoordinates($x, $y);

                if (!MapCellValidator::isCellWalkable($map, $x, $y)) {
                    $form->get('coordinates')->addError(new FormError('La case cible est bloquee ou hors carte.'));
                } else {
                    $player->setCoordinates($normalized);
                    $player->setLastCoordinates($normalized);
                    $player->setIsMoving(false);
                    $this->em->flush();
                    $this->movedPlayerHandler->movePlayer($player);

                    $this->adminLogger->log('update', 'Player', $player->getId(), sprintf(
                        '%s deplace vers %s sur %s',
                        $player->getName(),
                        $normalized,
                        $map->getName()
                    ));
                    $this->addFlash('success', 'Joueur "' . $player->getName() . '" place en ' . $normalized . ' (' . $map->getName() . ').');

                    return $this->redirectToRoute('admin_player_show', ['id' => $player->getId()]);
                }
            }
        }

        return $this->render('admin/player/move.html.twig', [
            'player' => $player,
            'form' => $form->createView(),
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
        $domains = $this->em->getRepository(Domain::class)->createQueryBuilder('d')
            ->orderBy('d.title', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/player/show.html.twig', [
            'player' => $player,
            'inventories' => $inventories,
            'activeQuests' => $activeQuests,
            'completedQuests' => $completedQuests,
            'domainXp' => $domainXp,
            'items' => $items,
            'domains' => $domains,
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
            $validRoles = ['ROLE_USER', 'ROLE_PLAYER', 'ROLE_GAME_DESIGNER', 'ROLE_WORLD_BUILDER', 'ROLE_MODERATOR', 'ROLE_ADMIN'];

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

    #[Route('/{id}/set-domain-xp', name: 'set_domain_xp', methods: ['POST'])]
    public function setDomainXp(Request $request, Player $player): Response
    {
        if ($this->isCsrfTokenValid('set_domain_xp' . $player->getId(), $request->request->get('_token'))) {
            $domainId = $request->request->getInt('domain_id');
            $xpValue = $request->request->getInt('xp_value', 0);

            $domain = $this->em->getRepository(Domain::class)->find($domainId);
            if (!$domain) {
                $this->addFlash('error', 'Domaine introuvable.');

                return $this->redirectToRoute('admin_player_show', ['id' => $player->getId()]);
            }

            // Find existing domain experience or create a new one
            $domainExp = $this->em->getRepository(DomainExperience::class)->findOneBy([
                'player' => $player,
                'domain' => $domain,
            ]);

            if (!$domainExp) {
                $domainExp = new DomainExperience();
                $domainExp->setPlayer($player);
                $domainExp->setDomain($domain);
                $domainExp->setCreatedAt(new \DateTime());
                $domainExp->setUpdatedAt(new \DateTime());
                $this->em->persist($domainExp);
            }

            $domainExp->setTotalExperience($xpValue);
            $this->em->flush();

            $this->adminLogger->log('set_domain_xp', 'Player', $player->getId(), $player->getName(), [
                'domain' => $domain->getTitle(),
                'xp' => $xpValue,
            ]);
            $this->addFlash('success', 'XP du domaine "' . $domain->getTitle() . '" mis a ' . number_format($xpValue, 0, ',', ' ') . ' pour "' . $player->getName() . '".');
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
