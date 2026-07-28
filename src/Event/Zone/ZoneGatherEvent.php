<?php

namespace App\Event\Zone;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Emis a chaque recolte reussie sur un filon de zone (ZON-38).
 *
 * **Pourquoi un evenement de plus.** Avant le pivot PBBG, la recolte passait par
 * `HarvestManager` et emettait {@see \App\Event\Map\SpotHarvestEvent}, porteur
 * d'un `ObjectLayer` — un objet de la carte navigable supprimee avec ZON-21. La
 * boucle de recolte vit desormais dans `GatherService`, sur les filons declares
 * de la zone, et n'emettait plus rien du tout : l'action la plus jouee du jeu
 * etait devenue inobservable.
 *
 * Consequence mesurable, et silencieuse comme toujours : l'influence de guilde
 * gagnee en recoltant valait zero depuis le pivot. Le joueur recoltait, le
 * journal enregistrait, et la guilde ne recevait rien — sans message d'erreur,
 * sans log, sans difference visible avec une recolte qui aurait compte.
 *
 * L'evenement porte la **zone**, pas une couche de carte : c'est la reference de
 * position du modele PBBG (regle 7).
 */
class ZoneGatherEvent extends Event
{
    final public const NAME = 'event.zone.gather';

    public function __construct(
        private readonly Player $player,
        private readonly Zone $zone,
        private readonly string $veinSlug,
        private readonly string $itemSlug,
        private readonly int $quantity,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getZone(): Zone
    {
        return $this->zone;
    }

    public function getVeinSlug(): string
    {
        return $this->veinSlug;
    }

    public function getItemSlug(): string
    {
        return $this->itemSlug;
    }

    /**
     * Unites reellement remises au joueur — jamais zero : une recolte n'echoue
     * pas, elle rend peu (GAME_ZONE_ACTIONS, loi 5).
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
