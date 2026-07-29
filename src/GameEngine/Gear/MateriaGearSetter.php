<?php

namespace App\GameEngine\Gear;

use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Exception\ItemNotEquippedException;
use App\Exception\ItemNotMateriaException;
use App\Exception\ItemRequirementsException;
use App\Exception\MateriaSlotTypeException;
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
     * @throws MateriaSlotTypeException
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

        // DOM-03 : la piece decide de ce que ses emplacements acceptent. Le
        // refus porte sur le **sertissage**, jamais sur le port : rien
        // n'empeche de porter la piece, et c'est la difference entre un
        // emplacement type et une classe (GAME_DOMAINS § 3, garde-fou 1).
        $accepted = $slot->getItem()->getGenericItem()->getMateriaSlotType();
        if (!$accepted->accepts($materia->getGenericItem()->getMateriaKind())) {
            throw new MateriaSlotTypeException();
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
