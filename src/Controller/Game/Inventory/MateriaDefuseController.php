<?php

namespace App\Controller\Game\Inventory;

use App\GameEngine\Reputation\CounterfeitService;
use App\GameEngine\Reputation\ShadowsMarketException;
use App\Helper\PlayerHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Le desamorcage d'une contrefacon (FAC-07).
 *
 * Revere chez la Confrerie : demonter proprement une contrefacon **vue** en
 * amethyste Trouble et essence, plutot que la laisser trahir en combat. Le
 * bouton n'apparait que sur une contrefacon vue — mais l'ecran n'est pas une
 * regle metier, le service re-verifie tout.
 */
#[Route('/game/inventory/materia')]
class MateriaDefuseController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly CounterfeitService $counterfeitService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/defuse/{id}', name: 'app_game_inventory_materia_defuse', methods: ['POST'])]
    public function defuse(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $materia = $this->findOwnedMateria($id);
        if (null === $materia) {
            $this->addFlash('error', $this->translator->trans('game.materia.convert.error.not_found'));

            return $this->redirectToRoute('app_game_inventory');
        }

        try {
            $result = $this->counterfeitService->defuse($this->playerHelper->getPlayer(), $materia);
            $this->addFlash('success', $this->translator->trans('game.shadows.counterfeit.defused', [
                '%essence%' => $result['essence'],
            ]));
        } catch (ShadowsMarketException $e) {
            $this->addFlash('error', $this->translator->trans($e->getMessage()));
        }

        return $this->redirectToRoute('app_game_inventory');
    }

    /**
     * La materia doit venir de l'inventaire de materia du joueur courant : on
     * ne desamorce ni l'objet d'un autre, ni un identifiant forge.
     */
    private function findOwnedMateria(int $id): ?\App\Entity\App\PlayerItem
    {
        foreach ($this->playerHelper->getMateriaInventory()->getItems() as $item) {
            if ($item->getId() === $id && $item->isMateria()) {
                return $item;
            }
        }

        return null;
    }
}
