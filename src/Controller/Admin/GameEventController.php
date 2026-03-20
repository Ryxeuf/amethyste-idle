<?php

namespace App\Controller\Admin;

use App\Entity\App\GameEvent;
use App\Entity\App\Map;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/events', name: 'admin_event_')]
#[IsGranted('ROLE_ADMIN')]
class GameEventController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminLogger $adminLogger,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $status = $request->query->get('status', '');
        $qb = $this->em->getRepository(GameEvent::class)->createQueryBuilder('e');

        if ($status) {
            $qb->where('e.status = :status')->setParameter('status', $status);
        }

        $qb->orderBy('e.startsAt', 'DESC');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $total = (int) (clone $qb)->select('COUNT(e.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        $events = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/event/index.html.twig', [
            'events' => $events,
            'status' => $status,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $maps = $this->em->getRepository(Map::class)->findBy([], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $event = new GameEvent();
            $event->setName($request->request->get('name', ''));
            $event->setType($request->request->get('type', GameEvent::TYPE_CUSTOM));
            $event->setDescription($request->request->get('description'));
            $event->setStartsAt(new \DateTime($request->request->get('starts_at', 'now')));
            $event->setEndsAt(new \DateTime($request->request->get('ends_at', '+1 hour')));
            $event->setRecurring($request->request->getBoolean('recurring'));
            $event->setCreatedAt(new \DateTime());
            $event->setUpdatedAt(new \DateTime());

            $recurrenceInterval = $request->request->getInt('recurrence_interval');
            if ($recurrenceInterval > 0) {
                $event->setRecurrenceInterval($recurrenceInterval);
            }

            $mapId = $request->request->getInt('map_id');
            if ($mapId) {
                $map = $this->em->getRepository(Map::class)->find($mapId);
                $event->setMap($map);
            }

            $parametersJson = $request->request->get('parameters', '');
            if (!empty($parametersJson)) {
                $parameters = json_decode($parametersJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $event->setParameters($parameters);
                } else {
                    $this->addFlash('error', 'Parametres JSON invalides : ' . json_last_error_msg());

                    return $this->render('admin/event/form.html.twig', [
                        'maps' => $maps,
                        'title' => 'Programmer un evenement',
                    ]);
                }
            }

            $this->em->persist($event);
            $this->em->flush();

            $this->adminLogger->log('create', 'GameEvent', $event->getId(), $event->getName());
            $this->addFlash('success', 'Evenement "' . $event->getName() . '" programme.');

            return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('admin/event/form.html.twig', [
            'maps' => $maps,
            'title' => 'Programmer un evenement',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, GameEvent $event): Response
    {
        $maps = $this->em->getRepository(Map::class)->findBy([], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $event->setName($request->request->get('name', $event->getName()));
            $event->setType($request->request->get('type', $event->getType()));
            $event->setDescription($request->request->get('description'));
            $event->setStatus($request->request->get('status', $event->getStatus()));
            $event->setStartsAt(new \DateTime($request->request->get('starts_at', $event->getStartsAt()->format('Y-m-d H:i'))));
            $event->setEndsAt(new \DateTime($request->request->get('ends_at', $event->getEndsAt()->format('Y-m-d H:i'))));
            $event->setRecurring($request->request->getBoolean('recurring'));
            $event->setUpdatedAt(new \DateTime());

            $recurrenceInterval = $request->request->getInt('recurrence_interval');
            $event->setRecurrenceInterval($recurrenceInterval > 0 ? $recurrenceInterval : null);

            $mapId = $request->request->getInt('map_id');
            if ($mapId) {
                $map = $this->em->getRepository(Map::class)->find($mapId);
                $event->setMap($map);
            } else {
                $event->setMap(null);
            }

            $parametersJson = $request->request->get('parameters', '');
            if (!empty($parametersJson)) {
                $parameters = json_decode($parametersJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $event->setParameters($parameters);
                } else {
                    $this->addFlash('error', 'Parametres JSON invalides.');

                    return $this->render('admin/event/form.html.twig', [
                        'maps' => $maps,
                        'event' => $event,
                        'title' => 'Modifier l\'evenement',
                    ]);
                }
            } else {
                $event->setParameters(null);
            }

            $this->em->flush();

            $this->adminLogger->log('update', 'GameEvent', $event->getId(), $event->getName());
            $this->addFlash('success', 'Evenement "' . $event->getName() . '" modifie.');

            return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('admin/event/form.html.twig', [
            'maps' => $maps,
            'event' => $event,
            'title' => 'Modifier l\'evenement',
        ]);
    }

    #[Route('/{id}/toggle', name: 'toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggle(Request $request, GameEvent $event): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $event->getId(), $request->request->get('_token'))) {
            $newStatus = match ($event->getStatus()) {
                GameEvent::STATUS_SCHEDULED => GameEvent::STATUS_ACTIVE,
                GameEvent::STATUS_ACTIVE => GameEvent::STATUS_COMPLETED,
                default => $event->getStatus(),
            };
            $event->setStatus($newStatus);
            $event->setUpdatedAt(new \DateTime());
            $this->em->flush();

            $this->adminLogger->log('toggle', 'GameEvent', $event->getId(), $event->getName() . ' → ' . $newStatus);
            $this->addFlash('success', 'Evenement "' . $event->getName() . '" passe en : ' . $event->getStatusLabel());
        }

        return $this->redirectToRoute('admin_event_index');
    }

    #[Route('/{id}/cancel', name: 'cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(Request $request, GameEvent $event): Response
    {
        if ($this->isCsrfTokenValid('cancel' . $event->getId(), $request->request->get('_token'))) {
            $event->setStatus(GameEvent::STATUS_CANCELLED);
            $event->setUpdatedAt(new \DateTime());
            $this->em->flush();

            $this->adminLogger->log('cancel', 'GameEvent', $event->getId(), $event->getName());
            $this->addFlash('success', 'Evenement "' . $event->getName() . '" annule.');
        }

        return $this->redirectToRoute('admin_event_index');
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, GameEvent $event): Response
    {
        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->get('_token'))) {
            $name = $event->getName();
            $this->em->remove($event);
            $this->em->flush();

            $this->adminLogger->log('delete', 'GameEvent', null, $name);
            $this->addFlash('success', 'Evenement "' . $name . '" supprime.');
        }

        return $this->redirectToRoute('admin_event_index');
    }
}
