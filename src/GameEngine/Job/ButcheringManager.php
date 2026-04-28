<?php

namespace App\GameEngine\Job;

use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Event\Map\ButcheringEvent;
use App\GameEngine\Event\GameEventBonusProvider;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\Helper\GearHelper;
use App\Helper\InventoryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ButcheringManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly GearHelper $gearHelper,
        private readonly GameEventBonusProvider $gameEventBonusProvider,
    ) {
    }

    /**
     * Vérifie si le joueur possède un couteau de dépeçage fonctionnel équipé.
     */
    public function canButcher(Player $player): bool
    {
        $knife = $this->getPlayerSkinningKnife($player);

        return $knife !== null && ($knife->getCurrentDurability() === null || $knife->getCurrentDurability() > 0);
    }

    /**
     * Retourne un message d'erreur précis expliquant pourquoi le joueur ne peut pas dépecer.
     */
    public function getButcherError(Player $player): string
    {
        if (!$player->hasToolSlot(Item::TOOL_TYPE_SKINNING_KNIFE)) {
            return 'Vous devez débloquer l\'emplacement de couteau de dépeçage via l\'arbre de compétences.';
        }

        $knife = $this->getPlayerSkinningKnife($player);
        if ($knife === null) {
            return 'Équipez un couteau de dépeçage dans votre emplacement d\'outil.';
        }

        if ($knife->getCurrentDurability() !== null && $knife->getCurrentDurability() <= 0) {
            return 'Votre couteau de dépeçage est cassé. Réparez-le avant de continuer.';
        }

        return 'Impossible de dépecer.';
    }

    /**
     * Dépèce un mob vaincu et retourne les matériaux obtenus.
     *
     * @return array{success: bool, items: array, message: string}
     */
    public function butcher(Player $player, Mob $mob): array
    {
        $knife = $this->getPlayerSkinningKnife($player);
        if ($knife === null || ($knife->getCurrentDurability() !== null && $knife->getCurrentDurability() <= 0)) {
            return [
                'success' => false,
                'items' => [],
                'message' => $this->getButcherError($player),
            ];
        }

        $monster = $mob->getMonster();
        $monsterItems = $monster->getMonsterItems();

        /** @var PlayerItem[] $harvestedItems */
        $harvestedItems = [];

        // Calculer le nombre de matériaux basé sur le tier de l'outil et le niveau du monstre
        $toolTier = $knife->getGenericItem()->getToolTier() ?? 1;
        $baseDropCount = 1;
        $bonusChance = min(80, $toolTier * 15); // 15-60% de chance de drop bonus

        // Le multiplicateur d'event `gathering_bonus` duplique chaque drop reussi.
        $multiplier = $this->gameEventBonusProvider->getGatheringMultiplier($player->getMap());
        $copies = max(1, (int) round($multiplier));

        foreach ($monsterItems as $monsterItem) {
            $dropChance = (int) ($monsterItem->getProbability() * 100);
            $roll = random_int(1, 100);

            // L'outil de meilleur tier améliore les chances
            $adjustedChance = min(100, $dropChance + ($toolTier * 5));

            if ($roll <= $adjustedChance) {
                for ($i = 0; $i < $copies; ++$i) {
                    try {
                        $playerItem = $this->playerItemGenerator->generateFromItemId(
                            $monsterItem->getItem()->getId()
                        );
                        $this->inventoryHelper->addItem($playerItem, false);
                        $harvestedItems[] = $playerItem;
                    } catch (\Exception) {
                        // Item non trouvé, on continue
                    }
                }
            }
        }

        // Réduire la durabilité du couteau
        $broken = $knife->reduceDurability(1);
        $this->entityManager->persist($knife);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new ButcheringEvent($player, $mob, $harvestedItems),
            ButcheringEvent::NAME
        );

        $itemNames = array_map(
            fn (PlayerItem $pi) => $pi->getGenericItem()->getName(),
            $harvestedItems
        );

        $message = count($harvestedItems) > 0
            ? 'Vous avez obtenu : ' . implode(', ', $itemNames)
            : 'Aucun matériau obtenu.';

        if ($broken) {
            $message .= ' Votre couteau de dépeçage est brisé !';
        }

        return [
            'success' => count($harvestedItems) > 0,
            'items' => array_map(fn (PlayerItem $pi) => [
                'name' => $pi->getGenericItem()->getName(),
                'slug' => $pi->getGenericItem()->getSlug(),
            ], $harvestedItems),
            'message' => $message,
        ];
    }

    /**
     * Récupère le couteau de dépeçage équipé dans l'emplacement d'outil.
     */
    private function getPlayerSkinningKnife(Player $player): ?PlayerItem
    {
        return $this->gearHelper->getEquippedToolByType(Item::TOOL_TYPE_SKINNING_KNIFE);
    }
}
