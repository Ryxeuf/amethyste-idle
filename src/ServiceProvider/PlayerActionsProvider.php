<?php

namespace App\ServiceProvider;

use App\Dto\Item\Materia;
use App\Entity\App\PlayerItem;
use App\Helper\GearHelper;

class PlayerActionsProvider
{
    /** @var GearHelper */
    protected $gearHelper;

    public function __construct(GearHelper $gearHelper)
    {
        $this->gearHelper = $gearHelper;
    }

    public function getMaterias(): iterable
    {
        if ($gear = $this->gearHelper->getFootGear()) {
            foreach ($this->addGearMateria($gear) as $item) {
                yield $item;
            }
        }
        if ($gear = $this->gearHelper->getChestGear()) {
            foreach ($this->addGearMateria($gear) as $item) {
                yield $item;
            }
        }
        if ($gear = $this->gearHelper->getHeadGear()) {
            foreach ($this->addGearMateria($gear) as $item) {
                yield $item;
            }
        }
        if ($gear = $this->gearHelper->getWeaponGear()) {
            foreach ($this->addGearMateria($gear) as $item) {
                yield $item;
            }
        }
    }

    private function addGearMateria(PlayerItem $item): iterable
    {
        foreach ($item->getSlots() as $slot) {
            if ($slot->getItemSet() && $slot->getItemSet()->getGenericItem()->getSpell()) {
                yield new Materia($slot->getItemSet());
            }
        }
    }
}
