<?php

namespace App\Controller\Game;

use App\Entity\App\Player;
use App\GameEngine\Zone\PlayerZoneSynchronizer;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerZoneSynchronizer $playerZoneSynchronizer,
    ) {
    }

    #[Route('/game', name: 'app_game')]
    public function __invoke(): Response
    {
        $player = $this->playerHelper->getPlayer();

        if (!$player) {
            return $this->redirectToRoute('app_home');
        }

        // Equipped items (gear > 0)
        $equippedItems = [];
        $bagInventory = $this->playerHelper->getBagInventory();
        foreach ($bagInventory->getItems() as $playerItem) {
            if ($playerItem->getGear() > 0) {
                $equippedItems[] = $playerItem;
            }
        }

        // Inventory stats
        $bagSize = $bagInventory->getSize();
        $bagUsed = $bagInventory->getOccupiedSpace();

        // Domain experiences
        $domainExperiences = $player->getDomainExperiences();

        // Active quests
        $activeQuests = $player->getQuests();

        // Skills count
        $skillsCount = $player->getSkills()->count();

        // Aventuriers presents dans la meme zone (position de reference depuis
        // le pivot, regle #7) : le comptage par carte laissait le compteur a 0
        // pour toute zone creee sans carte d'origine.
        // Le tableau de bord est le premier ecran apres connexion : c'est ici
        // qu'un joueur sans position doit en recevoir une, sans quoi il lit
        // « position inconnue » avant meme d'avoir ouvert l'ecran de zone.
        $zone = $this->playerZoneSynchronizer->resolveOrAssign($player, true);

        $playersInZone = 0;
        if (null !== $zone) {
            $playersInZone = max(0, $this->entityManager->getRepository(Player::class)
                ->count(['currentZone' => $zone]) - 1); // hors soi-meme
        }

        return $this->render('game/index.html.twig', [
            'player' => $player,
            'equippedItems' => $equippedItems,
            'bagSize' => $bagSize,
            'bagUsed' => $bagUsed,
            'domainExperiences' => $domainExperiences,
            'activeQuests' => $activeQuests,
            'skillsCount' => $skillsCount,
            'zone' => $zone,
            'playersInZone' => $playersInZone,
        ]);
    }
}
