<?php

declare(strict_types=1);

namespace App\GameEngine\Economy;

use App\Entity\App\GilsSupplySnapshot;

/**
 * Comparaison de deux releves de masse monetaire (ECO-15).
 *
 * La grandeur qui compte est la masse **par personnage**. Le total brut monte
 * mecaniquement quand la population monte, et ce n'est pas de l'inflation.
 */
final readonly class GilsSupplyTrend
{
    public function __construct(
        public GilsSupplySnapshot $earlier,
        public GilsSupplySnapshot $current,
        public int $days,
    ) {
    }

    /**
     * Variation de la masse par tete, en pourcentage.
     *
     * Une base a zero — un serveur neuf ou vide au premier releve — rend le
     * pourcentage indefini plutot qu'infini : diviser par zero produirait une
     * alerte permanente qui n'apprendrait rien.
     */
    public function perCapitaChangePercent(): ?float
    {
        $before = $this->earlier->getPerCapita();
        if ($before <= 0.0) {
            return null;
        }

        return ($this->current->getPerCapita() - $before) / $before * 100.0;
    }

    /**
     * Meme variation, ramenee a une semaine.
     *
     * Les releves ne tombent pas forcement a exactement N jours d'ecart : la
     * tache planifiee peut avoir saute un tour. Comparer un ecart de 3 jours a
     * un seuil hebdomadaire declencherait a tort.
     */
    public function weeklyChangePercent(): ?float
    {
        $change = $this->perCapitaChangePercent();
        if (null === $change) {
            return null;
        }

        $elapsed = max(1, $this->elapsedDays());

        return $change * 7 / $elapsed;
    }

    public function elapsedDays(): int
    {
        return (int) $this->earlier->getCapturedAt()->diff($this->current->getCapturedAt())->days;
    }

    public function isInflationary(float $threshold = GilsSupplyService::WEEKLY_ALERT_PERCENT): bool
    {
        $weekly = $this->weeklyChangePercent();

        return null !== $weekly && $weekly > $threshold;
    }

    /**
     * Une masse qui fond aussi vite qu'elle gonflerait merite la meme attention :
     * elle signale des puits trop gourmands, pas une economie saine.
     */
    public function isDeflationary(float $threshold = GilsSupplyService::WEEKLY_ALERT_PERCENT): bool
    {
        $weekly = $this->weeklyChangePercent();

        return null !== $weekly && $weekly < -$threshold;
    }
}
