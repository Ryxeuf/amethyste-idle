<?php

namespace App\Transformer;

use App\Dto\Fight\FightModel;
use App\Dto\Fight\FightPlayer;
use App\Dto\Item\Materia;
use App\Dto\Item\UsableItem;
use App\Dto\Mob\MobModel;
use App\Entity\App\Fight as FightEntity;
use App\Entity\App\Mob as MobEntity;
use App\Entity\App\PlayerItem;
use App\GameEngine\Fight\FightNotificationHandler;
use App\Helper\FightTimelineHelper;
use App\Helper\GearHelper;
use App\Helper\ItemHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Generator;

class FightModelTransformer
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly FightTimelineHelper $timelineHelper,
        private readonly GearHelper $gearHelper,
        private readonly FightNotificationHandler $notificationHandler,
        private readonly ItemHelper $itemHelper
    )
    {
    }

    public function transform(FightEntity $fight): FightModel
    {
        $output = new FightModel();
        $output->id = $fight->getId();
        $output->mob = new MobModel($fight->getMob());
        $allPlayersDead = true;
        foreach ($fight->getPlayers() as $player) {
            $output->players[] = new FightPlayer($player);
            $allPlayersDead = $allPlayersDead && $player->isDead();
        }

        $output->terminated = $fight->getMob()->isDead() || $allPlayersDead;
        $output->victory = $fight->getMob()->isDead() && !$allPlayersDead;

        if ($output->victory) {
            $output->loot = $this->getLoots($fight->getMob());
        } elseif (!$output->terminated) {
            $output->timeline = $this->timelineHelper->getCurrentTimeline($fight);
            foreach ($this->getItems() as $item) {
                $output->items[] = $item;
            }

            foreach ($this->getSpells() as $spell) {
                $output->spells[] = $spell;
            }
        }
        $output->notifications = $this->notificationHandler->getNotifications();

        return $output;
    }

    protected function getLoots(MobEntity $mob)
    {
        foreach ($mob->getItems() as $item) {
            yield new UsableItem($item);
        }
    }

    protected function getItems()
    {
        foreach ($this->playerHelper->getUsableItems() as $item) {
            yield new UsableItem($item);
        }
    }

    protected function getSpells(): ?Generator
    {
        if ($this->gearHelper->getFootGear()){
            foreach ($this->getGearMaterias($this->gearHelper->getFootGear()) as $item) {
                yield $item;
            }
        }
        if ($this->gearHelper->getChestGear()){
            foreach ($this->getGearMaterias($this->gearHelper->getChestGear()) as $item) {
                yield $item;
            }
        }
        if ($this->gearHelper->getHeadGear()){
            foreach ($this->getGearMaterias($this->gearHelper->getHeadGear()) as $item) {
                yield $item;
            }
        }
        if ($this->gearHelper->getWeaponGear()){
            foreach ($this->getGearMaterias($this->gearHelper->getWeaponGear()) as $item) {
                yield $item;
            }
        }
    }

    private function getGearMaterias(PlayerItem $item)
    {
        foreach ($item->getSlots() as $slot) {
            if ($slot->getItemSet() && $this->itemHelper->getItemSpell($slot->getItemSet()->getGenericItem())){
                yield new Materia($slot->getItemSet());
            }
        }
    }
}