<?php

namespace App\GameEngine\Renown;

use App\Entity\App\Player;
use App\Enum\PlayerRenownTier;

/**
 * Fournit le taux de reduction accorde par les marchands PNJ au joueur
 * en fonction de son palier de renommee globale (voir PlayerRenownTier).
 *
 * Cette reduction se cumule avec d'autres reductions eventuelles
 * (guilde de controle regional, evenements, etc.) et n'est accordee
 * qu'a l'achat (pas a la vente).
 */
class PlayerRenownDiscountProvider
{
    /**
     * Taux plafond total combine (renommee + autres reductions),
     * pour eviter de distribuer des objets gratuitement par empilement.
     */
    public const MAX_COMBINED_DISCOUNT = 0.50;

    /**
     * Retourne le taux de reduction marchand du joueur, dans [0.0, 1.0].
     */
    public function getShopDiscount(Player $player): float
    {
        return PlayerRenownTier::fromScore($player->getRenownScore())->shopDiscount();
    }

    /**
     * Combine la reduction de renommee a une autre reduction deja calculee
     * (additive), plafonnee a MAX_COMBINED_DISCOUNT.
     */
    public function combineDiscount(float $baseDiscount, Player $player): float
    {
        $combined = $baseDiscount + $this->getShopDiscount($player);

        if ($combined < 0.0) {
            return 0.0;
        }

        if ($combined > self::MAX_COMBINED_DISCOUNT) {
            return self::MAX_COMBINED_DISCOUNT;
        }

        return $combined;
    }
}
