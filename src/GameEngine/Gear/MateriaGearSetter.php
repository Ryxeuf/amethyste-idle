<?php

namespace App\GameEngine\Gear;

use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Enum\Element;
use App\Enum\QuestGesture;
use App\Event\Game\PlayerGestureEvent;
use App\Exception\ItemNotEquippedException;
use App\Exception\ItemNotMateriaException;
use App\Exception\ItemRequirementsException;
use App\Exception\MateriaSlotTypeException;
use App\Helper\GearHelper;
use App\Helper\PlayerItemHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MateriaGearSetter
{
    public function __construct(private readonly GearHelper $gearHelper, private readonly EntityManagerInterface $entityManager, private readonly PlayerItemHelper $playerItemHelper, private readonly EventDispatcherInterface $eventDispatcher, private readonly SlotAcceptanceWidener $slotAcceptanceWidener)
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
        //
        // ARC-16b : une accointance `slot_acceptance` active elargit ce que
        // l'emplacement accepte — apres le refus, jamais a sa place. Elle ne
        // rend ni point ni levier : la materia se sertit, c'est tout.
        $accepted = $slot->getItem()->getGenericItem()->getMateriaSlotType();
        if (!$accepted->accepts($materia->getGenericItem()->getMateriaKind())) {
            $player = $materia->getInventory()?->getPlayer();
            if ($player === null || !$this->slotAcceptanceWidener->widens($player, $materia)) {
                throw new MateriaSlotTypeException();
            }
        }

        $slot->setItemSet($materia);
        $this->entityManager->persist($slot);
        $this->entityManager->flush();

        // ONB-12a : annonce apres le flush — un geste se constate quand il a
        // reellement eu lieu, pas quand on s'apprete a le faire.
        $this->eventDispatcher->dispatch(
            new PlayerGestureEvent(QuestGesture::SocketMateria, $this->readingsOf($materia)),
            PlayerGestureEvent::NAME,
        );
    }

    /**
     * Les lectures possibles d'une materia sertie.
     *
     * Son slug, et son element : une quete de l'acte I demande « une materia de
     * votre element », jamais un objet precis — c'est le joueur qui a choisi son
     * domaine, et la recompense en decoule (GAME_ONBOARDING § 5.2).
     *
     * @return list<string>
     */
    private function readingsOf(PlayerItem $materia): array
    {
        $generic = $materia->getGenericItem();

        $readings = [$generic->getSlug()];
        $element = $generic->getElement();
        if (Element::None !== $element) {
            $readings[] = $element->value;
        }

        return $readings;
    }

    public function unsetMateria(Slot $slot): void
    {
        $slot->setItemSet(null);
        $this->entityManager->persist($slot);
        $this->entityManager->flush();
    }
}
