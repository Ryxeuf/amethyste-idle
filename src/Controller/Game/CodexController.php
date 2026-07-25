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

        // Regroupe les entrees « a collectionner » par categorie ; compte les
        // entrees debloquees. Les faits de monde (public, NAR-07) sont exclus de
        // la completion et affiches a part, chronologiquement.
        $categories = [];
        $unlockedCount = 0;
        $totalCollectible = 0;
        foreach ($entries as $entry) {
            if ($entry->isPublic()) {
                continue;
            }
            $categories[$entry->getCategory()][] = $entry;
            ++$totalCollectible;
            if (\in_array($entry->getId(), $unlockedIds, true)) {
                ++$unlockedCount;
            }
        }

        $worldFacts = $this->codexEntryRepository->findWorldFactsChronological();

        return $this->render('game/codex/index.html.twig', [
            'categories' => $categories,
            'worldFacts' => $worldFacts,
            'unlockedIds' => $unlockedIds,
            'unlockedCount' => $unlockedCount,
            'totalCount' => $totalCollectible,
            'player' => $player,
        ]);
    }
}
