<?php

namespace App\Controller\Game\Inventory;

use App\GameEngine\Materia\MateriaConversionException;
use App\GameEngine\Materia\MateriaConversionService;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Fondre ou lire, depuis l'ecran des materia (FAC-04b).
 *
 * Deux boutons, un choix definitif : la doctrine du monde ramenee a une
 * micro-decision quotidienne (GAME_WORLD § 12.2).
 */
#[Route('/game/inventory/materia')]
class MateriaConvertController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly MateriaConversionService $conversionService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/melt/{id}', name: 'app_game_inventory_materia_melt', methods: ['POST'])]
    public function melt(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $materia = $this->findOwnedMateria($id);
        if (null === $materia) {
            $this->addFlash('error', $this->translator->trans('game.materia.convert.error.not_found'));

            return $this->redirectToRoute('app_game_inventory');
        }

        try {
            $result = $this->conversionService->melt($this->playerHelper->getPlayer(), $materia);
            $this->addFlash('success', $this->translator->trans('game.materia.convert.melted', [
                '%gils%' => $result['gils'],
                '%essence%' => $result['essence'],
            ]));
        } catch (MateriaConversionException $e) {
            $this->addFlash('error', $this->translator->trans($e->getMessage()));
        }

        return $this->redirectToRoute('app_game_inventory');
    }

    #[Route('/read/{id}', name: 'app_game_inventory_materia_read', methods: ['POST'])]
    public function read(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $materia = $this->findOwnedMateria($id);
        if (null === $materia) {
            $this->addFlash('error', $this->translator->trans('game.materia.convert.error.not_found'));

            return $this->redirectToRoute('app_game_inventory');
        }

        try {
            $result = $this->conversionService->read($this->playerHelper->getPlayer(), $materia);
            $message = $this->translator->trans('game.materia.convert.read', [
                '%points%' => $result['accordPoints'],
            ]);
            if ($result['codexUnlocked'] > 0) {
                $message .= ' ' . $this->translator->trans('game.materia.convert.codex_unlocked');
            }
            $this->addFlash('success', $message);
        } catch (MateriaConversionException $e) {
            $this->addFlash('error', $this->translator->trans($e->getMessage()));
        }

        return $this->redirectToRoute('app_game_inventory');
    }

    /**
     * La materia doit venir de l'inventaire de materia du joueur courant : on
     * ne convertit ni l'objet d'un autre, ni un identifiant forge.
     */
    private function findOwnedMateria(int $id): ?\App\Entity\App\PlayerItem
    {
        foreach ($this->playerHelper->getMateriaInventory()->getItems() as $item) {
            if ($item->getId() === $id && $item->isMateria()) {
                return $item;
            }
        }

        return null;
    }
}
