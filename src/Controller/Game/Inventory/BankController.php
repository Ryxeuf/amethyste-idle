<?php

namespace App\Controller\Game\Inventory;

use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/bank', name: 'app_game_inventory_bank_list')]
class BankController extends AbstractController
{
    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $bankInventory = $this->playerHelper->getBankInventory();

        return $this->render('game/inventory/bank/_list.html.twig', [
            'bankItems' => $bankInventory->getItems(),
            'bankSize' => $bankInventory->getSize(),
        ]);
    }
}
