<?php

namespace App\GameEngine\Gathering;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Event\GatheringEvent;
use App\Event\GatheringXpEvent;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\Helper\InventoryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class GatheringManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly ToolDurabilityManager $toolDurabilityManager,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
    ) {
    }

    /**
     * Attempt to gather a resource (fishing or skinning).
     *
     * @param string $type    'fishing' or 'skinning'
     * @param array  $context Extra context (e.g., mob drops for skinning)
     *
     * @return array{success: bool, item: ?Item, quantity: int, message: string}
     */
    public function gather(Player $player, string $type, array $context = []): array
    {
        // 1. Check if player has the right tool equipped or in inventory
        $tool = $this->findTool($player, $type);
        if (!$tool) {
            return ['success' => false, 'item' => null, 'quantity' => 0, 'message' => 'Outil requis non trouvé.'];
        }

        // 2. Check tool durability
        if ($this->toolDurabilityManager->isToolBroken($tool)) {
            return ['success' => false, 'item' => null, 'quantity' => 0, 'message' => 'Votre outil est cassé.'];
        }

        // 3. Calculate success chance based on skill level + tool quality
        $skillLevel = $this->getGatheringSkillLevel($player, $type);
        $baseChance = 50 + ($skillLevel * 5); // 50% base + 5% per skill level
        $toolBonus = $this->getToolBonus($tool);
        $chance = min(95, $baseChance + $toolBonus);

        // 4. Roll for success
        $roll = random_int(1, 100);
        if ($roll > $chance) {
            // Tool durability loss on failure too
            $this->toolDurabilityManager->reduceDurability($tool);
            $this->em->flush();

            return ['success' => false, 'item' => null, 'quantity' => 0, 'message' => 'La récolte a échoué.'];
        }

        // 5. Determine what was gathered
        $gathered = $this->determineGatheredItem($type, $skillLevel, $context);
        if (!$gathered) {
            return ['success' => false, 'item' => null, 'quantity' => 0, 'message' => 'Rien à récolter ici.'];
        }

        // 6. Add item to player inventory
        $quantity = random_int(1, 1 + intdiv($skillLevel, 3));
        for ($i = 0; $i < $quantity; ++$i) {
            $playerItem = $this->playerItemGenerator->generateFromItemId($gathered->getId());
            $this->inventoryHelper->addItem($playerItem, false);
        }

        // 7. Degrade tool
        $this->toolDurabilityManager->reduceDurability($tool);

        // 8. Grant gathering XP via event
        $xp = ($gathered->getLevel() ?? 1) * 10;
        $this->dispatcher->dispatch(
            new GatheringXpEvent($player, $xp, $type),
            GatheringXpEvent::NAME
        );

        // 9. Dispatch gathering event
        $this->dispatcher->dispatch(
            new GatheringEvent($player, $gathered, $quantity, $type),
            GatheringEvent::NAME
        );

        $this->em->flush();

        return [
            'success' => true,
            'item' => $gathered,
            'quantity' => $quantity,
            'message' => sprintf('Vous avez récolté %dx %s !', $quantity, $gathered->getName()),
        ];
    }

    private function findTool(Player $player, string $type): ?PlayerItem
    {
        $toolTypes = [
            'fishing' => Item::TOOL_TYPE_FISHING_ROD,
            'skinning' => Item::TOOL_TYPE_SKINNING_KNIFE,
        ];
        $requiredType = $toolTypes[$type] ?? null;
        if (!$requiredType) {
            return null;
        }

        // Search in player inventories for a tool of the right type
        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }
            foreach ($inventory->getItems() as $playerItem) {
                $genericItem = $playerItem->getGenericItem();
                if ($genericItem->isTool()
                    && $genericItem->getToolType() === $requiredType
                    && !$this->toolDurabilityManager->isToolBroken($playerItem)
                ) {
                    return $playerItem;
                }
            }
        }

        return null;
    }

    private function getGatheringSkillLevel(Player $player, string $type): int
    {
        // Look for domain experience matching the gathering type
        $level = 0;
        foreach ($player->getDomainExperiences() as $domainExperience) {
            $domain = $domainExperience->getDomain();
            if (str_contains(strtolower($domain->getTitle()), $type)) {
                // Convert total XP to an approximate level
                $level += intdiv($domainExperience->getTotalExperience(), 100);
            }
        }

        return $level;
    }

    private function getToolBonus(PlayerItem $tool): int
    {
        // Tool tier gives +3% per tier
        $tier = $tool->getGenericItem()->getToolTier() ?? 1;

        return $tier * 3;
    }

    private function determineGatheredItem(string $type, int $skillLevel, array $context): ?Item
    {
        if ($type === 'skinning' && !empty($context['drops'])) {
            // Skinning: pick from mob drops
            $drops = $context['drops'];
            $index = array_rand($drops);

            return $drops[$index];
        }

        // Fishing: select from fishing items based on skill level
        $fishItems = $this->em->getRepository(Item::class)->findBy(['type' => Item::TYPE_RESOURCE]);
        // Filter to items that have a domain related to fishing
        $fishItems = array_values(array_filter($fishItems, function (Item $item) {
            $domain = $item->getDomain();

            return $domain && str_contains(strtolower($domain->getTitle()), 'fishing');
        }));

        if (empty($fishItems)) {
            return null;
        }

        // Higher skill = chance for rarer items (later items in list)
        $maxIndex = min(count($fishItems) - 1, intdiv($skillLevel, 2));
        $index = random_int(0, $maxIndex);

        return $fishItems[$index];
    }
}
