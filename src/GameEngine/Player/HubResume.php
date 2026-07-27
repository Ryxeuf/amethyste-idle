<?php

declare(strict_types=1);

namespace App\GameEngine\Player;

use App\Entity\App\Zone;

/**
 * La reprise : l'unique action primaire du tableau de bord.
 *
 * Le systeme de design n'autorise qu'une action primaire par ecran. Un hub n'y
 * echappe pas : sa premiere responsabilite est de repondre a « je me connecte,
 * je fais quoi ? » par **une** phrase, pas par une grille de raccourcis qui
 * double le menu.
 *
 * Les etats de temps reel (voyage, expedition) ne sont pas des boutons : c'est
 * le registre 2 de GAME_ZONE_ACTIONS.md, qui s'affiche comme un etat avec son
 * compte a rebours. `actionable` porte cette distinction.
 */
final readonly class HubResume
{
    /** Le personnage est mort : se relever prime sur tout le reste. */
    public const STATE_DEAD = 'dead';

    /** Un combat est en cours : aucune autre action n'est possible. */
    public const STATE_FIGHT = 'fight';

    /** Voyage en cours (registre 2) : un etat, pas un bouton. */
    public const STATE_TRAVEL = 'travel';

    /** Expedition en cours (registre 2) : un etat, pas un bouton. */
    public const STATE_EXPEDITION = 'expedition';

    /** Expedition terminee : le butin attend. */
    public const STATE_EXPEDITION_DONE = 'expedition_done';

    /** Rien ne bloque : on repart dans la zone courante. */
    public const STATE_READY = 'ready';

    /** Aucune zone assignee : le joueur n'a nulle part ou aller. */
    public const STATE_LOST = 'lost';

    public function __construct(
        public string $state,
        public string $route,
        public ?Zone $zone = null,
        public ?int $remainingSeconds = null,
        public bool $actionable = true,
    ) {
    }
}
