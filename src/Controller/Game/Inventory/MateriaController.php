<?php

namespace App\Controller\Game\Inventory;

use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/materia', name: 'app_game_inventory_materia_list')]
class MateriaController extends AbstractController
{
    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    public function __invoke(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Récupérer l'inventaire de materia du joueur
        $materiaInventory = $this->playerHelper->getMateriaInventory();

        // Récupérer les objets materia de l'inventaire
        $playerItems = $materiaInventory->getItems();

        // Transformer les objets PlayerItem en tableau pour la vue
        $materias = [];
        foreach ($playerItems as $item) {
            if ($item->isMateria()) {
                $genericItem = $item->getGenericItem();
                $materias[] = [
                    'id' => $item->getId(),
                    'name' => $genericItem->getName(),
                    'level' => $genericItem->getLevel() ?? 1,
                    'element' => $genericItem->getElement()->value,
                    'rarity' => $genericItem->getRarity(),
                    'description' => $genericItem->getDescription(),
                    'effects' => $genericItem->getEffect() ?? '',
                ];
            }
        }

        return $this->render('game/inventory/materia/_list.html.twig', [
            'materias' => $materias,
        ]);
    }
}
