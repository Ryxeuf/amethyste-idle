<?php

namespace App\Controller\Game\Inventory;

use App\Entity\App\PlayerItem;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

#[Route('/game/inventory/equipment/equip/{id}', name: 'app_game_inventory_equipment_equip', methods: ['POST'])]
class EquipItemController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(int $id): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Récupérer l'inventaire du joueur
        $bagInventory = $this->playerHelper->getBagInventory();
        
        // Trouver l'item à équiper
        $itemToEquip = null;
        foreach ($bagInventory->getItems() as $item) {
            if ($item->getId() === $id) {
                $itemToEquip = $item;
                break;
            }
        }
        
        // Si l'item n'existe pas, renvoyer une erreur
        if (!$itemToEquip) {
            throw $this->createNotFoundException('Item non trouvé');
        }
        
        // Vérifier que l'item est un équipement
        if (!$itemToEquip->getGenericItem()->isGear()) {
            throw new \LogicException('Cet item n\'est pas un équipement');
        }
        
        // Récupérer le type d'emplacement de l'équipement
        $slotType = $itemToEquip->getGenericItem()->getGearLocation();
        
        // Récupérer la valeur binaire correspondant à l'emplacement
        $gearValue = $this->getGearValueBySlotType($slotType);
        
        // Vérifier si un équipement est déjà équipé à cet emplacement
        $currentEquippedItem = null;
        foreach ($bagInventory->getItems() as $item) {
            if ($item->getGenericItem()->isGear() && 
                $item->getGear() > 0 && 
                $item->getGenericItem()->getGearLocation() === $slotType) {
                $currentEquippedItem = $item;
                break;
            }
        }
        
        // Si un item est déjà équipé, le déséquiper
        if ($currentEquippedItem) {
            $currentEquippedItem->setGear(0);
            $this->entityManager->persist($currentEquippedItem);
        }
        
        // Équiper le nouvel item
        $itemToEquip->setGear($gearValue);
        $this->entityManager->persist($itemToEquip);
        $this->entityManager->flush();
        
        // Retourner la mise à jour de l'inventaire
        return $this->render('game/inventory/equipment/_list.html.twig', [
            'equipments' => $this->getAvailableEquipments($bagInventory),
            'equipped' => $this->getEquippedItems($bagInventory),
            'stats' => $this->getPlayerStats()
        ]);
    }
    
    /**
     * Récupère tous les équipements disponibles dans l'inventaire
     */
    private function getAvailableEquipments($bagInventory): array
    {
        $equipments = [];
        
        foreach ($bagInventory->getItems() as $item) {
            if ($item->getGenericItem()->isGear() && $item->getGear() === 0) {
                $genericItem = $item->getGenericItem();
                $gearLocation = $genericItem->getGearLocation();
                
                $stats = [];
                if ($genericItem->getProtection()) {
                    $stats['Défense'] = $genericItem->getProtection();
                }
                
                $equipments[] = [
                    'id' => $item->getId(),
                    'name' => $genericItem->getName(),
                    'slotType' => $gearLocation,
                    'slotTypeName' => $this->getSlotTypeName($gearLocation),
                    'level' => $genericItem->getLevel() ?? 1,
                    'rarity' => $genericItem->getElement(),
                    'description' => $genericItem->getDescription(),
                    'stats' => $stats
                ];
            }
        }
        
        return $equipments;
    }
    
    /**
     * Récupère tous les équipements équipés
     */
    private function getEquippedItems($bagInventory): array
    {
        $equipped = [
            'head' => null,
            'shoulder' => null,
            'neck' => null,
            'chest' => null,
            'hand' => null,
            'main_weapon' => null,
            'side_weapon' => null,
            'belt' => null,
            'leg' => null,
            'foot' => null,
            'ring_1' => null,
            'ring_2' => null
        ];
        
        foreach ($bagInventory->getItems() as $item) {
            if ($item->getGenericItem()->isGear() && $item->getGear() > 0) {
                $genericItem = $item->getGenericItem();
                $gearLocation = $genericItem->getGearLocation();
                
                if ($gearLocation && isset($equipped[$gearLocation])) {
                    $equipped[$gearLocation] = [
                        'id' => $item->getId(),
                        'name' => $genericItem->getName()
                    ];
                }
            }
        }
        
        return $equipped;
    }
    
    /**
     * Récupère les statistiques du joueur
     */
    private function getPlayerStats(): array
    {
        // Ici, vous pourriez calculer les statistiques en fonction des équipements
        // Pour l'instant, nous utilisons des valeurs statiques
        return [
            'attack' => 25,
            'defense' => 18,
            'magic' => 12,
            'speed' => 15,
            'health' => 100,
            'mana' => 50
        ];
    }
    
    /**
     * Traduit le type d'emplacement en nom lisible
     */
    private function getSlotTypeName(string $slotType): string
    {
        return match ($slotType) {
            'head' => 'Tête',
            'neck' => 'Cou',
            'chest' => 'Torse',
            'hand' => 'Mains',
            'main_weapon' => 'Arme principale',
            'side_weapon' => 'Arme secondaire',
            'belt' => 'Ceinture',
            'leg' => 'Jambes',
            'foot' => 'Pieds',
            'ring_1', 'ring_2' => 'Anneau',
            'shoulder' => 'Épaules',
            default => 'Inconnu',
        };
    }
    
    /**
     * Convertit le type d'emplacement en valeur binaire
     */
    private function getGearValueBySlotType(string $slotType): int
    {
        return match ($slotType) {
            'head' => PlayerItem::GEAR_HEAD,
            'shoulder' => PlayerItem::GEAR_SHOULDER,
            'neck' => PlayerItem::GEAR_NECK,
            'chest' => PlayerItem::GEAR_CHEST,
            'hand' => PlayerItem::GEAR_HAND,
            'main_weapon' => PlayerItem::GEAR_MAIN_WEAPON,
            'side_weapon' => PlayerItem::GEAR_SIDE_WEAPON,
            'belt' => PlayerItem::GEAR_BELT,
            'leg' => PlayerItem::GEAR_LEG,
            'foot' => PlayerItem::GEAR_FOOT,
            'ring_1' => PlayerItem::GEAR_RING_1,
            'ring_2' => PlayerItem::GEAR_RING_2,
            default => 0,
        };
    }
} 