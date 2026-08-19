<?php

namespace App\GameEngine\Tutorial;

/**
 * Ou aller, chez qui, et pour y faire quoi.
 *
 * Le bandeau de tutoriel disait ce qu'il fallait faire et **jamais ou** : il
 * envoyait « chez la maitresse d'armes » sans nommer la zone, et son lien
 * pointait sur `app_game_zone` — c'est-a-dire, quatre fois sur cinq, sur la
 * page depuis laquelle on le lisait. Un lien qui recharge la page courante ne
 * se lit pas comme un lien inutile : il se lit comme un lien casse.
 *
 * Cet objet porte les trois choses qui manquaient — **le lieu**, **la
 * personne**, et **une destination qui n'est pas la page courante** —, et il
 * les tient de la donnee : la zone du PNJ vise par la quete, pas une chaine
 * ecrite a cote.
 */
final readonly class TutorialDestination
{
    /**
     * @param array<string, int|string> $routeParams
     * @param array<string, string>     $actionParams parametres du libelle du lien
     */
    public function __construct(
        public string $route,
        public array $routeParams,
        public string $actionKey,
        public array $actionParams = [],
        public ?string $place = null,
        public ?string $person = null,
    ) {
    }

    /**
     * Le lieu et la personne, rendus ensemble quand les deux sont connus.
     *
     * Rend `null` quand il n'y a rien a dire — un geste d'inventaire n'a pas de
     * lieu, et afficher un « Ou : » vide serait pire que de se taire.
     */
    public function where(): ?string
    {
        return match (true) {
            null !== $this->place && null !== $this->person => $this->place . ' — ' . $this->person,
            null !== $this->person => $this->person,
            default => $this->place,
        };
    }
}
