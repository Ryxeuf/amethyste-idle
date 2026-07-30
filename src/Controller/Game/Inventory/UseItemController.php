<?php

namespace App\Controller\Game\Inventory;

use App\Entity\App\PlayerItem;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Progression\DomainAccessManager;
use App\GameEngine\Progression\SkillAcquiring;
use App\Helper\InventoryHelper;
use App\Helper\ItemHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/inventory/items/use/{id}', name: 'app_game_inventory_items_use', methods: ['POST'])]
class UseItemController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly ItemHelper $itemHelper,
        private readonly InventoryHelper $inventoryHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly SkillAcquiring $skillAcquiring,
        private readonly PlayerSkillHelper $playerSkillHelper,
        private readonly SpellApplicator $spellApplicator,
        private readonly DomainAccessManager $domainAccessManager,
    ) {
    }

    public function __invoke(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($id);

        if (!$playerItem || !$this->inventoryHelper->hasItem($playerItem)) {
            $this->addFlash('error', 'Objet introuvable.');

            return $this->redirectToRoute('app_game_inventory_items_list');
        }

        $item = $playerItem->getGenericItem();

        if (!$item->isObject()) {
            $this->addFlash('error', 'Cet objet ne peut pas être utilisé.');

            return $this->redirectToRoute('app_game_inventory_items_list');
        }

        // Vérifier que l'objet est utilisable (sort ou apprentissage de compétence)
        if (!$this->itemHelper->isUsable($item)) {
            $this->addFlash('error', 'Cet objet ne peut pas être utilisé.');

            return $this->redirectToRoute('app_game_inventory_items_list');
        }

        // Vérifier que le joueur n'est pas en combat
        if ($player->getFight()) {
            $this->addFlash('error', 'Vous ne pouvez pas utiliser d\'objet pendant un combat.');

            return $this->redirectToRoute('app_game_inventory_items_list');
        }

        // Vérifier que le joueur n'est pas mort
        if ($player->isDead()) {
            $this->addFlash('error', 'Vous ne pouvez pas utiliser d\'objet étant mort.');

            return $this->redirectToRoute('app_game_inventory_items_list');
        }

        // Cas 1 : Sort lié (potion de soin, etc.) → déléguer au SpellApplicator
        if ($spell = $this->itemHelper->getItemSpell($item)) {
            $modifiers = $this->itemHelper->getItemSpellModifiers($item, $player);
            $this->spellApplicator->apply($spell, $player, $player, $modifiers);
            $this->addFlash('success', sprintf('Vous utilisez %s.', $item->getName()));
        }
        // Cas 2 : Apprentissage de compétence (parchemin)
        elseif ($skill = $this->itemHelper->getItemSkillLearning($item)) {
            if ($this->playerSkillHelper->hasSkill($skill)) {
                $this->addFlash('error', 'Vous connaissez déjà cette compétence.');

                return $this->redirectToRoute('app_game_inventory_items_list');
            }

            $this->skillAcquiring->acquireSkill($skill);
            $this->addFlash('success', sprintf('Vous étudiez %s et apprenez la compétence « %s » !', $item->getName(), $skill->getTitle()));
        }
        // Cas 3 : Ouverture d'un arbre (parchemin de domaine, ONB-08)
        elseif ($domain = $this->itemHelper->getItemDomainOpening($item)) {
            // Relire un parchemin deja lu ne consomme rien : l'objet garde sa
            // valeur marchande, et le joueur n'est pas puni d'un double clic.
            if (!$this->domainAccessManager->open($player, $domain)) {
                $this->addFlash('warning', sprintf('Vous connaissez déjà la voie du %s.', $domain->getTitle()));

                return $this->redirectToRoute('app_game_inventory_items_list');
            }

            $this->addFlash('success', sprintf('Vous déchiffrez %s : l\'arbre du %s s\'ouvre à vous.', $item->getName(), $domain->getTitle()));
        }

        // Décrémenter les usages
        $nbUsages = $playerItem->getNbUsages();
        if ($nbUsages > 0) {
            $playerItem->setNbUsages($nbUsages - 1);
            if ($playerItem->getNbUsages() <= 0) {
                $this->entityManager->remove($playerItem);
            }
        } elseif ($nbUsages == 0) {
            $this->entityManager->remove($playerItem);
        }
        // nbUsages == -1 signifie usage illimité

        $this->entityManager->flush();

        return $this->redirectToRoute('app_game_inventory_items_list');
    }
}
