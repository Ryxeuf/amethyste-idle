<?php

declare(strict_types=1);

namespace App\GameEngine\Economy;

/**
 * Etat de l'arriere de loyers (tache 134, jalon F.0).
 */
final readonly class RentBacklogReport
{
    public function __construct(
        public int $houseCount,
        public int $shopCount,
        public int $worstHousePeriods,
        public int $worstShopPeriods,
    ) {
    }

    public function isEmpty(): bool
    {
        return 0 === $this->houseCount && 0 === $this->shopCount;
    }

    /**
     * Nombre de jours pendant lesquels un prelevement quotidien aurait eu lieu
     * si le planificateur avait ete branche sans rien faire.
     *
     * Chaque execution ne rattrape qu'une periode : le retard le plus lourd
     * donne donc directement le nombre de jours de prelevements en rafale.
     */
    public function dailyChargesAvoided(): int
    {
        return max($this->worstHousePeriods, $this->worstShopPeriods);
    }
}
