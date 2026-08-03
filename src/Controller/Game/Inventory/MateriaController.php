<?php

namespace App\Controller\Game\Inventory;

use App\GameEngine\Reputation\CounterfeitService;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/materia', name: 'app_game_inventory_materia_list')]
class MateriaController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly CounterfeitService $counterfeitService,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        // Récupérer l'inventaire de materia du joueur
        $materiaInventory = $this->playerHelper->getMateriaInventory();

        // Récupérer les objets materia de l'inventaire
        $playerItems = $materiaInventory->getItems();

        // Transformer et grouper les materia par slug+element+level
        $locale = $request->getLocale();
        $grouped = [];
        foreach ($playerItems as $item) {
            if ($item->isMateria()) {
                $genericItem = $item->getGenericItem();
                // FAC-07 : une contrefacon VUE (identifiee, ou percee par
                // l'œil du faussaire) sort du groupe des authentiques — sans
                // ca, aucun bouton ne pourrait la designer. Une contrefacon
                // non vue reste dans le groupe : indiscernable, par canon.
                $seenCounterfeit = null !== $player && $this->counterfeitService->eyeSees($player, $item);
                $key = $genericItem->getSlug() . '_' . $genericItem->getElement()->value . '_' . ($genericItem->getLevel() ?? 1)
                    . ($seenCounterfeit ? '_fake' : '');
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'id' => $item->getId(),
                        'name' => $genericItem->getLocalizedName($locale),
                        'level' => $genericItem->getLevel() ?? 1,
                        'element' => $genericItem->getElement()->value,
                        'rarity' => $genericItem->getRarity(),
                        'description' => $genericItem->getLocalizedDescription($locale),
                        'effects' => $genericItem->getEffect() ?? '',
                        'quantity' => 0,
                        'counterfeit' => $seenCounterfeit,
                    ];
                }
                ++$grouped[$key]['quantity'];
            }
        }
        $materias = array_values($grouped);

        return $this->render('game/inventory/materia/_list.html.twig', [
            'materias' => $materias,
            // FAC-04b : le solde d'essence s'affiche la ou on la gagne.
            'essence' => $player?->getEssence() ?? 0,
        ]);
    }
}
