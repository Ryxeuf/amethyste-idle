<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_user_')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    private const ASSIGNABLE_ROLES = [
        'ROLE_USER' => 'Utilisateur',
        'ROLE_PLAYER' => 'Joueur',
        'ROLE_GAME_DESIGNER' => 'Game Designer',
        'ROLE_WORLD_BUILDER' => 'World Builder',
        'ROLE_MODERATOR' => 'Moderateur',
        'ROLE_ADMIN' => 'Administrateur',
        'ROLE_SUPER_ADMIN' => 'Super Admin',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminLogger $adminLogger,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $roleFilter = $request->query->get('role', '');

        $qb = $this->em->getRepository(User::class)->createQueryBuilder('u');

        if ($search) {
            $qb->andWhere('LOWER(u.email) LIKE LOWER(:q) OR LOWER(u.username) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        if ($roleFilter) {
            $qb->andWhere('u.roles LIKE :role')
               ->setParameter('role', '%"' . $roleFilter . '"%');
        }

        $qb->orderBy('u.createdAt', 'DESC');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $total = (int) (clone $qb)->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();
        $users = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'availableRoles' => self::ASSIGNABLE_ROLES,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
            'availableRoles' => self::ASSIGNABLE_ROLES,
        ]);
    }

    #[Route('/{id}/roles', name: 'update_roles', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateRoles(Request $request, User $user): Response
    {
        if (!$this->isCsrfTokenValid('roles' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier vos propres roles.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $submittedRoles = $request->request->all('roles');
        $validRoles = array_keys(self::ASSIGNABLE_ROLES);
        $roles = array_values(array_intersect($submittedRoles, $validRoles));

        if (!$this->isGranted('ROLE_SUPER_ADMIN') && in_array('ROLE_SUPER_ADMIN', $roles, true)) {
            $this->addFlash('error', 'Seul un Super Admin peut attribuer le role Super Admin.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $user->setRoles($roles);
        $this->em->flush();

        $this->adminLogger->log('update_roles', 'User', $user->getId(), $user->getUsername() ?? $user->getEmail(), ['roles' => $roles]);
        $this->addFlash('success', 'Roles de "' . ($user->getUsername() ?? $user->getEmail()) . '" mis a jour.');

        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/ban', name: 'ban', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function ban(Request $request, User $user): Response
    {
        if (!$this->isCsrfTokenValid('ban' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous bannir vous-meme.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $user->setIsBanned(!$user->isBanned());
        $this->em->flush();

        $status = $user->isBanned() ? 'banni' : 'debanni';
        $this->adminLogger->log($user->isBanned() ? 'ban' : 'unban', 'User', $user->getId(), $user->getUsername() ?? $user->getEmail());
        $this->addFlash('success', 'Utilisateur "' . ($user->getUsername() ?? $user->getEmail()) . '" ' . $status . '.');

        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/reset-password', name: 'reset_password', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function resetPassword(Request $request, User $user, UserPasswordHasherInterface $passwordHasher): Response
    {
        if (!$this->isCsrfTokenValid('reset_password' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $newPassword = $request->request->get('new_password', '');
        if (\strlen($newPassword) < 6) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caracteres.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $this->em->flush();

        $this->adminLogger->log('reset_password', 'User', $user->getId(), $user->getUsername() ?? $user->getEmail());
        $this->addFlash('success', 'Mot de passe de "' . ($user->getUsername() ?? $user->getEmail()) . '" reinitialise.');

        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }
}
