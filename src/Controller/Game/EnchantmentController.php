<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerItem;
use App\GameEngine\Item\EnchantmentManager;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/game/craft')]
#[IsGranted('ROLE_USER')]
class EnchantmentController extends AbstractController
{
    public function __construct(
        private readonly EnchantmentManager $enchantmentManager,
        private readonly PlayerHelper $playerHelper,
        private readonly GearHelper $gearHelper,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/enchant', name: 'app_game_craft_enchant')]
    public function index(): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        $definitions = $this->enchantmentManager->getDefinitions();
        $canApply = [];
        $hasSkill = [];

        foreach ($definitions as $type => $def) {
            $canApply[$type] = $this->enchantmentManager->canApply($player, $type);
            $hasSkill[$type] = $this->enchantmentManager->hasRequiredSkill($player, $type);
        }

        // Collect equipped gear items
        $equippedItems = [];
        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }
            foreach ($inventory->getItems() as $playerItem) {
                if ($this->gearHelper->isEquipped($playerItem) && $playerItem->isGear()) {
                    $enchantment = $this->enchantmentManager->getActiveEnchantment($playerItem);
                    $equippedItems[] = [
                        'playerItem' => $playerItem,
                        'enchantment' => $enchantment,
                    ];
                }
            }
        }

        return $this->render('game/crafting/enchant.html.twig', [
            'definitions' => $definitions,
            'canApply' => $canApply,
            'hasSkill' => $hasSkill,
            'equippedItems' => $equippedItems,
        ]);
    }

    #[Route('/enchant/apply', name: 'app_game_craft_enchant_apply', methods: ['POST'])]
    public function apply(Request $request): Response
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->redirectToRoute('app_game');
        }

        $enchantType = $request->request->getString('enchant_type');
        $playerItemId = $request->request->getInt('player_item_id');

        if ($enchantType === '' || $playerItemId === 0) {
            $this->addFlash('warning', 'Parametres invalides.');

            return $this->redirectToRoute('app_game_craft_enchant');
        }

        // Verify skill
        if (!$this->enchantmentManager->hasRequiredSkill($player, $enchantType)) {
            $this->addFlash('warning', 'Vous ne possedez pas la competence requise.');

            return $this->redirectToRoute('app_game_craft_enchant');
        }

        // Find the player item
        $playerItem = $this->em->getRepository(PlayerItem::class)->find($playerItemId);
        if ($playerItem === null) {
            $this->addFlash('warning', 'Objet introuvable.');

            return $this->redirectToRoute('app_game_craft_enchant');
        }

        // Verify item belongs to player and is equipped
        if (!$this->gearHelper->isEquipped($playerItem)) {
            $this->addFlash('warning', 'L\'objet doit etre equipe.');

            return $this->redirectToRoute('app_game_craft_enchant');
        }

        // Verify item is a valid target
        if (!$this->enchantmentManager->isValidTarget($playerItem, $enchantType)) {
            $this->addFlash('warning', 'Cet enchantement ne peut pas etre applique sur cet objet.');

            return $this->redirectToRoute('app_game_craft_enchant');
        }

        // Verify ingredients
        $check = $this->enchantmentManager->canApply($player, $enchantType);
        if (!$check['possible']) {
            $this->addFlash('warning', 'Ingredients manquants.');

            return $this->redirectToRoute('app_game_craft_enchant');
        }

        // Consume ingredients and apply
        $this->enchantmentManager->consumeIngredients($player, $enchantType);
        $enchantment = $this->enchantmentManager->apply($playerItem, $enchantType);
        $this->em->flush();

        $this->addFlash('success', sprintf(
            'Enchantement "%s" applique sur %s ! (Duree : %d min)',
            $enchantment->getName(),
            $playerItem->getGenericItem()->getName(),
            (int) ceil($enchantment->getRemainingSeconds() / 60)
        ));

        return $this->redirectToRoute('app_game_craft_enchant');
    }
}
