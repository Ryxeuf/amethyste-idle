<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Enum\SettlementRank;
use App\GameEngine\World\WorldLoadService;
use App\Repository\SettlementRepository;

/**
 * La Crue (FOY-08) — combien de grandes villes le monde peut porter.
 *
 * **Decision B du socle de monde.** Sans quota, tout le monde monte tout et il
 * n'y a pas d'enjeu de territoire : c'est la seule mecanique qui rend le pilier
 * des foyers *politique*, et elle y parvient **sans un coup echange** — deux
 * guildes se disputent une place, pas un mur.
 *
 * **On mesure la charge, pas les tetes** (BALANCE § 22.5). Le quota s'indexe sur
 * la population effective — l'energie reellement depensee — ce qui l'immunise
 * contre le multi-compte : dix comptes qui ne jouent pas ne font pas monter le
 * plafond.
 *
 * **Le sediment n'est jamais perdu.** Un foyer qui merite un rang que le quota
 * refuse le garde en reserve : il monte des qu'une place se libere. Le rang est
 * une lecture du sediment, jamais un stock a part — c'est ce qui fait qu'une
 * attente ne coute rien.
 *
 * **Personne n'est en attente en silence.** `occupants()` nomme ceux qui tiennent
 * les places : une competition qu'on ne voit pas est vecue comme un bug, et
 * c'est le risque nomme par PLAN_SETTLEMENTS.
 */
class CrueQuotaService
{
    /** @var array<string, int>|null */
    private ?array $quotas = null;

    public function __construct(
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementDefinitionLoader $loader,
        private readonly WorldLoadService $worldLoadService,
    ) {
    }

    /**
     * Nombre de foyers que le monde autorise a **ce rang ou au-dessus**.
     *
     * `null` quand le rang n'est pas contingente : les petits rangs ne le sont
     * pas, et c'est deliberé — la Crue borne les grandes villes, pas le droit
     * d'exister.
     */
    public function quotaFor(SettlementRank $rank): ?int
    {
        $required = $this->quotas()[$rank->value] ?? null;
        if ($required === null) {
            return null;
        }

        return (int) floor($this->worldLoadService->effectivePopulation() / $required);
    }

    /**
     * Foyers occupant deja une place a ce rang ou au-dessus.
     *
     * @return list<Settlement>
     */
    public function occupants(SettlementRank $rank, ?Settlement $excluding = null): array
    {
        $occupants = [];
        foreach ($this->settlementRepository->findAllRanked() as $settlement) {
            if ($excluding !== null && $settlement->getZone()->getSlug() === $excluding->getZone()->getSlug()) {
                continue;
            }
            if ($settlement->getRank()->isAtLeast($rank)) {
                $occupants[] = $settlement;
            }
        }

        return $occupants;
    }

    public function allows(Settlement $settlement, SettlementRank $rank): bool
    {
        $quota = $this->quotaFor($rank);
        if ($quota === null) {
            return true;
        }

        return \count($this->occupants($rank, $settlement)) < $quota;
    }

    /**
     * Le plus haut rang que ce foyer peut effectivement prendre.
     *
     * Une montee peut franchir plusieurs crans d'un coup ; le quota s'applique a
     * **chacun**. On redescend donc jusqu'au premier rang autorise, plutot que de
     * refuser la montee entiere : un foyer qui merite la Cite mais ne peut pas
     * l'avoir doit quand meme devenir Bourg si la place existe. Tout refuser
     * ferait payer a une ville le succes des autres deux fois.
     */
    public function highestAllowed(Settlement $settlement, SettlementRank $natural): SettlementRank
    {
        $candidate = $natural;

        while (!$this->allows($settlement, $candidate)) {
            $previous = $candidate->previous();
            if ($previous === null) {
                return SettlementRank::Ruin;
            }
            $candidate = $previous;
        }

        return $candidate;
    }

    /**
     * @return array<string, int>
     */
    private function quotas(): array
    {
        if ($this->quotas === null) {
            $this->quotas = $this->loader->load()['crue'];
        }

        return $this->quotas;
    }
}
