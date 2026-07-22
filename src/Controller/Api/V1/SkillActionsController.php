<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Entity\App\BuildPreset;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\BuildPresetManager;
use App\GameEngine\Progression\SkillAcquiring;
use App\GameEngine\Progression\SkillRespecManager;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Actions des arbres de talent sous /api/v1 (migration API-first, phase 3.2) :
 * acquisition de competence, respec, presets de build.
 *
 * Ces endpoints sont authentifies par session : en l'absence de token CSRF,
 * ils EXIGENT Content-Type application/json (un formulaire HTML cross-site
 * ne peut pas l'envoyer, et fetch cross-origin echoue au preflight CORS).
 */
#[Route('/api/v1/skills')]
class SkillActionsController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerSkillHelper $skillHelper,
        private readonly SkillAcquiring $skillAcquiring,
        private readonly SkillRespecManager $respecManager,
        private readonly BuildPresetManager $presetManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/acquire', name: 'api_v1_skills_acquire', methods: ['POST'])]
    public function acquire(Request $request): JsonResponse
    {
        $error = $this->guard($request);
        if ($error !== null) {
            return $error;
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['skillId'])) {
            return ApiResponse::error('bad_request', 'Corps JSON attendu avec skillId.', 400);
        }

        $skill = $this->entityManager->getRepository(Skill::class)->find((int) $data['skillId']);
        if ($skill === null) {
            return ApiResponse::error('not_found', 'Competence introuvable.', 404);
        }

        if ($this->skillHelper->hasSkill($skill)) {
            return ApiResponse::error('action_rejected', 'Competence deja acquise.', 409);
        }

        if (!$this->skillHelper->canAcquireSkill($skill)) {
            return ApiResponse::error('action_rejected', 'Prerequis non remplis ou points insuffisants.', 409);
        }

        $this->skillAcquiring->acquireSkill($skill);

        $player = $this->playerHelper->getPlayer();

        return ApiResponse::success([
            'skillId' => $skill->getId(),
            'acquired' => true,
            'totalUsedPoints' => $this->skillHelper->getTotalUsedPoints($player),
        ]);
    }

    #[Route('/respec', name: 'api_v1_skills_respec', methods: ['POST'])]
    public function respec(Request $request): JsonResponse
    {
        $error = $this->guard($request);
        if ($error !== null) {
            return $error;
        }

        $player = $this->playerHelper->getPlayer();

        if ($player->getSkills()->isEmpty()) {
            return ApiResponse::error('action_rejected', 'Aucune competence a redistribuer.', 409);
        }

        if ($player->getFight() !== null) {
            return ApiResponse::error('action_rejected', 'Impossible de redistribuer en combat.', 409);
        }

        $cost = $this->respecManager->getRespecCost($player);

        if (!$this->respecManager->respec($player)) {
            return ApiResponse::error('action_rejected', sprintf('Fonds insuffisants. Il vous faut %d gils.', $cost), 409);
        }

        return ApiResponse::success([
            'respecced' => true,
            'cost' => $cost,
            'gils' => $player->getGils(),
        ]);
    }

    #[Route('/presets', name: 'api_v1_skills_preset_save', methods: ['POST'])]
    public function savePreset(Request $request): JsonResponse
    {
        $error = $this->guard($request);
        if ($error !== null) {
            return $error;
        }

        $player = $this->playerHelper->getPlayer();

        if (!$this->presetManager->canSave($player)) {
            return ApiResponse::error('action_rejected', sprintf('Limite de %d presets atteinte ou aucune competence acquise.', BuildPresetManager::MAX_PRESETS_PER_PLAYER), 409);
        }

        $data = json_decode($request->getContent(), true);
        $name = is_array($data) ? (string) ($data['name'] ?? '') : '';

        $preset = $this->presetManager->save($player, $name);
        if ($preset === null) {
            return ApiResponse::error('validation_failed', 'Nom de preset invalide (1 a 50 caracteres).', 422);
        }

        return ApiResponse::success([
            'id' => $preset->getId(),
            'name' => $preset->getName(),
            'skillSlugs' => $preset->getSkillSlugs(),
        ], 201);
    }

    #[Route('/presets/{id}/load', name: 'api_v1_skills_preset_load', methods: ['POST'])]
    public function loadPreset(Request $request, BuildPreset $preset): JsonResponse
    {
        $error = $this->guard($request);
        if ($error !== null) {
            return $error;
        }

        $player = $this->playerHelper->getPlayer();

        if ($preset->getPlayer()->getId() !== $player->getId()) {
            return ApiResponse::error('not_found', 'Preset introuvable.', 404);
        }

        $result = $this->presetManager->load($player, $preset);
        if (!$result['success']) {
            return ApiResponse::error('action_rejected', $result['message'], 409);
        }

        return ApiResponse::success([
            'loaded' => true,
            'presetId' => $preset->getId(),
            'message' => $result['message'],
        ]);
    }

    #[Route('/presets/{id}', name: 'api_v1_skills_preset_delete', methods: ['DELETE'])]
    public function deletePreset(Request $request, BuildPreset $preset): JsonResponse
    {
        $error = $this->guard($request);
        if ($error !== null) {
            return $error;
        }

        $player = $this->playerHelper->getPlayer();

        if (!$this->presetManager->delete($player, $preset)) {
            return ApiResponse::error('not_found', 'Preset introuvable.', 404);
        }

        return ApiResponse::success(['deleted' => true]);
    }

    /**
     * Verifications communes : joueur courant present et Content-Type JSON
     * (protection CSRF des endpoints a cookie de session, voir docblock classe).
     */
    private function guard(Request $request): ?JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->playerHelper->getPlayer() === null) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        // Les DELETE ne sont pas emissibles par un formulaire HTML et declenchent
        // toujours un preflight CORS : seul POST necessite la protection.
        if ($request->isMethod('POST')) {
            $contentType = (string) $request->headers->get('Content-Type', '');
            if (!str_contains($contentType, 'json')) {
                return ApiResponse::error('bad_request', 'Content-Type application/json requis.', 400);
            }
        }

        return null;
    }
}
