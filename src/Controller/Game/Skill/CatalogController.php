<?php

namespace App\Controller\Game\Skill;

use App\GameEngine\Progression\DomainCatalogView;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Le catalogue des arbres — le premier des trois etats (ONB-09).
 *
 * *Public, complet, des la premiere minute.* Le joueur voit **la carte entiere
 * du savoir possible** et choisit deliberement ou aller ; ce que le parchemin
 * achete est le **detail technique**, pas l'existence, pas la vocation, pas la
 * possibilite.
 *
 * L'ecran est volontairement le meme pour tout le monde. Un catalogue qui ne
 * montrerait que les arbres « pertinents » orienterait — et c'est exactement ce
 * que la decision A8 ecarte.
 */
#[Route('/game/skills/catalog', name: 'app_game_skills_catalog', methods: ['GET'])]
class CatalogController extends AbstractController
{
    public function __construct(
        private readonly DomainCatalogView $catalogView,
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    public function __invoke(): Response
    {
        $player = $this->playerHelper->getPlayer();

        return $this->render('game/skills/catalog.html.twig', [
            'groups' => $this->catalogView->cardsByElement($player),
            // ARC-13b-b — ce que chaque element marque. Une phrase par groupe,
            // pas par carte : la marque appartient a l'element.
            'element_traces' => $this->catalogView->elementTraces(),
        ]);
    }
}
