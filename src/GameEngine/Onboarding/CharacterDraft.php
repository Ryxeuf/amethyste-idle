<?php

namespace App\GameEngine\Onboarding;

use App\Enum\CreationStep;

/**
 * Le personnage en cours de creation, entre deux pas du tunnel (ONB-05).
 *
 * **« Aucune saisie perdue »** est la contrainte qui donne sa forme a cette
 * classe. Un tunnel en quatre pas sans memoire est pire que le formulaire
 * unique qu'il remplace : revenir en arriere pour corriger un nom couterait
 * alors le peuple et le visage deja choisis.
 *
 * Le brouillon vit en **session** et non en base : rien n'est encore un
 * personnage, et un abandon ne doit rien laisser derriere lui. Il se serialise
 * en tableau pour la meme raison — une session ne doit pas contenir d'objet
 * dont la classe pourrait changer sous elle.
 */
final class CharacterDraft
{
    public const SESSION_KEY = 'onboarding.character_draft';

    public function __construct(
        public ?string $name = null,
        public ?string $raceSlug = null,
        public ?string $body = null,
        public ?string $hair = null,
        public ?string $hairColor = null,
    ) {
    }

    /**
     * @param array<string, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        return new self(
            self::text($stored['name'] ?? null),
            self::text($stored['raceSlug'] ?? null),
            self::text($stored['body'] ?? null),
            self::text($stored['hair'] ?? null),
            self::text($stored['hairColor'] ?? null),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'raceSlug' => $this->raceSlug,
            'body' => $this->body,
            'hair' => $this->hair,
            'hairColor' => $this->hairColor,
        ];
    }

    /**
     * Le premier pas encore a franchir.
     *
     * Sert a **reprendre** un tunnel interrompu : un joueur qui revient sur
     * `/game/character/create` apres avoir ferme l'onglet reprend la ou il en
     * etait, plutot que de recommencer. Le visage est le seul pas facultatif —
     * un personnage sans apparence choisie reste un personnage, et l'exiger
     * bloquerait la creation sur une decision qui n'engage rien.
     */
    public function firstIncompleteStep(): ?CreationStep
    {
        if (null === $this->name) {
            return CreationStep::Name;
        }

        if (null === $this->raceSlug) {
            return CreationStep::People;
        }

        return null;
    }

    public function isReady(): bool
    {
        return null === $this->firstIncompleteStep();
    }

    private static function text(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return '' === $trimmed ? null : $trimmed;
    }
}
