<?php

namespace App\Controller\Game;

use App\Helper\PlayerHelper;
use App\Repository\CodexEntryRepository;
use App\Repository\PlayerCodexEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Ecran Codex (NAR-06) : lecture de la trame de monde. Les entrees debloquees
 * sont lisibles par categorie ; les entrees verrouillees sont teasees (titre
 * masque + indice de deblocage). Complétion affichee (n/total).
 */
#[Route('/game/codex')]
class CodexController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly CodexEntryRepository $codexEntryRepository,
        private readonly PlayerCodexEntryRepository $playerCodexEntryRepository,
    ) {
    }

    #[Route('', name: 'app_game_codex', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        $entries = $this->codexEntryRepository->findAllOrdered();
        $unlockedIds = $this->playerCodexEntryRepository->unlockedEntryIds($player);

        // Regroupe par categorie ; compte les entrees debloquees.
        $categories = [];
        $unlockedCount = 0;
        foreach ($entries as $entry) {
            $categories[$entry->getCategory()][] = $entry;
            if (\in_array($entry->getId(), $unlockedIds, true)) {
                ++$unlockedCount;
            }
        }

        return $this->render('game/codex/index.html.twig', [
            'categories' => $categories,
            'unlockedIds' => $unlockedIds,
            'unlockedCount' => $unlockedCount,
            'totalCount' => \count($entries),
            'player' => $player,
        ]);
    }
}
