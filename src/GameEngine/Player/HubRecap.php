<?php

declare(strict_types=1);

namespace App\GameEngine\Player;

use App\Entity\App\PlayerJournalEntry;

/**
 * Le recap : ce que le personnage a fait recemment.
 *
 * Un hub de PBBG doit repondre a « ou j'en etais ? » aussi bien qu'a « je fais
 * quoi ? ». Le journal existe deja et porte la reponse ; il n'etait simplement
 * jamais montre a l'endroit ou la question se pose.
 *
 * L'agregat par type ne remplace pas les lignes : le compte donne l'allure de la
 * session precedente, les lignes en donnent le detail.
 */
final readonly class HubRecap
{
    /**
     * @param array<string, int>       $counts  type de journal => nombre d'entrees dans la fenetre
     * @param list<PlayerJournalEntry> $entries dernieres entrees, les plus recentes d'abord
     */
    public function __construct(
        public array $counts = [],
        public array $entries = [],
    ) {
    }

    public function isEmpty(): bool
    {
        return [] === $this->entries;
    }
}
