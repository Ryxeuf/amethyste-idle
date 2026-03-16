<?php

namespace App\Controller\Game\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\GameEngine\Fight\StatusEffectManager;
use App\Helper\PlayerHelper;
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

        /** @var Fight $fight */
        $fight = $this->entityManager->getRepository(Fight::class)->find($fightId);
        if (!$fight) {
            return new JsonResponse(['error' => 'Fight not found'], Response::HTTP_NOT_FOUND);
        }

        $player = $this->playerHelper->getPlayer();
        if (!$player) {
            return new JsonResponse(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }

        $player->setFight(null);
        $this->entityManager->persist($player);
        foreach ($fight->getMobs() as $mob) {
            /* @var Mob $mob */
            $this->entityManager->remove($mob);
        }
        foreach ($fight->getPlayers() as $player) {
            /* @var Player $player */
            $player->setFight(null);
            $this->entityManager->persist($player);
        }
        $this->statusEffectManager->clearAllEffects($fight);
        $this->entityManager->remove($fight);

        // $player->addItems($items);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
