<?php

namespace App\GameEngine\Settlement;

use App\Enum\SettlementRank;
use App\Enum\SettlementType;

/**
 * Ce que le foyer ajoute a un etabli, et d'ou ca vient (FOY-07).
 *
 * Le total seul ne suffit pas : un joueur qui lit « +6 » sans savoir ce qui le
 * compose ne peut pas arbitrer *ou* crafter, et c'est precisement l'arbitrage
 * que le jalon existe pour creer (GAME_WORLD § 5.2 — « on voyage pour
 * crafter »). Les trois parts sont donc rendues separement.
 *
 * `capped` dit que le plafond a mordu. Le taire donnerait un bonus qui cesse
 * d'augmenter sans explication, et le joueur en conclurait — a raison — que
 * faire monter sa ville ne sert plus a rien.
 */
readonly class WorkshopBonus
{
    public function __construct(
        public int $rank,
        public int $type,
        public int $line,
        public int $total,
        public bool $capped,
        public ?SettlementRank $settlementRank = null,
        public ?SettlementType $settlementType = null,
        public ?string $productionLine = null,
    ) {
    }

    /**
     * Aucun foyer, ou rien que ce foyer apporte a ce metier.
     *
     * Le cas par defaut, et de loin le plus frequent : quatre metiers, un seul
     * type de foyer, une seule ligne de production. Il doit donc etre le moins
     * couteux a construire et le plus simple a lire.
     */
    public static function none(): self
    {
        return new self(0, 0, 0, 0, false);
    }

    public function isZero(): bool
    {
        return $this->total === 0;
    }
}
