<?php

namespace App\Controller\Admin;

use App\Entity\App\FeatureFlag;
use App\Entity\User;
use App\Form\Admin\FeatureFlagType;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/feature-flags', name: 'admin_feature_flag_')]
#[IsGranted('ROLE_ADMIN')]
class FeatureFlagController extends AbstractController
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
        $statusFilter = $request->query->get('status', '');

        $qb = $this->em->getRepository(FeatureFlag::class)->createQueryBuilder('f');

        if ($search) {
            $qb->andWhere('LOWER(f.slug) LIKE LOWER(:q) OR LOWER(f.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        if ('enabled' === $statusFilter) {
            $qb->andWhere('f.enabled = true');
        } elseif ('disabled' === $statusFilter) {
            $qb->andWhere('f.enabled = false');
        }

        $qb->orderBy('f.slug', 'ASC');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $total = (int) (clone $qb)->select('COUNT(f.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        $flags = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/feature_flag/index.html.twig', [
            'flags' => $flags,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $flag = new FeatureFlag();
        $form = $this->createForm(FeatureFlagType::class, $flag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existing = $this->em->getRepository(FeatureFlag::class)->findOneBy(['slug' => $flag->getSlug()]);
            if ($existing) {
                $this->addFlash('error', 'Un flag avec ce slug existe deja.');
            } else {
                $this->em->persist($flag);
                $this->em->flush();
                $this->adminLogger->log('create', 'FeatureFlag', $flag->getId(), $flag->getSlug(), [
                    'enabled' => $flag->isEnabled(),
                ]);
                $this->addFlash('success', 'Feature flag "' . $flag->getSlug() . '" cree avec succes.');

                return $this->redirectToRoute('admin_feature_flag_edit', ['id' => $flag->getId()]);
            }
        }

        return $this->render('admin/feature_flag/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, FeatureFlag $flag): Response
    {
        $form = $this->createForm(FeatureFlagType::class, $flag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->adminLogger->log('update', 'FeatureFlag', $flag->getId(), $flag->getSlug(), [
                'enabled' => $flag->isEnabled(),
            ]);
            $this->addFlash('success', 'Feature flag "' . $flag->getSlug() . '" modifie avec succes.');

            return $this->redirectToRoute('admin_feature_flag_edit', ['id' => $flag->getId()]);
        }

        return $this->render('admin/feature_flag/edit.html.twig', [
            'flag' => $flag,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/toggle', name: 'toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Request $request, FeatureFlag $flag): Response
    {
        if (!$this->isCsrfTokenValid('toggle' . $flag->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_feature_flag_index');
        }

        $flag->setEnabled(!$flag->isEnabled());
        $this->em->flush();

        $this->adminLogger->log($flag->isEnabled() ? 'enable' : 'disable', 'FeatureFlag', $flag->getId(), $flag->getSlug());
        $this->addFlash('success', 'Feature flag "' . $flag->getSlug() . '" ' . ($flag->isEnabled() ? 'active' : 'desactive') . ' globalement.');

        return $this->redirectToRoute('admin_feature_flag_index');
    }

    #[Route('/{id}/users/add', name: 'add_user', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addUser(Request $request, FeatureFlag $flag): Response
    {
        if (!$this->isCsrfTokenValid('add_user' . $flag->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_feature_flag_edit', ['id' => $flag->getId()]);
        }

        $identifier = trim((string) $request->request->get('identifier', ''));
        if ('' === $identifier) {
            $this->addFlash('error', 'Email ou username requis.');

            return $this->redirectToRoute('admin_feature_flag_edit', ['id' => $flag->getId()]);
        }

        $user = $this->em->getRepository(User::class)->createQueryBuilder('u')
            ->where('LOWER(u.email) = LOWER(:id) OR LOWER(u.username) = LOWER(:id)')
            ->setParameter('id', $identifier)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user instanceof User) {
            $this->addFlash('error', 'Utilisateur "' . $identifier . '" introuvable.');

            return $this->redirectToRoute('admin_feature_flag_edit', ['id' => $flag->getId()]);
        }

        if ($flag->hasUser($user)) {
            $this->addFlash('warning', 'Utilisateur deja assigne a ce flag.');

            return $this->redirectToRoute('admin_feature_flag_edit', ['id' => $flag->getId()]);
        }

        $flag->addUser($user);
        $this->em->flush();

        $this->adminLogger->log('add_user', 'FeatureFlag', $flag->getId(), $flag->getSlug(), [
            'user_id' => $user->getId(),
            'user' => $user->getUsername() ?? $user->getEmail(),
        ]);
        $this->addFlash('success', 'Utilisateur "' . ($user->getUsername() ?? $user->getEmail()) . '" ajoute au flag.');

        return $this->redirectToRoute('admin_feature_flag_edit', ['id' => $flag->getId()]);
    }

    #[Route('/{id}/users/{userId}/remove', name: 'remove_user', methods: ['POST'], requirements: ['id' => '\d+', 'userId' => '\d+'])]
    public function removeUser(Request $request, FeatureFlag $flag, int $userId): Response
    {
        if (!$this->isCsrfTokenValid('remove_user' . $flag->getId() . '_' . $userId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_feature_flag_edit', ['id' => $flag->getId()]);
        }

        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            $this->addFlash('error', 'Utilisateur introuvable.');

            return $this->redirectToRoute('admin_feature_flag_edit', ['id' => $flag->getId()]);
        }

        $flag->removeUser($user);
        $this->em->flush();

        $this->adminLogger->log('remove_user', 'FeatureFlag', $flag->getId(), $flag->getSlug(), [
            'user_id' => $user->getId(),
            'user' => $user->getUsername() ?? $user->getEmail(),
        ]);
        $this->addFlash('success', 'Utilisateur retire du flag.');

        return $this->redirectToRoute('admin_feature_flag_edit', ['id' => $flag->getId()]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, FeatureFlag $flag): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $flag->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_feature_flag_index');
        }

        $slug = $flag->getSlug();
        $id = $flag->getId();
        $this->em->remove($flag);
        $this->em->flush();

        $this->adminLogger->log('delete', 'FeatureFlag', $id, $slug);
        $this->addFlash('success', 'Feature flag "' . $slug . '" supprime.');

        return $this->redirectToRoute('admin_feature_flag_index');
    }
}
