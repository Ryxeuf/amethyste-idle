<?php

declare(strict_types=1);

namespace App\GameEngine\Player;

use App\Entity\Game\CodexEntry;

/**
 * Le recap de la semaine close — l'etat « lundi » du bloc « La semaine ».
 *
 * GAME_DASHBOARD § 4. Ce n'est ni un ecran, ni une modale, ni un sixieme
 * panneau : c'est le meme bloc qui, une fois par semaine et une seule, s'ouvre
 * en disant ce qui s'est depose avant de dire ce qui s'ouvre.
 *
 * **Quatre depots au maximum**, et le plafond est applique a la construction
 * pour la meme raison que celui de `HubWeek` : au-dela, le recap cesse d'etre
 * une respiration et redevient un rapport. Les quatre sont ceux du cadrage —
 * assiduite, commission, defis de guilde, chantier du foyer.
 *
 * **Une ligne de chronique**, au plus, et c'est la premiere surface joueur de
 * la chronique des foyers : le fait de monde le plus recent qui concerne la
 * zone d'attache. Jusqu'ici il fallait ouvrir le Codex pour savoir que sa ville
 * avait grandi.
 */
final readonly class HubWeekRecap
{
    /**
     * Les quatre depots du cadrage, et pas un cinquieme.
     *
     * Un systeme hebdomadaire de plus devra decider **lequel des quatre il
     * remplace** dans le recap, exactement comme pour les cinq lignes du bloc
     * courant.
     */
    public const MAX_LINES = 4;

    /** @var list<HubWeekRecapLine> */
    public array $lines;

    /**
     * @param list<HubWeekRecapLine> $lines     ce qui s'est depose, dans l'ordre canonique du § 4
     * @param string                 $weekKey   la semaine **close**, jamais la courante
     * @param ?CodexEntry            $chronicle fait de monde de la zone d'attache, s'il y en a un
     */
    public function __construct(
        array $lines,
        public string $weekKey = '',
        public ?CodexEntry $chronicle = null,
    ) {
        // Tronque plutot que de lever : un ecran de jeu ne tombe pas parce
        // qu'un depot de plus s'est declare (meme regle que `HubWeek`).
        $this->lines = \array_slice($lines, 0, self::MAX_LINES);
    }

    /**
     * Le recap a-t-il quelque chose a raconter ?
     *
     * Une semaine ou rien ne s'est depose et ou le monde n'a rien dit ne merite
     * pas qu'on ouvre le bloc pour l'annoncer : le silence se lit tres bien
     * comme un silence, et un encart « rien cette semaine » serait precisement
     * le reproche que le § 4 refuse.
     */
    public function isEmpty(): bool
    {
        return [] === $this->lines && null === $this->chronicle;
    }
}
