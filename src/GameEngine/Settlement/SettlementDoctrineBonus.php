<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Zone;
use App\Enum\SettlementDoctrine;
use App\Repository\SettlementRepository;

/**
 * Ce que la doctrine d'un foyer change, la ou ca se joue (FOY-13).
 *
 * Trois consommateurs — la recolte, la Paleur, le depot de sediment — et un
 * seul endroit qui sait lire la doctrine d'une zone. Sans ce point unique,
 * chacun serait alle chercher le foyer a sa facon, et le jour ou l'axe gagne un
 * troisieme effet il aurait fallu le brancher trois fois.
 *
 * **Memoise par zone** : le tick quotidien de la Paleur passe sur tous les
 * filons de la carte, souvent plusieurs par zone. Une requete par filon aurait
 * fait payer au monde ce qui se lit une fois par lieu.
 */
class SettlementDoctrineBonus
{
    /** @var array<string, SettlementDoctrine|null> */
    private array $cache = [];

    public function __construct(
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementDefinitionLoader $loader,
    ) {
    }

    /**
     * La doctrine du foyer de cette zone, ou `null`.
     *
     * `null` couvre trois situations qui n'ont pas a etre distinguees ici :
     * pas de foyer, pas encore de doctrine, ou une zone batie sur la Voute.
     */
    public function doctrineOf(Zone $zone): ?SettlementDoctrine
    {
        $slug = $zone->getSlug();
        if (!\array_key_exists($slug, $this->cache)) {
            $this->cache[$slug] = $this->settlementRepository->findOneByZone($zone)?->getDoctrine();
        }

        return $this->cache[$slug];
    }

    /**
     * Facteur applique au rendement d'une recolte dans la zone.
     */
    public function gatherMultiplier(Zone $zone): float
    {
        return SettlementDoctrine::Foundry === $this->doctrineOf($zone)
            ? 1.0 + $this->loader->load()['doctrine']['foundry']['gather_bonus']
            : 1.0;
    }

    /**
     * Facteur applique a la **montee** de la Paleur d'un filon de la zone.
     *
     * Jamais a la recuperation : un atelier oriente ce qu'on fait au filon, il
     * ne decide pas de la vitesse a laquelle le monde se repare tout seul.
     */
    public function palenessMultiplier(Zone $zone): float
    {
        $doctrine = $this->doctrineOf($zone);
        if (null === $doctrine) {
            return 1.0;
        }

        $definition = $this->loader->load()['doctrine'];

        return match ($doctrine) {
            SettlementDoctrine::Foundry => $definition['foundry']['paleness_multiplier'],
            SettlementDoctrine::Readers => $definition['readers']['paleness_multiplier'],
        };
    }

    /**
     * Facteur applique au sediment `lore` depose dans la zone.
     */
    public function loreMultiplier(Zone $zone): float
    {
        return SettlementDoctrine::Readers === $this->doctrineOf($zone)
            ? $this->loader->load()['doctrine']['readers']['lore_multiplier']
            : 1.0;
    }

    /**
     * Vide la memoire. Utile a un tick long, ou une doctrine adoptee en cours
     * de route ne doit pas rester invisible jusqu'au redemarrage.
     */
    public function forget(): void
    {
        $this->cache = [];
    }
}
