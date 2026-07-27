<?php

namespace App\Twig\Components;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Helper\PlayerHelper;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent()]
class DashboardPlayerRecap
{
    use DefaultActionTrait;

    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    public function getPlayer(): ?Player
    {
        return $this->playerHelper->getPlayer();
    }

    /**
     * Zone courante : la position de reference depuis le pivot PBBG (regle #7).
     * La carte et les coordonnees affichees ici jusqu'alors ne decrivaient plus
     * rien de jouable — un joueur « en 85-34 sur la carte de test » n'a aucune
     * action possible.
     */
    public function getZone(): ?Zone
    {
        return $this->getPlayer()?->getCurrentZone();
    }

    /**
     * Cle de traduction du type de zone (`game.zone.type.*`), ou null si le
     * joueur n'a pas encore de zone.
     */
    public function getZoneTypeKey(): ?string
    {
        $zone = $this->getZone();

        return null === $zone ? null : 'game.zone.type.' . $zone->getType();
    }

    public function isZoneSafe(): bool
    {
        return $this->getZone()?->isSafe() ?? false;
    }

    public function getLife(): int
    {
        return $this->getPlayer()?->getLife() ?? 0;
    }

    public function getMaxLife(): int
    {
        return $this->getPlayer()?->getMaxLife() ?? 1;
    }

    public function getEnergy(): int
    {
        return $this->getPlayer()?->getEnergy() ?? 0;
    }

    public function getMaxEnergy(): int
    {
        return $this->getPlayer()?->getMaxEnergy() ?? 1;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->getPlayer()?->getCreatedAt() ?? new \DateTime();
    }

    public function getName(): string
    {
        return $this->getPlayer()?->getName() ?? 'Inconnu';
    }

    public function getClassType(): string
    {
        return $this->getPlayer()?->getClassType() ?? 'Aventurier';
    }

    public function getGils(): int
    {
        return $this->getPlayer()?->getGils() ?? 0;
    }

    public function isDead(): bool
    {
        return $this->getPlayer()?->isDead() ?? false;
    }

    public function isInFight(): bool
    {
        return $this->getPlayer()?->getFight() !== null;
    }
}
