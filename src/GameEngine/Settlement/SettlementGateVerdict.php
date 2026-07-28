<?php

namespace App\GameEngine\Settlement;

use App\Enum\SettlementRank;

/**
 * Pourquoi un service est ouvert ou ferme (FOY-05).
 *
 * Le verdict porte une **clef de traduction et ses parametres**, jamais une
 * phrase. Un service ferme doit dire ce qui manque, dans la langue du joueur, et
 * le meme refus doit se lire pareil partout — un bouton grise sans explication
 * est la facon la plus sure de faire croire a un bug.
 */
final readonly class SettlementGateVerdict
{
    private function __construct(
        public bool $allowed,
        public string $service,
        public ?SettlementRank $required = null,
        public ?SettlementRank $current = null,
    ) {
    }

    public static function open(string $service): self
    {
        return new self(true, $service);
    }

    public static function closed(string $service, SettlementRank $required, SettlementRank $current): self
    {
        return new self(false, $service, $required, $current);
    }

    public function messageKey(): ?string
    {
        return $this->allowed ? null : 'game.settlement.gate.closed';
    }

    /**
     * @return array<string, string>
     */
    public function messageParams(): array
    {
        // `closed()` pose toujours les deux rangs ensemble : les tester tous
        // les deux ici n'est pas de la prudence superflue, c'est ce qui evite un
        // acces nullsafe dont l'analyse statique montre qu'il ne sert a rien.
        if ($this->allowed || $this->required === null || $this->current === null) {
            return [];
        }

        return [
            '%service%' => $this->service,
            '%required%' => $this->required->value,
            '%current%' => $this->current->value,
        ];
    }

    /**
     * Nombre de rangs manquants — de quoi doser un message : « il s'en faut d'un
     * palier » ne se dit pas comme « il s'en faut de trois ».
     */
    public function missingRanks(): int
    {
        if ($this->allowed || $this->required === null || $this->current === null) {
            return 0;
        }

        return max(0, $this->required->level() - $this->current->level());
    }
}
