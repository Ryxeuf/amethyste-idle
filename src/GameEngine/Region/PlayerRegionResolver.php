<?php

namespace App\GameEngine\Region;

use App\Entity\App\Player;
use App\Entity\App\Region;
use App\Entity\App\Zone;

/**
 * Region ou se tient un joueur (ECO-03).
 *
 * La position de reference est la **zone** (regle projet #7) : la region se lit
 * donc `zone → sourceMap → region`. La carte du joueur (`Player::map`) n'est
 * qu'un repli pour les personnages pas encore rattaches a une zone — depuis le
 * pivot, `ZoneTravelService` ne la met plus a jour, elle **ne suit pas** le
 * voyage et ne peut donc pas servir de source de verite.
 *
 * Ce resolveur existe parce que la lecture etait dupliquee et divergente :
 * `RegionDiscoveryTracker` lisait bien la zone, `AuctionManager` lisait
 * uniquement la carte — et prelevait donc la taxe de la region ou le joueur
 * se trouvait avant le pivot, pas celle ou il vend.
 */
class PlayerRegionResolver
{
    public function resolve(?Player $player): ?Region
    {
        if (null === $player) {
            return null;
        }

        return $this->resolveForZone($player->getCurrentZone())
            ?? $player->getMap()?->getRegion();
    }

    /**
     * Region d'une zone du graphe.
     *
     * Expose separement pour ce qui est ancre a un **lieu** et non a un joueur
     * — une echoppe, par exemple (ECO-11), dont la taxe se lit a son adresse et
     * non a la position de l'acheteur. Une zone sans carte d'origine n'a pas de
     * region : c'est le cas depuis ZON-26b, et il est traite comme le marche
     * « sans region » plutot que refuse.
     */
    public function resolveForZone(?Zone $zone): ?Region
    {
        return $zone?->getSourceMap()?->getRegion();
    }

    /**
     * Deux joueurs (ou un joueur et une annonce) partagent-ils le meme marche ?
     *
     * L'absence de region des deux cotes compte comme un marche commun : sans
     * cela, les personnages hors graphe se retrouveraient sans aucun marche.
     *
     * La comparaison porte sur le **slug**, unique en base : l'identifiant est
     * nul pour une region non encore persistee, ce qui ferait passer deux
     * regions distinctes pour la meme.
     */
    public function isSameMarket(?Region $left, ?Region $right): bool
    {
        if (null === $left || null === $right) {
            return null === $left && null === $right;
        }

        return $left->getSlug() === $right->getSlug();
    }
}
