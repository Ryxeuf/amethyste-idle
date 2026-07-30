<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Entity\App\PlayerItem;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Player\PlayerActionHelper;
use App\GameEngine\Progression\DomainAccessManager;
use App\GameEngine\Progression\SkillAcquiring;
use App\Helper\GearHelper;
use App\Helper\InventoryHelper;
use App\Helper\ItemHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerItemHelper;
use App\Helper\PlayerSkillHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Actions d'inventaire sous /api/v1 (migration API-first, phase 2.2) :
 * equiper, desequiper, utiliser un objet.
 *
 * Reprend l'orchestration des controleurs legacy /game/inventory/* (Twig
 * redirections + flashes, non delegables) avec les memes helpers metier.
 * Le socketing de materia arrivera en phase 2.3.
 * Content-Type application/json exige sur les POST (convention CSRF v1).
 */
#[Route('/api/v1/inventory')]
class InventoryActionsController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly GearHelper $gearHelper,
        private readonly PlayerItemHelper $playerItemHelper,
        private readonly PlayerActionHelper $playerActionHelper,
        private readonly ItemHelper $itemHelper,
        private readonly InventoryHelper $inventoryHelper,
        private readonly PlayerSkillHelper $playerSkillHelper,
        private readonly SkillAcquiring $skillAcquiring,
        private readonly SpellApplicator $spellApplicator,
        private readonly EntityManagerInterface $entityManager,
        private readonly DomainAccessManager $domainAccessManager,
    ) {
    }

    #[Route('/equip/{id}', name: 'api_v1_inventory_equip', methods: ['POST'])]
    public function equip(Request $request, int $id): JsonResponse
    {
        $error = $this->guard($request);
        if ($error !== null) {
            return $error;
        }

        $player = $this->playerHelper->getPlayer();

        $itemToEquip = $this->findBagItem($id);
        if ($itemToEquip === null) {
            return ApiResponse::error('not_found', 'Objet introuvable dans le sac.', 404);
        }

        $genericItem = $itemToEquip->getGenericItem();

        // Outils : memes regles que le legacy (slot debloque + competence requise)
        if ($genericItem->isTool()) {
            $toolType = $genericItem->getToolType();
            $hasSlot = $toolType !== null && (
                $player->hasToolSlot($toolType)
                || \in_array($toolType, $this->playerActionHelper->getUnlockedToolSlots(), true)
            );

            if (!$hasSlot) {
                return ApiResponse::error('action_rejected', 'Emplacement d\'outil non debloque.', 409);
            }

            if (!$player->hasToolSlot($toolType)) {
                $player->unlockToolSlot($toolType);
            }

            if (!$this->playerActionHelper->canEquipTool($genericItem->getSlug())) {
                return ApiResponse::error('action_rejected', 'Competence requise pour equiper cet outil.', 409);
            }

            $gearValue = PlayerItem::TOOL_TYPE_TO_GEAR[$toolType] ?? null;
            if ($gearValue === null) {
                return ApiResponse::error('action_rejected', 'Type d\'outil invalide.', 409);
            }

            $currentTool = $this->gearHelper->getEquippedToolByType($toolType);
            if ($currentTool !== null) {
                $currentTool->setGear(0);
            }

            $itemToEquip->setGear($gearValue);
            $this->entityManager->flush();

            return ApiResponse::success([
                'equipped' => true,
                'itemId' => $itemToEquip->getId(),
                'slot' => 'tool_' . $toolType,
                'replacedItemId' => $currentTool?->getId(),
            ]);
        }

        if (!$genericItem->isGear()) {
            return ApiResponse::error('action_rejected', 'Cet objet n\'est pas un equipement.', 409);
        }

        if (!$this->playerItemHelper->canBeEquipped($itemToEquip)) {
            return ApiResponse::error('action_rejected', 'Competences requises manquantes pour equiper cet objet.', 409);
        }

        $slotType = $genericItem->getGearLocation();
        $gearValue = $this->gearHelper->getPlayerItemGearByLocation($slotType);
        if ($gearValue === null) {
            return ApiResponse::error('action_rejected', 'Emplacement d\'equipement invalide.', 409);
        }

        $currentEquipped = $this->gearHelper->getEquippedGearByLocation($slotType);
        if ($currentEquipped !== null) {
            $currentEquipped->setGear(0);
        }

        $itemToEquip->setGear($gearValue);
        $this->entityManager->flush();

        return ApiResponse::success([
            'equipped' => true,
            'itemId' => $itemToEquip->getId(),
            'slot' => $slotType,
            'replacedItemId' => $currentEquipped?->getId(),
        ]);
    }

    #[Route('/unequip/{id}', name: 'api_v1_inventory_unequip', methods: ['POST'])]
    public function unequip(Request $request, int $id): JsonResponse
    {
        $error = $this->guard($request);
        if ($error !== null) {
            return $error;
        }

        $itemToUnequip = $this->findBagItem($id);
        if ($itemToUnequip === null || !$this->gearHelper->isEquipped($itemToUnequip)) {
            return ApiResponse::error('not_found', 'Equipement non trouve.', 404);
        }

        $itemToUnequip->setGear(0);
        $this->entityManager->flush();

        return ApiResponse::success([
            'unequipped' => true,
            'itemId' => $itemToUnequip->getId(),
        ]);
    }

    #[Route('/use/{id}', name: 'api_v1_inventory_use', methods: ['POST'])]
    public function useItem(Request $request, int $id): JsonResponse
    {
        $error = $this->guard($request);
        if ($error !== null) {
            return $error;
        }

        $player = $this->playerHelper->getPlayer();

        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($id);
        if (!$playerItem || !$this->inventoryHelper->hasItem($playerItem)) {
            return ApiResponse::error('not_found', 'Objet introuvable.', 404);
        }

        $item = $playerItem->getGenericItem();

        if (!$item->isObject() || !$this->itemHelper->isUsable($item)) {
            return ApiResponse::error('action_rejected', 'Cet objet ne peut pas etre utilise.', 409);
        }

        if ($player->getFight()) {
            return ApiResponse::error('action_rejected', 'Impossible d\'utiliser un objet pendant un combat.', 409);
        }

        if ($player->isDead()) {
            return ApiResponse::error('action_rejected', 'Impossible d\'utiliser un objet etant mort.', 409);
        }

        $effect = null;
        if ($spell = $this->itemHelper->getItemSpell($item)) {
            $modifiers = $this->itemHelper->getItemSpellModifiers($item, $player);
            $this->spellApplicator->apply($spell, $player, $player, $modifiers);
            $effect = 'spell';
        } elseif ($skill = $this->itemHelper->getItemSkillLearning($item)) {
            if ($this->playerSkillHelper->hasSkill($skill)) {
                return ApiResponse::error('action_rejected', 'Vous connaissez deja cette competence.', 409);
            }

            $this->skillAcquiring->acquireSkill($skill);
            $effect = 'skill';
        } elseif ($domain = $this->itemHelper->getItemDomainOpening($item)) {
            // ONB-08 : un parchemin relu n'est pas consomme — l'ouverture est
            // idempotente, et le refus le dit plutot que de bruler l'objet.
            if (!$this->domainAccessManager->open($player, $domain)) {
                return ApiResponse::error('action_rejected', 'Vous connaissez deja la voie du ' . $domain->getTitle() . '.', 409);
            }

            $effect = 'domain';
        }

        // Decrementer les usages (-1 = illimite)
        $consumed = false;
        $nbUsages = $playerItem->getNbUsages();
        if ($nbUsages > 0) {
            $playerItem->setNbUsages($nbUsages - 1);
            if ($playerItem->getNbUsages() <= 0) {
                $this->entityManager->remove($playerItem);
                $consumed = true;
            }
        } elseif ($nbUsages == 0) {
            $this->entityManager->remove($playerItem);
            $consumed = true;
        }

        $this->entityManager->flush();

        return ApiResponse::success([
            'used' => true,
            'effect' => $effect,
            'consumed' => $consumed,
            'life' => $player->getLife(),
        ]);
    }

    private function findBagItem(int $id): ?PlayerItem
    {
        foreach ($this->playerHelper->getBagInventory()->getItems() as $item) {
            if ($item->getId() === $id) {
                return $item;
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
