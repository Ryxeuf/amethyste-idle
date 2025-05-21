<?php

namespace App\Controller\Game\Inventory;

use App\Entity\Game\Item;
use App\Helper\PlayerHelper;
use App\Helper\GearHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/equipment', name: 'app_game_inventory_equipment_list')]
class EquipmentController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly GearHelper $gearHelper
    ) {
    }

    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Récupérer l'inventaire du joueur
        $bagInventory = $this->playerHelper->getBagInventory();
        $player = $this->playerHelper->getPlayer();
        
        // Tableau pour stocker les équipements équipés
        $equipped = [
            Item::GEAR_LOCATION_HEAD => null,
            Item::GEAR_LOCATION_SHOULDER => null,
            Item::GEAR_LOCATION_NECK => null,
            Item::GEAR_LOCATION_CHEST => null,
            Item::GEAR_LOCATION_HAND => null,
            Item::GEAR_LOCATION_MAIN_WEAPON => null,
            Item::GEAR_LOCATION_SIDE_WEAPON => null,
            Item::GEAR_LOCATION_BELT => null,
            Item::GEAR_LOCATION_LEG => null,
            Item::GEAR_LOCATION_FOOT => null,
            Item::GEAR_LOCATION_RING_1 => null,
            Item::GEAR_LOCATION_RING_2 => null
        ];
        
        // Récupérer les équipements du joueur (équipés et non équipés)
        $equipments = [];
        
        // Parcourir tous les objets du sac pour trouver les équipements
        foreach ($bagInventory->getItems() as $item) {
            // Si c'est un équipement et qu'il n'est pas équipé
            if ($item->getGenericItem()->isGear() && !$this->gearHelper->isEquipped($item)) {
                $genericItem = $item->getGenericItem();
                $gearLocation = $genericItem->getGearLocation();
                
                // Ajouter l'équipement à la liste des équipements disponibles
                $stats = [];
                if ($genericItem->getProtection()) {
                    $stats['Défense'] = $genericItem->getProtection();
                }
                
                // Récupérer d'autres statistiques si disponibles
                
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
        
        // Récupérer les équipements équipés avec GearHelper
        foreach (array_keys($equipped) as $slotType) {
            $equippedItem = $this->gearHelper->getEquippedGearByLocation($slotType);
            if ($equippedItem) {
                $genericItem = $equippedItem->getGenericItem();
                $stats = [];
                if ($genericItem->getProtection()) {
                    $stats['Défense'] = $genericItem->getProtection();
                }
                
                $equipped[$slotType] = [
                    'id' => $equippedItem->getId(),
                    'name' => $genericItem->getName(),
                    'level' => $genericItem->getLevel() ?? 1,
                    'rarity' => $genericItem->getElement(),
                    'description' => $genericItem->getDescription(),
                    'stats' => $stats
                ];
            }
        }
        
        // Calculer les statistiques du joueur en fonction des équipements
        $stats = $this->calculatePlayerStats($player, $equipped);
        
        return $this->render('game/inventory/equipment/_list.html.twig', [
            'equipments' => $equipments,
            'equipped' => $equipped,
            'stats' => $stats
        ]);
    }
    
    /**
     * Calcule les statistiques du joueur en fonction des équipements équipés
     */
    private function calculatePlayerStats($player, array $equipped): array
    {
        // Valeurs de base du joueur
        $stats = [
            'attack' => 25,
            'defense' => 18,
            'magic' => 12,
            'speed' => 15,
            'health' => 100,
            'mana' => 50
        ];
        
        // Ajouter les bonus des équipements
        foreach ($equipped as $item) {
            if ($item !== null && isset($item['stats']['Défense'])) {
                $stats['defense'] += $item['stats']['Défense'];
            }
        }
        
        return $stats;
    }
    
    /**
     * Traduit le type d'emplacement en nom lisible
     */
    private function getSlotTypeName(string $slotType): string
    {
        return match ($slotType) {
            Item::GEAR_LOCATION_HEAD => 'Tête',
            Item::GEAR_LOCATION_NECK => 'Cou',
            Item::GEAR_LOCATION_CHEST => 'Torse',
            Item::GEAR_LOCATION_HAND => 'Mains',
            Item::GEAR_LOCATION_MAIN_WEAPON => 'Arme principale',
            Item::GEAR_LOCATION_SIDE_WEAPON => 'Arme secondaire',
            Item::GEAR_LOCATION_BELT => 'Ceinture',
            Item::GEAR_LOCATION_LEG => 'Jambes',
            Item::GEAR_LOCATION_FOOT => 'Pieds',
            Item::GEAR_LOCATION_RING_1, Item::GEAR_LOCATION_RING_2 => 'Anneau',
            Item::GEAR_LOCATION_SHOULDER => 'Épaules',
            default => 'Inconnu',
        };
    }
} 