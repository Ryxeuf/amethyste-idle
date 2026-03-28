<?php

namespace App\Controller\Admin;

use App\Entity\App\Fight;
use App\Service\Admin\AdminFightModerationService;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/fights', name: 'admin_fight_')]
#[IsGranted('ROLE_MODERATOR')]
class FightController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminLogger $adminLogger,
        private readonly AdminFightModerationService $fightModeration,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $onlyActive = $request->query->getBoolean('active', false);

        $qb = $this->em->getRepository(Fight::class)->createQueryBuilder('f')
            ->orderBy('f.id', 'DESC')
            ->setMaxResults(100);

        if ($onlyActive) {
            $qb->andWhere('f.inProgress = true');
        }

        $fights = $qb->getQuery()->getResult();

        return $this->render('admin/fight/index.html.twig', [
            'fights' => $fights,
            'onlyActive' => $onlyActive,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Fight $fight): Response
    {
        return $this->render('admin/fight/show.html.twig', [
            'fight' => $fight,
        ]);
    }

    #[Route('/{id}/update', name: 'update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(Request $request, Fight $fight): Response
    {
        if (!$this->isCsrfTokenValid('admin_fight_update' . $fight->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('admin_fight_show', ['id' => $fight->getId()]);
        }

        $step = max(0, $request->request->getInt('step', $fight->getStep()));
        $inProgress = $request->request->getBoolean('in_progress', $fight->isInProgress());

        $this->fightModeration->applyFightParameters($fight, $step, $inProgress);

        $this->adminLogger->log('fight_update', 'Fight', $fight->getId(), 'Parametres combat', [
            'step' => $step,
            'in_progress' => $inProgress,
        ]);
        $this->addFlash('success', 'Combat #' . $fight->getId() . ' mis a jour (tour / en cours).');

        return $this->redirectToRoute('admin_fight_show', ['id' => $fight->getId()]);
    }

    #[Route('/{id}/release', name: 'release', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function release(Request $request, Fight $fight): Response
    {
        if (!$this->isCsrfTokenValid('admin_fight_release' . $fight->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('admin_fight_show', ['id' => $fight->getId()]);
        }

        $this->fightModeration->forceReleaseFightKeepingMobs($fight);

        $this->adminLogger->log('fight_release', 'Fight', $fight->getId(), 'Liberation mobs + joueurs');
        $this->addFlash('success', 'Combat #' . $fight->getId() . ' : tous les participants ont ete detaches, mobs liberes sur la carte.');

        return $this->redirectToRoute('admin_fight_index');
    }

    #[Route('/{id}/force-delete', name: 'force_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function forceDelete(Request $request, Fight $fight): Response
    {
        if (!$this->isCsrfTokenValid('admin_fight_force_delete' . $fight->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('admin_fight_show', ['id' => $fight->getId()]);
        }

        $id = $fight->getId();
        $this->fightModeration->forceDeleteFightAndMobs($fight);

        $this->adminLogger->log('fight_force_delete', 'Fight', $id, 'Suppression combat + mobs');
        $this->addFlash('warning', 'Combat #' . $id . ' supprime (mobs retires de la base).');

        return $this->redirectToRoute('admin_fight_index');
    }
}
