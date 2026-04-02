<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerJournalEntry;
use App\Helper\PlayerHelper;
use App\Repository\PlayerJournalEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/journal')]
class JournalController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerJournalEntryRepository $journalRepository,
    ) {
    }

    #[Route('', name: 'app_game_journal', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $type = $request->query->get('type');

        if ($type !== null && !\in_array($type, PlayerJournalEntry::TYPES, true)) {
            $type = null;
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 30;
        $offset = ($page - 1) * $limit;

        $entries = $this->journalRepository->findByPlayer($player, $type, $limit, $offset);
        $total = $this->journalRepository->countByPlayer($player, $type);
        $maxPage = max(1, (int) ceil($total / $limit));

        return $this->render('game/journal/index.html.twig', [
            'entries' => $entries,
            'currentType' => $type,
            'types' => PlayerJournalEntry::TYPES,
            'typeLabels' => PlayerJournalEntry::TYPE_LABELS,
            'page' => $page,
            'maxPage' => $maxPage,
            'total' => $total,
            'player' => $player,
        ]);
    }
}
