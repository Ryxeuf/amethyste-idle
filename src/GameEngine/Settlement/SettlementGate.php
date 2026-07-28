<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Zone;
use App\Enum\SettlementRank;
use App\Repository\SettlementRepository;

/**
 * Ce que le rang d'un foyer ouvre — et ce qu'il n'a pas le droit de fermer
 * (FOY-05).
 *
 * **Decision A : rien n'est retro-gate.** Le gate ne s'applique qu'aux services
 * *nouveaux*, ceux que le pilier apporte. Tout ce qui existait avant reste
 * ouvert, quel que soit le rang : une ville qui maigrit ne perd pas ses
 * habitants, ses boutiques ni ses etablis. Un joueur ne doit jamais decouvrir
 * qu'un service dont il se servait hier lui est ferme aujourd'hui parce que
 * d'autres ont cesse de frequenter sa zone.
 *
 * Le defaut est donc **ouvert** : un service absent de la table passe. Gater se
 * declare ; s'ouvrir ne se declare pas. C'est l'inverse d'un systeme de
 * permissions, et c'est voulu — un oubli de configuration doit laisser jouer,
 * pas bloquer.
 *
 * Le refus n'est jamais une exception : `verdict()` rend une decision **lisible**
 * — ce qui manque, et de combien — pour que l'ecran puisse dire *pourquoi*
 * plutot que d'afficher un bouton grise sans explication.
 */
class SettlementGate
{
    /** @var array<string, SettlementRank>|null */
    private ?array $services = null;

    public function __construct(
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementDefinitionLoader $loader,
    ) {
    }

    public function allows(Zone $zone, string $service): bool
    {
        return $this->verdict($zone, $service)->allowed;
    }

    /**
     * Decision motivee, pour que l'interface puisse expliquer un refus.
     */
    public function verdict(Zone $zone, string $service): SettlementGateVerdict
    {
        $required = $this->services()[$service] ?? null;

        if ($required === null) {
            // Service non gate : ouvert partout, y compris dans une zone sans
            // foyer. C'est le cas de tout ce qui existait avant le pilier.
            return SettlementGateVerdict::open($service);
        }

        $settlement = $this->settlementRepository->findOneByZone($zone);
        if ($settlement === null) {
            // Une zone sans foyer n'a pas de rang, donc pas de service nouveau.
            // Ce n'est pas une privation : Lumiere et les Jardins sont batis sur
            // la Voute, rien ne s'y accumule et rien n'y grandit.
            return SettlementGateVerdict::closed($service, $required, SettlementRank::Ruin);
        }

        $current = $settlement->getRank();

        return $current->isAtLeast($required)
            ? SettlementGateVerdict::open($service)
            : SettlementGateVerdict::closed($service, $required, $current);
    }

    /**
     * Services gates et leur rang minimum, tels que declares.
     *
     * @return array<string, SettlementRank>
     */
    public function services(): array
    {
        if ($this->services === null) {
            $this->services = $this->loader->load()['services'];
        }

        return $this->services;
    }
}
