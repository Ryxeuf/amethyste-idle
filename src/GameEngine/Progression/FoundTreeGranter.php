<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Helper\InventoryHelper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * La rencontre qu'un accomplissement declenche (DOM-10).
 *
 * *Le joueur qui a mene l'arbre du mineur a son dernier palier croise, au fond
 * d'une galerie, un vieux Nain a moitie change en minerai. Il ne vend rien : il
 * donne son carnet.*
 *
 * ## Ce que ce service repare
 *
 * **Terminer un arbre ne donnait rien.** Le dernier palier etait un cul-de-sac —
 * et depuis DOM-09, `miner-master` etait meme litteralement vide : il ne portait
 * qu'une statistique de combat qui fuyait, et qui est partie. Il devient une
 * **condition de rencontre** : ce n'est pas ce qu'un nœud donne qui compte, c'est
 * ce qu'il prouve.
 *
 * ## Les deux lois que la forme de ce service porte
 *
 * **Cumulatif, jamais manque** : la condition est un accomplissement **durable**
 * (un nœud appris ne se desapprend qu'au respec), donc la rencontre reste
 * disponible indefiniment. Il n'y a **aucun etat global** ici — pas de compteur
 * de decouvreurs, pas de premier arrive, pas de fenetre. Deux joueurs qui
 * finissent le meme arbre a six mois d'ecart recoivent le meme carnet.
 *
 * **Le parchemin est lie** : il est marque `bind_on_pickup` en donnees, et
 * `InventoryHelper::addItem()` le lie a son porteur des qu'il entre dans le sac.
 * Ce qui circule entre joueurs est **l'information**, jamais l'objet — sans
 * cela, le premier decouvreur met le secret a l'hotel des ventes, et il meurt en
 * deux jours.
 *
 * ## Ce qu'il ne fait pas
 *
 * Il n'**ouvre** pas l'arbre : il remet le parchemin, et c'est le joueur qui le
 * lit. La difference n'est pas cosmetique — *le savoir n'est jamais impose*
 * (GAME_DOMAINS § 1), et un arbre ouvert d'office serait un arbre qu'on n'a pas
 * choisi.
 */
class FoundTreeGranter
{
    public function __construct(
        private readonly FoundTreeCatalog $catalog,
        private readonly EntityManagerInterface $entityManager,
        private readonly InventoryHelper $inventoryHelper,
    ) {
    }

    /**
     * Ce nœud vient d'etre appris : declenche-t-il une rencontre ?
     *
     * Rend le slug du parchemin remis, ou `null` — ce qui est le cas de la
     * quasi-totalite des acquisitions, et c'est voulu : *une rencontre est rare
     * parce que l'accomplissement l'est, jamais parce qu'un jet l'a decide*.
     */
    public function onSkillAcquired(Player $player, Skill $skill): ?string
    {
        $tree = $this->catalog->treeEarnedBy((string) $skill->getSlug());
        if ($tree === null) {
            return null;
        }

        $slug = $this->catalog->trees()[$tree]['parchment']['slug'];

        // Une rencontre ne se rejoue pas pour le meme joueur : il a deja le
        // carnet, ou il l'a deja lu. Le verifier ici plutot qu'en marquant le
        // joueur evite un second etat a tenir — *ce qu'on peut lire, on ne le
        // stocke pas*.
        if ($this->alreadyHolds($player, $slug)) {
            return null;
        }

        $parchment = $this->entityManager->getRepository(Item::class)->findOneBy(['slug' => $slug]);
        if ($parchment === null) {
            return null;
        }

        $this->inventoryHelper->addItemId((int) $parchment->getId());

        return $slug;
    }

    /**
     * Le joueur a-t-il deja ce parchemin en sac ?
     */
    private function alreadyHolds(Player $player, string $slug): bool
    {
        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem->getGenericItem()->getSlug() === $slug) {
                    return true;
                }
            }
        }

        return false;
    }
}
