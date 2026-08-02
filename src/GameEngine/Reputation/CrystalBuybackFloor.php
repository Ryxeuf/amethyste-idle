<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Entity\Game\Item;

/**
 * Le plancher d'achat du cristal (FAC-04a).
 *
 * GAME_WORLD § 12.2 : « la Fonderie rachete toujours le cristal, a prix bas
 * mais garanti. » C'est le miroir du plancher T1 de vente (ECO-02) : un
 * debutant n'est jamais bloque par un marche sans acheteur — protection
 * cold-start cote vente. Le plancher ne vaut qu'au comptoir de la Fonderie,
 * et que pour l'amethystite : partout ailleurs, le rachat PNJ reste au taux
 * commun.
 *
 * **Hostile chez la Fonderie : le plancher se ferme** — c'est la consequence
 * `buyback_floor_closed` declaree par FAC-03, qui prend vie ici. La boucle
 * cœur tient : le rachat au taux commun et l'hotel des ventes restent, seule
 * la *garantie* disparait. Et un debutant n'est jamais Hostile.
 */
class CrystalBuybackFloor
{
    /**
     * Le comptoir qui porte le plancher. Un seul aujourd'hui — le siege, au
     * carreau des Mines. En ouvrir un autre, c'est ajouter un slug ici.
     */
    public const COUNTER_SLUGS = ['mines-comptoir-de-la-fonderie'];

    public const CRYSTAL_SLUG = 'ore-amethyst-crystal';

    /**
     * Le prix garanti, en gils. « Bas mais garanti » : au-dessus du rachat
     * commun (30 % de 15 = 4 gils), en dessous du prix d'achat (15 gils) et
     * de ce qu'un lot correct rend au marche — le plancher protege du marche
     * vide, il ne le remplace pas.
     */
    public const FLOOR_PRICE = 9;

    public function __construct(
        private readonly HostileConsequenceResolver $hostileConsequences,
    ) {
    }

    /**
     * Le prix plancher si ce comptoir, cet objet et ce joueur y ont droit —
     * `null` sinon (le rachat commun s'applique alors seul).
     */
    public function floorFor(?Pnj $pnj, Item $item, Player $player): ?int
    {
        if (null === $pnj || !\in_array($pnj->getSlug(), self::COUNTER_SLUGS, true)) {
            return null;
        }
        if (self::CRYSTAL_SLUG !== $item->getSlug()) {
            return null;
        }
        if ($this->hostileConsequences->isCrystalBuybackClosed($player)) {
            // FAC-03, buyback_floor_closed : elle ne rachete plus votre
            // cristal. Le taux commun reste — la boucle cœur ne ferme jamais.
            return null;
        }

        return self::FLOOR_PRICE;
    }
}
