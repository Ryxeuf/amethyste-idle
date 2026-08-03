<?php

namespace App\GameEngine\Housing;

use App\Entity\App\Zone;
use App\Enum\SettlementRank;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\World\WorldScaleService;
use App\Repository\PlayerHouseRepository;
use App\Repository\SettlementRepository;

/**
 * Les parcelles residentielles par rang (FOY-18).
 *
 * GAME_WORLD § 12.6 b : la liste explicite de zones residentielles devient
 * une regle — **tout foyer au rang de Hameau ou plus est residentiel**, a
 * capacite par rang (declaree dans `settlements.yaml`, calibree a W = 1 et
 * mise a l'echelle par le facteur de monde). Le Quartier des Jardins reste le
 * plancher inconditionnel — il n'a pas de foyer du tout (bati sur la Voute),
 * et c'est HousingManager qui le tient, hors de toute regle de rang.
 *
 * **Jamais d'expulsion** (decision A) : la capacite ne gate que l'ouverture
 * de NOUVELLES parcelles. Une regression de rang — ou une contraction du
 * monde (W descend) — peut laisser plus de demeures que de parcelles : elles
 * restent, toutes. Aucun chemin de ce service ne touche une demeure
 * existante, la borne tient par construction.
 */
class ResidentialParcels
{
    public function __construct(
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementDefinitionLoader $loader,
        private readonly WorldScaleService $worldScale,
        private readonly PlayerHouseRepository $houseRepository,
    ) {
    }

    /**
     * Cette zone est-elle residentielle par son rang ? Le plancher des
     * Jardins ne passe pas ici : il n'a pas de foyer, il a une garantie.
     */
    public function isRankResidential(Zone $zone): bool
    {
        $settlement = $this->settlementRepository->findOneByZone($zone);
        if (null === $settlement) {
            return false;
        }

        return null !== $this->scaledCapacity($settlement->getRank());
    }

    /**
     * La capacite de parcelles au rang donne, mise a l'echelle du monde —
     * `null` si le rang ne loge pas (Ruine, Campement, ou bloc absent).
     */
    public function scaledCapacity(SettlementRank $rank): ?int
    {
        $capacities = $this->loader->load()['housing']['parcels_per_rank'] ?? [];
        $nominal = $capacities[$rank->value] ?? null;
        if (!\is_int($nominal)) {
            return null;
        }

        // Meme primitive que les seuils de rang (BALANCE § 24.3) : W met les
        // seuils a l'echelle, jamais les taux — et jamais sous 1.
        $scale = $this->worldScale->current();
        if ($scale <= 0.0 || 1.0 === $scale) {
            return $nominal;
        }

        return max(1, (int) round($nominal * $scale));
    }

    /**
     * Ce que l'ecran de zone montre du logement, ou `null` si le rang ne
     * loge pas. `free` peut etre a zero avec plus de demeures que de
     * parcelles (regression, contraction de W) : rien n'expulse.
     *
     * @return array{capacity: int, taken: int, free: int}|null
     */
    public function panel(Zone $zone): ?array
    {
        $settlement = $this->settlementRepository->findOneByZone($zone);
        if (null === $settlement) {
            return null;
        }

        $capacity = $this->scaledCapacity($settlement->getRank());
        if (null === $capacity) {
            return null;
        }

        $taken = $this->houseRepository->countInZone($zone);

        return [
            'capacity' => $capacity,
            'taken' => $taken,
            'free' => max(0, $capacity - $taken),
        ];
    }

    /**
     * Une nouvelle parcelle peut-elle s'ouvrir ici ? La SEULE question que la
     * capacite ait le droit de poser — jamais une passe de reconciliation.
     */
    public function canOpenParcel(Zone $zone): bool
    {
        $panel = $this->panel($zone);

        return null !== $panel && $panel['free'] > 0;
    }
}
