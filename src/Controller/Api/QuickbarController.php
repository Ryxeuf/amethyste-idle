<?php

namespace App\Controller\Api;

use App\Entity\App\PlayerItem;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Progression\SkillAcquiring;
use App\Helper\InventoryHelper;
use App\Helper\ItemHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/quickbar')]
class QuickbarController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly ItemHelper $itemHelper,
        private readonly InventoryHelper $inventoryHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly SpellApplicator $spellApplicator,
        private readonly SkillAcquiring $skillAcquiring,
        private readonly PlayerSkillHelper $playerSkillHelper,
    ) {
    }

    #[Route('/items', name: 'api_quickbar_items', methods: ['GET'])]
    public function items(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $bagInventory = $this->playerHelper->getBagInventory();
        $playerItems = $bagInventory->getItems();

        $grouped = [];
        foreach ($playerItems as $playerItem) {
            $item = $playerItem->getGenericItem();
            if (!$item->isObject()) {
                continue;
            }
            $slug = $item->getSlug();
            if (!isset($grouped[$slug])) {
                $grouped[$slug] = [
                    'id' => $playerItem->getId(),
                    'slug' => $slug,
                    'name' => $item->getName(),
                    'description' => $item->getDescription(),
                    'usable' => $this->itemHelper->isUsable($item),
                    'quantity' => 0,
                ];
            }
            ++$grouped[$slug]['quantity'];
        }

        return $this->json(array_values($grouped));
    }

    #[Route('/use/{id}', name: 'api_quickbar_use', methods: ['POST'])]
    public function useItem(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($id);

        if (!$playerItem || !$this->inventoryHelper->hasItem($playerItem)) {
            return $this->json(['success' => false, 'message' => 'Objet introuvable.'], 404);
        }

        $item = $playerItem->getGenericItem();

        if (!$item->isObject()) {
            return $this->json(['success' => false, 'message' => 'Cet objet ne peut pas être utilisé.'], 400);
        }

        if (!$this->itemHelper->isUsable($item)) {
            return $this->json(['success' => false, 'message' => 'Cet objet ne peut pas être utilisé.'], 400);
        }

        if ($player->getFight()) {
            return $this->json(['success' => false, 'message' => 'Impossible pendant un combat.'], 400);
        }

        if ($player->isDead()) {
            return $this->json(['success' => false, 'message' => 'Impossible étant mort.'], 400);
        }

        $message = '';

        if ($spell = $this->itemHelper->getItemSpell($item)) {
            $modifiers = $this->itemHelper->getItemSpellModifiers($item, $player);
            $this->spellApplicator->apply($spell, $player, $player, $modifiers);
            $message = sprintf('Vous utilisez %s.', $item->getName());
        } elseif ($skill = $this->itemHelper->getItemSkillLearning($item)) {
            if ($this->playerSkillHelper->hasSkill($skill)) {
                return $this->json(['success' => false, 'message' => 'Vous connaissez déjà cette compétence.'], 400);
            }
            $this->skillAcquiring->acquireSkill($skill);
            $message = sprintf('Compétence « %s » apprise !', $skill->getTitle());
        }

        $nbUsages = $playerItem->getNbUsages();
        if ($nbUsages > 0) {
            $playerItem->setNbUsages($nbUsages - 1);
            if ($playerItem->getNbUsages() <= 0) {
                $this->entityManager->remove($playerItem);
            }
        } elseif ($nbUsages === 0) {
            $this->entityManager->remove($playerItem);
        }

        $this->entityManager->flush();

        // Return updated item data
        $remaining = $this->getRemainingForSlug($item->getSlug());

        return $this->json([
            'success' => true,
            'message' => $message,
            'slug' => $item->getSlug(),
            'remaining' => $remaining['quantity'],
            'newId' => $remaining['id'],
            'playerLife' => $player->getLife(),
            'playerMaxLife' => $player->getMaxLife(),
            'playerEnergy' => $player->getEnergy(),
            'playerMaxEnergy' => $player->getMaxEnergy(),
        ]);
    }

    private function getRemainingForSlug(string $slug): array
    {
        $bagInventory = $this->playerHelper->getBagInventory();
        $count = 0;
        $firstId = null;
        foreach ($bagInventory->getItems() as $playerItem) {
            $item = $playerItem->getGenericItem();
            if ($item->getSlug() === $slug && $item->isObject()) {
                ++$count;
                if ($firstId === null) {
                    $firstId = $playerItem->getId();
                }
            }
        }

        return ['quantity' => $count, 'id' => $firstId];
    }
}
