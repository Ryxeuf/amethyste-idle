<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use App\Entity\Game\FactionReward;
use App\GameEngine\Reputation\FactionTensionCatalog;
use App\GameEngine\Reputation\FoundryContractException;
use App\GameEngine\Reputation\FoundryContractManager;
use App\GameEngine\Reputation\PatronageException;
use App\GameEngine\Reputation\PatronageService;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/game/factions')]
class FactionController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly FactionTensionCatalog $tensionCatalog,
        private readonly PatronageService $patronageService,
        private readonly FoundryContractManager $foundryContractManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('', name: 'app_game_factions', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        $factions = $this->entityManager->getRepository(Faction::class)->findAll();

        $playerFactions = $this->entityManager->getRepository(PlayerFaction::class)->findBy(['player' => $player]);
        $playerFactionMap = [];
        foreach ($playerFactions as $pf) {
            $playerFactionMap[$pf->getFaction()->getId()] = $pf;
        }

        $allRewards = $this->entityManager->getRepository(FactionReward::class)->findBy([], ['requiredTier' => 'ASC']);
        $rewardsByFaction = [];
        foreach ($allRewards as $reward) {
            $factionId = $reward->getFaction()->getId();
            $rewardsByFaction[$factionId][] = $reward;
        }

        // FAC-05 — l'affiche de la semaine sur la carte de la Fonderie. La
        // lecture tire paresseusement le contrat s'il manque : une affiche
        // absente un lundi matin serait un silence.
        $foundryContract = $this->foundryContractManager->current();

        return $this->render('game/factions/index.html.twig', [
            'factions' => $factions,
            'playerFactionMap' => $playerFactionMap,
            'rewardsByFaction' => $rewardsByFaction,
            'player' => $player,
            'axisByFaction' => $this->axisByFaction($factions),
            'patron' => $player->getPatronFaction(),
            'patronageTier' => $this->tensionCatalog->patronageTier(),
            'tensionTier' => $this->tensionCatalog->beyondTier(),
            'tensionPercent' => $this->tensionCatalog->offsetPercent(),
            'foundryContract' => $foundryContract,
            'foundryContractItem' => $this->entityManager->getRepository(\App\Entity\Game\Item::class)
                ->findOneBy(['slug' => $foundryContract->getItemSlug()]),
            'foundryContractBlocker' => $this->foundryContractManager->blocker($player, $foundryContract),
            'foundryContractDelivered' => null !== $this->foundryContractManager->fulfillmentOf($player, $foundryContract),
        ]);
    }

    /**
     * Honorer le contrat d'approvisionnement de la semaine (FAC-05).
     */
    #[Route('/foundry-contract/deliver', name: 'app_game_factions_foundry_deliver', methods: ['POST'])]
    public function deliverFoundryContract(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('foundry_contract', (string) $request->request->get('_token'))) {
            $this->addFlash('warning', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_game_factions');
        }

        try {
            $result = $this->foundryContractManager->deliver($player);
        } catch (FoundryContractException $e) {
            $this->addFlash('warning', $this->translator->trans($e->getMessage()));

            return $this->redirectToRoute('app_game_factions');
        }

        $this->addFlash('success', sprintf('Contrat honore : +%d gils, +%d essence.', $result['gils'], $result['essence']));

        return $this->redirectToRoute('app_game_factions');
    }

    /**
     * Porter les couleurs d'une faction (FAC-01).
     */
    #[Route('/patron/{id}', name: 'app_game_factions_patron', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function patron(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('faction_patron', (string) $request->request->get('_token'))) {
            $this->addFlash('warning', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_game_factions');
        }

        $faction = $this->entityManager->getRepository(Faction::class)->find($id);
        if ($faction === null) {
            $this->addFlash('warning', 'Faction inconnue.');

            return $this->redirectToRoute('app_game_factions');
        }

        try {
            $this->patronageService->choose($player, $faction);
        } catch (PatronageException $e) {
            // Le motif est rendu, jamais avale : « pas encore assez proche » se
            // leve en jouant, « pas pendant un combat » en finissant le tour, et
            // un message unique enverrait la moitie des joueurs au mauvais
            // endroit.
            $this->addFlash('warning', $this->refusalMessage($e));

            return $this->redirectToRoute('app_game_factions');
        }

        $this->addFlash('success', sprintf('Vous portez desormais les couleurs de %s.', $faction->getName()));

        return $this->redirectToRoute('app_game_factions');
    }

    /**
     * N'en porter aucune (FAC-01).
     */
    #[Route('/patron/clear', name: 'app_game_factions_patron_clear', methods: ['POST'])]
    public function clearPatron(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$this->isCsrfTokenValid('faction_patron', (string) $request->request->get('_token'))) {
            $this->addFlash('warning', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_game_factions');
        }

        try {
            $this->patronageService->clear($player);
        } catch (PatronageException $e) {
            $this->addFlash('warning', $this->refusalMessage($e));

            return $this->redirectToRoute('app_game_factions');
        }

        $this->addFlash('success', 'Vous ne portez plus aucune couleur.');

        return $this->redirectToRoute('app_game_factions');
    }

    private function refusalMessage(PatronageException $e): string
    {
        return match ($e->reason) {
            PatronageException::REASON_IN_COMBAT => 'On ne change pas de couleurs au milieu d\'un combat.',
            default => sprintf('Il faut etre au moins %s d\'une faction pour en porter les couleurs.', $this->tensionCatalog->patronageTier()->label()),
        };
    }

    /**
     * L'axe de chaque faction : son oppose, ou son absence d'oppose.
     *
     * **L'absence d'oppose est une information**, pas un vide : la Guilde des
     * Marchands vend aux deux camps, et c'est son identite (GAME_WORLD § 6.4 a).
     * Ne rien afficher la ou il n'y a pas d'opposition laisserait croire a un
     * oubli.
     *
     * @param list<Faction> $factions
     *
     * @return array<int, array{axis: string|null, opponent: string|null, neutral: bool}>
     */
    private function axisByFaction(array $factions): array
    {
        $namesBySlug = [];
        foreach ($factions as $faction) {
            $namesBySlug[$faction->getSlug()] = $faction->getName();
        }

        $axis = [];
        foreach ($factions as $faction) {
            $opponentSlug = $this->tensionCatalog->opponentOf($faction->getSlug());

            $axis[$faction->getId()] = [
                'axis' => $this->tensionCatalog->axisOf($faction->getSlug()),
                // Une paire dont un membre n'est pas encore seme reste
                // affichable : l'axe se lit, l'oppose se nommera le jour ou il
                // existera (la Fonderie arrive avec FAC-04).
                'opponent' => $opponentSlug === null ? null : ($namesBySlug[$opponentSlug] ?? null),
                'neutral' => $opponentSlug === null,
            ];
        }

        return $axis;
    }
}
