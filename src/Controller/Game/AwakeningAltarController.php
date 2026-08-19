<?php

namespace App\Controller\Game;

use App\Entity\Game\Item;
use App\GameEngine\Repertoire\AwakeningAltar;
use App\GameEngine\Repertoire\AwakeningException;
use App\GameEngine\Settlement\SettlementGate;
use App\Helper\PlayerHelper;
use App\Repository\AwakeningRiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * L'ecran de l'Autel d'eveil (REP-04).
 *
 * **Visible avant d'etre atteignable.** L'ecran s'ouvre meme quand le foyer
 * n'est pas Metropole, et il dit ce qui manque. C'est ce que le plan demande —
 * *l'objet de desir de l'horizon de l'an se voit* —, et c'est aussi ce qui
 * repare le defaut que l'audit avait nomme : `awakening_altar` etait **gate sans
 * etre route**, donc annonce par le panneau de foyer et introuvable.
 */
#[Route('/game/awakening')]
class AwakeningAltarController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly AwakeningAltar $altar,
        private readonly AwakeningRiteRepository $rites,
        private readonly SettlementGate $gate,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_game_awakening', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game_index');
        }

        $zone = $player->getCurrentZone();
        $now = new \DateTimeImmutable();

        return $this->render('game/awakening/index.html.twig', [
            'player' => $player,
            'zone' => $zone,
            'verdict' => $zone !== null ? $this->gate->verdict($zone, AwakeningAltar::SERVICE) : null,
            'cost' => $zone !== null ? $this->altar->costAt($zone) : null,
            'awakenable' => $this->altar->awakenableBy($player),
            'perfectLots' => \count($this->altar->perfectLots($player)),
            'rite' => $this->rites->findPending($player),
            'now' => $now,
        ]);
    }

    #[Route('/start/{id}', name: 'app_game_awakening_start', methods: ['POST'])]
    public function start(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $zone = $player?->getCurrentZone();

        if ($player === null || $zone === null) {
            return $this->redirectToRoute('app_game_index');
        }

        if (!$this->isCsrfTokenValid('awakening_start', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.common.error.invalid_token');

            return $this->redirectToRoute('app_game_awakening');
        }

        $materia = $this->entityManager->getRepository(Item::class)->find($id);
        if ($materia === null) {
            $this->addFlash('error', 'game.repertoire.altar.error.not_awakenable');

            return $this->redirectToRoute('app_game_awakening');
        }

        try {
            $this->altar->start($player, $zone, $materia, new \DateTimeImmutable());
            $this->addFlash('success', 'game.repertoire.altar.started');
        } catch (AwakeningException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_awakening');
    }

    #[Route('/claim', name: 'app_game_awakening_claim', methods: ['POST'])]
    public function claim(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game_index');
        }

        if (!$this->isCsrfTokenValid('awakening_claim', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'game.common.error.invalid_token');

            return $this->redirectToRoute('app_game_awakening');
        }

        try {
            $this->altar->claim($player, new \DateTimeImmutable());
            $this->addFlash('success', 'game.repertoire.altar.claimed');
        } catch (AwakeningException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_game_awakening');
    }
}
