<?php

namespace App\Controller\Game\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\GameEngine\Dungeon\DungeonManager;
use App\GameEngine\Fight\CombatLogArchiver;
use App\GameEngine\Fight\StatusEffectManager;
use App\Helper\InventoryHelper;
use App\Helper\PlayerHelper;
use App\Repository\DungeonRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight/loot/proceed', name: 'app_game_fight_loot_proceed', methods: ['POST'])]
class FightLootProceedController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly StatusEffectManager $statusEffectManager,
        private readonly CombatLogArchiver $combatLogArchiver,
        private readonly DungeonRunRepository $dungeonRunRepository,
        private readonly DungeonManager $dungeonManager,
        private readonly InventoryHelper $inventoryHelper,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['fightId']) || !isset($data['items'])) {
            return new JsonResponse(['error' => 'Invalid request data'], Response::HTTP_BAD_REQUEST);
        }

        $fightId = $data['fightId'];
        $items = $data['items'];

        $fight = $this->entityManager->getRepository(Fight::class)->find($fightId);
        if (!$fight instanceof Fight) {
            return new JsonResponse(['error' => 'Fight not found'], Response::HTTP_NOT_FOUND);
        }

        $player = $this->playerHelper->getPlayer();

        // Transférer les items de loot sélectionnés vers l'inventaire du joueur
        $selectedItemIds = array_map('intval', $items);
        foreach ($fight->getMobs() as $mob) {
            foreach ($mob->getItems()->toArray() as $item) {
                // En coop/world boss, ne gérer que les items liés à ce joueur
                if (($fight->isWorldBossFight() || $fight->isCoopFight())
                    && $item->getBoundToPlayerId() !== $player->getId()) {
                    continue;
                }

                if (\in_array($item->getId(), $selectedItemIds, true)) {
                    $item->setMob(null);
                    $mob->removeItem($item);
                    $this->inventoryHelper->addItem($item, false);
                } else {
                    $mob->removeItem($item);
                    $this->entityManager->remove($item);
                }
            }
        }

        // Détacher ce joueur du combat
        $player->setFight(null);
        $this->entityManager->persist($player);
        $fight->removePlayer($player);

        // Si c'est un world boss ou coop et qu'il reste des joueurs, ne pas supprimer le combat
        $remainingPlayers = $fight->getPlayers()->filter(fn (Player $p) => $p->getFight() !== null);
        $isMultiPlayer = $fight->isWorldBossFight() || $fight->isCoopFight();

        if ($isMultiPlayer && $remainingPlayers->count() > 0) {
            $this->entityManager->flush();

            return new JsonResponse(['success' => true]);
        }

        // Dernier joueur ou combat classique : nettoyage complet
        $this->combatLogArchiver->archive($fight);

        foreach ($fight->getPlayers() as $remainingPlayer) {
            $remainingPlayer->setFight(null);
            $this->entityManager->persist($remainingPlayer);
        }

        foreach ($fight->getMobs() as $mob) {
            foreach ($mob->getItems()->toArray() as $remainingItem) {
                $this->entityManager->remove($remainingItem);
            }
            $this->entityManager->remove($mob);
        }

        $this->statusEffectManager->clearAllEffects($fight);
        $this->entityManager->remove($fight);
        $this->entityManager->flush();

        // Teleport player out of dungeon if run is completed
        $completedRun = $this->dungeonRunRepository->findLastCompletedRunForPlayer($player);
        if ($completedRun !== null && $completedRun->getOriginMap() !== null) {
            $this->dungeonManager->teleportPlayerBack($completedRun);
        }

        return new JsonResponse(['success' => true]);
    }
}
