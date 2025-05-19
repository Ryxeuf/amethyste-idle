<?php

namespace App\Controller\Game\Inventory;

use App\Entity\App\PlayerItem;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/equipment', name: 'app_game_inventory_equipment_list')]
class EquipmentController extends AbstractController
{
    public function __construct(private readonly PlayerHelper $playerHelper)
    {
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
        
        // Récupérer les équipements du joueur (équipés et non équipés)
        $equipments = [];
        
        // Parcourir tous les objets du sac pour trouver les équipements
        foreach ($bagInventory->getItems() as $item) {
            // Si c'est un équipement et qu'il n'est pas équipé
            if ($item->getGenericItem()->isGear() && $item->getGear() === 0) {
                $genericItem = $item->getGenericItem();
                $gearLocation = $genericItem->getGearLocation();
                
                // Ajouter l'équipement à la liste des équipements disponibles
                $stats = [];
                if ($genericItem->getProtection()) {
                    $stats['Défense'] = $genericItem->getProtection();
                }
                
                // Récupérer d'autres statistiques si disponibles
                // Ceci est simpliste, vous devrez l'adapter selon votre modèle de données
                
                $equipments[] = [
                    'id' => $item->getId(),
                    'name' => $genericItem->getName(),
                    'slotType' => $gearLocation,
                    'slotTypeName' => $this->getSlotTypeName($gearLocation),
                    'level' => $genericItem->getLevel() ?? 1,
                    'rarity' => $genericItem->getElement(), // Utiliser l'élément comme rareté
                    'description' => $genericItem->getDescription(),
                    'stats' => $stats
                ];
            }
        }
        
        // Trouver les équipements équipés
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
        
        // Statistiques du personnage (exemple statique)
        // Dans un cas réel, récupérez ces valeurs depuis l'entité Player
        $stats = [
            'attack' => 25,
            'defense' => 18,
            'magic' => 12,
            'speed' => 15,
            'health' => 100,
            'mana' => 50
        ];
        
        return $this->render('game/inventory/equipment/_list.html.twig', [
            'equipments' => $equipments,
            'equipped' => $equipped,
            'stats' => $stats
        ]);
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
} 