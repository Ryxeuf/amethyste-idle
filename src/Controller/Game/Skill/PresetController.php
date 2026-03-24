<?php

namespace App\Controller\Game\Skill;

use App\Entity\App\BuildPreset;
use App\GameEngine\Progression\BuildPresetManager;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PresetController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly BuildPresetManager $presetManager,
    ) {
    }

    #[Route('/game/skills/presets/save', name: 'app_game_skill_preset_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game_skills');
        }

        if (!$this->isCsrfTokenValid('preset_save', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');

            return $this->redirectToRoute('app_game_skills');
        }

        $name = (string) $request->request->get('preset_name', '');

        if (!$this->presetManager->canSave($player)) {
            $this->addFlash('error', sprintf('Limite de %d presets atteinte.', BuildPresetManager::MAX_PRESETS_PER_PLAYER));

            return $this->redirectToRoute('app_game_skills');
        }

        $preset = $this->presetManager->save($player, $name);

        if ($preset) {
            $this->addFlash('success', sprintf('Build « %s » sauvegardé !', $preset->getName()));
        } else {
            $this->addFlash('error', 'Impossible de sauvegarder le preset (nom invalide ou limite atteinte).');
        }

        return $this->redirectToRoute('app_game_skills');
    }

    #[Route('/game/skills/presets/{id}/load', name: 'app_game_skill_preset_load', methods: ['POST'])]
    public function load(Request $request, BuildPreset $preset): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game_skills');
        }

        if (!$this->isCsrfTokenValid('preset_load_' . $preset->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');

            return $this->redirectToRoute('app_game_skills');
        }

        $result = $this->presetManager->load($player, $preset);

        $this->addFlash($result['success'] ? 'success' : 'error', $result['message']);

        return $this->redirectToRoute('app_game_skills');
    }

    #[Route('/game/skills/presets/{id}/delete', name: 'app_game_skill_preset_delete', methods: ['POST'])]
    public function delete(Request $request, BuildPreset $preset): Response
    {
        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game_skills');
        }

        if (!$this->isCsrfTokenValid('preset_delete_' . $preset->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');

            return $this->redirectToRoute('app_game_skills');
        }

        if ($this->presetManager->delete($player, $preset)) {
            $this->addFlash('success', 'Preset supprimé.');
        } else {
            $this->addFlash('error', 'Impossible de supprimer ce preset.');
        }

        return $this->redirectToRoute('app_game_skills');
    }
}
