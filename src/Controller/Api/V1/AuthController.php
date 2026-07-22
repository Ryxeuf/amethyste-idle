<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Entity\User;
use App\Security\Api\ApiJwtManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Authentification par token de l'API v1 (migration API-first, phase 0.2).
 * Destinee aux clients natifs (mobile/Steam) ; le client web garde la
 * session par cookie. Endpoints publics (PUBLIC_ACCESS dans security.yaml).
 */
#[Route('/api/v1/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ApiJwtManager $jwtManager,
    ) {
    }

    #[Route('/login', name: 'api_v1_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $error = $this->requireJsonContentType($request);
        if ($error !== null) {
            return $error;
        }

        $data = json_decode($request->getContent(), true);
        $email = is_array($data) ? trim((string) ($data['email'] ?? '')) : '';
        $password = is_array($data) ? (string) ($data['password'] ?? '') : '';

        if ($email === '' || $password === '') {
            return ApiResponse::error('bad_request', 'Corps JSON attendu avec email et password.', 400);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user === null || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return ApiResponse::error('invalid_credentials', 'Identifiants invalides.', 401);
        }

        return ApiResponse::success([
            'user' => [
                'email' => $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
            ],
        ] + $this->jwtManager->createTokenPair($user));
    }

    #[Route('/refresh', name: 'api_v1_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $error = $this->requireJsonContentType($request);
        if ($error !== null) {
            return $error;
        }

        $data = json_decode($request->getContent(), true);
        $refreshToken = is_array($data) ? (string) ($data['refreshToken'] ?? '') : '';

        if ($refreshToken === '') {
            return ApiResponse::error('bad_request', 'Corps JSON attendu avec refreshToken.', 400);
        }

        $email = $this->jwtManager->validate($refreshToken, 'refresh');
        if ($email === null) {
            return ApiResponse::error('invalid_credentials', 'Refresh token invalide ou expire.', 401);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user === null) {
            return ApiResponse::error('invalid_credentials', 'Utilisateur introuvable.', 401);
        }

        return ApiResponse::success($this->jwtManager->createTokenPair($user));
    }

    private function requireJsonContentType(Request $request): ?JsonResponse
    {
        $contentType = (string) $request->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'json')) {
            return ApiResponse::error('bad_request', 'Content-Type application/json requis.', 400);
        }

        return null;
    }
}
