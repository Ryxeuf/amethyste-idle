<?php

namespace App\GameEngine\Gear;

use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Exception\ItemNotEquippedException;
use App\Exception\ItemNotMateriaException;
use App\Exception\ItemRequirementsException;
use App\Helper\GearHelper;
use App\Helper\PlayerItemHelper;
use Doctrine\ORM\EntityManagerInterface;

class MateriaGearSetter
{
    public function __construct(private readonly GearHelper $gearHelper, private readonly EntityManagerInterface $entityManager, private readonly PlayerItemHelper $playerItemHelper)
    {
    }

    /**
     * @throws ItemNotEquippedException
     * @throws ItemNotMateriaException
     * @throws ItemRequirementsException
     */
    public function setMateria(PlayerItem $materia, Slot $slot): void
    {
        if (!$this->gearHelper->isEquipped($slot->getItem())) {
            throw new ItemNotEquippedException();
        }
        if (!$materia->isMateria()) {
            throw new ItemNotMateriaException();
        }

        if (!$this->playerItemHelper->canEquipMateria($materia)) {
            throw new ItemRequirementsException();
        }

        $slot->setItemSet($materia);
        $this->entityManager->persist($slot);
        $this->entityManager->flush();
    }

    public function unsetMateria(Slot $slot): void
    {
        $slot->setItemSet(null);
        $this->entityManager->persist($slot);
        $this->entityManager->flush();
    }
}
