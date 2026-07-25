<?php

namespace App\GameEngine\Season;

use App\Entity\App\InfluenceSeason;
use App\GameEngine\Codex\WorldFactService;

/**
 * Resolution de saison & credits narratifs (NAR-11).
 *
 * A la cloture d'une saison, l'issue est **predefinie** (une seule branche) : la
 * guilde qui controle une region en recolte les credits narratifs — son nom
 * s'inscrit au **journal de monde** public (fait canon horodate, NAR-07). Aucune
 * branche par vainqueur : seul le nom credite varie.
 *
 * Idempotent : chaque fait de monde est enregistre par un slug deterministe
 * (`<arc de saison>_<region>_resolution`), donc rejouer la resolution met a jour
 * sans dupliquer.
 */
class SeasonResolutionService
{
    public function __construct(
        private readonly WorldFactService $worldFactService,
    ) {
    }

    /**
     * Credite les guildes controlantes au journal de monde a la cloture de la saison.
     *
     * @param array<string, string|null> $regionControl slug de region => nom de la guilde controlante (null = region libre)
     *
     * @return int nombre de faits de monde enregistres
     */
    public function resolve(InfluenceSeason $season, array $regionControl): int
    {
        $arc = $season->getStoryArc();
        $seasonName = $season->getName();
        $recorded = 0;

        foreach ($regionControl as $regionSlug => $guildName) {
            if (!\is_string($guildName) || trim($guildName) === '') {
                continue;
            }

            $regionLabel = ucfirst(str_replace('-', ' ', $regionSlug));

            $this->worldFactService->recordWorldFact(
                sprintf('%s_%s_resolution', $arc, $regionSlug),
                sprintf('%s — %s', $seasonName, $regionLabel),
                sprintf(
                    'Au terme de la saison, la guilde « %s » tient %s. Son nom s\'inscrit dans la chronique du monde.',
                    $guildName,
                    $regionLabel,
                ),
                $guildName,
            );
            ++$recorded;
        }

        // Aucune guilde controlante nulle part : la saison se clot sans vainqueur credite.
        if ($recorded === 0) {
            $this->worldFactService->recordWorldFact(
                sprintf('%s_resolution', $arc),
                sprintf('%s — Résolution', $seasonName),
                'La saison s\'achève sans qu\'aucune guilde ne s\'impose durablement. Le monde retient son souffle.',
                null,
            );
            ++$recorded;
        }

        return $recorded;
    }
}
