<?php

namespace App\GameEngine\Season;

use App\Enum\TideVoice;

/**
 * Ce que la partition a decide pour un creneau (NAR-15).
 *
 * Trois issues, et la troisieme n'est pas un echec : un creneau **reserve** par
 * la colonne vertebrale reste vide jusqu'a ce que son jalon l'ecrive. Rendre un
 * simple `?string` aurait confondu « rien a poser parce que c'est reserve » avec
 * « rien a poser parce que rien n'a ete choisi », et la difference est tout le
 * jalon : c'est elle qui empeche une rotation de prendre le creneau de « La
 * Premiere Pierre ».
 */
final readonly class TideChoice
{
    private function __construct(
        public TideVoice $voice,
        public ?string $tide,
        public ?string $theme,
        public ?string $milestone,
    ) {
    }

    public static function consequence(string $tide): self
    {
        return new self(TideVoice::Consequence, $tide, null, null);
    }

    public static function rotation(string $tide): self
    {
        return new self(TideVoice::Rotation, $tide, null, null);
    }

    public static function reserved(string $theme, string $milestone): self
    {
        return new self(TideVoice::Canon, null, $theme, $milestone);
    }

    public static function nothing(): self
    {
        return new self(TideVoice::None, null, null, null);
    }

    /**
     * Y a-t-il un arc a poser ? Un creneau reserve repond **non**.
     */
    public function isComposable(): bool
    {
        return $this->tide !== null;
    }
}
