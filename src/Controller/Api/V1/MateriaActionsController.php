<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Exception\ItemNotEquippedException;
use App\Exception\ItemNotMateriaException;
use App\Exception\ItemRequirementsException;
use App\Exception\MateriaSlotTypeException;
use App\GameEngine\Gear\MateriaGearSetter;
use App\Helper\PlayerHelper;
use App\Helper\PlayerItemHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Socketing de materia sous /api/v1 (migration API-first, phase 2.3).
 * Reprend l'orchestration des controleurs legacy set/unset (Twig, non
 * delegables) avec MateriaGearSetter et ses exceptions typees.
 * Content-Type application/json exige (convention CSRF v1).
 */
#[Route('/api/v1/inventory/materia')]
class MateriaActionsController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly MateriaGearSetter $materiaGearSetter,
        private readonly PlayerItemHelper $playerItemHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/set/{slotId}/{materiaId}', name: 'api_v1_inventory_materia_set', methods: ['POST'])]
    public function set(Request $request, int $slotId, int $materiaId): JsonResponse
    {
        $error = $this->guard($request);
        if ($error !== null) {
            return $error;
        }

        $slot = $this->findOwnedSlot($slotId);
        if ($slot === null) {
            return ApiResponse::error('not_found', 'Slot introuvable sur votre equipement.', 404);
        }

        $materia = null;
        foreach ($this->playerHelper->getMateriaInventory()->getItems() as $item) {
            if ($item->getId() === $materiaId && $item->isMateria()) {
                $materia = $item;
                break;
            }
        }

        if ($materia === null) {
            return ApiResponse::error('not_found', 'Materia introuvable dans votre inventaire.', 404);
        }

        if ($materia->getSlotSet() !== null) {
            return ApiResponse::error('action_rejected', 'Cette materia est deja equipee dans un autre slot.', 409);
        }

        $block = $this->playerItemHelper->getMateriaSocketBlockMessage($materia);
        if ($block !== null) {
            return ApiResponse::error('action_rejected', $block, 409);
        }

        try {
            $this->materiaGearSetter->setMateria($materia, $slot);
        } catch (ItemRequirementsException $e) {
            return ApiResponse::error('action_rejected', $this->playerItemHelper->getMateriaSocketBlockMessage($materia) ?? $e->getMessage(), 409);
        } catch (ItemNotEquippedException) {
            return ApiResponse::error('action_rejected', 'L\'equipement n\'est plus porte.', 409);
        } catch (ItemNotMateriaException) {
            return ApiResponse::error('action_rejected', 'Cet objet n\'est pas une materia.', 409);
        } catch (MateriaSlotTypeException) {
            // DOM-03 : un refus de sertissage est une reponse metier, pas une
            // erreur serveur. Sans ce cas, l'API rendait un 500 muet.
            return ApiResponse::error('action_rejected', sprintf(
                'Cet emplacement n\'accepte que les materia de %s.',
                mb_strtolower($slot->getItem()->getGenericItem()->getMateriaSlotType()->label()),
            ), 409);
        }

        return ApiResponse::success([
            'socketed' => true,
            'slotId' => $slot->getId(),
            'materiaId' => $materia->getId(),
        ]);
    }

    #[Route('/unset/{slotId}', name: 'api_v1_inventory_materia_unset', methods: ['POST'])]
    public function unset(Request $request, int $slotId): JsonResponse
    {
        $error = $this->guard($request);
        if ($error !== null) {
            return $error;
        }

        $slot = $this->findOwnedSlot($slotId);
        if ($slot === null) {
            return ApiResponse::error('not_found', 'Slot introuvable sur votre equipement.', 404);
        }

        $materia = $slot->getItemSet();
        if ($materia === null) {
            return ApiResponse::error('action_rejected', 'Ce slot est deja vide.', 409);
        }

        $materiaId = $materia->getId();
        $this->materiaGearSetter->unsetMateria($slot);

        return ApiResponse::success([
            'unsocketed' => true,
            'slotId' => $slot->getId(),
            'materiaId' => $materiaId,
        ]);
    }

    /**
     * Retourne le slot uniquement s'il appartient a un equipement du sac
     * du joueur courant (meme verification que le legacy).
     */
    private function findOwnedSlot(int $slotId): ?Slot
    {
        $slot = $this->entityManager->getRepository(Slot::class)->find($slotId);
        if ($slot === null) {
            return null;
        }

        /** @var PlayerItem $gearItem */
        $gearItem = $slot->getItem();
        foreach ($this->playerHelper->getBagInventory()->getItems() as $item) {
            if ($item->getId() === $gearItem->getId()) {
                return $slot;
            }
        }

        return null;
    }

    private function guard(Request $request): ?JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->playerHelper->getPlayer() === null) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        $contentType = (string) $request->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'json')) {
            return ApiResponse::error('bad_request', 'Content-Type application/json requis.', 400);
        }

        return null;
    }
}
