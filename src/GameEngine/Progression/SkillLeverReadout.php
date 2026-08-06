<?php

namespace App\GameEngine\Progression;

/**
 * Ce qu'un nœud passif dit a l'ecran (ARC-12b).
 *
 * GAME_ARCHETYPES § 4.3 et § 8 bis. Un passif conditionnel qui n'annonce ni ce
 * qu'il rapporte ni ce qu'il faut porter est **indiscernable d'un passif
 * mort** : le joueur voit un chiffre qui ne bouge pas et conclut que le nœud
 * ne sert a rien. C'est le defaut que ce jalon ferme — et c'est aussi ce qui
 * fait que *l'equipement est le build* plutot qu'un total (GAME_DOMAINS § 3,
 * une promesse qui n'avait jamais eu de quoi se tenir).
 *
 * Trois choses, et pas une de plus :
 *
 *  - **l'effet**, celui qu'on obtient quand la condition est remplie — jamais
 *    l'effet moyen que le budget compte, qui est une affaire d'auteur ;
 *  - **la condition**, dite en clair (« a la dague », « en plaque ») plutot
 *    qu'en `weapon:dagger` ;
 *  - **si elle est remplie**, quand on sait le dire.
 */
final readonly class SkillLeverReadout
{
    public function __construct(
        /** Le levier, tel que le joueur le lit (« Puissance »). */
        public string $leverLabel,
        /** Ce que le nœud rapporte, condition remplie. */
        public float $effect,
        /** L'unite de cet effet (« % », « pts »). */
        public string $unit,
        /** Ce qu'il faut porter, en clair — `null` si le nœud est toujours vrai. */
        public ?string $requirement = null,
    ) {
    }

    public function isConditional(): bool
    {
        return $this->requirement !== null;
    }

    /**
     * L'effet, arrondi comme un ecran l'affiche.
     *
     * Un dixieme : les taux de change descendent sous l'unite (§ 4), et
     * afficher un entier ferait lire « +0 % » a un nœud qui rend 0,4 point.
     */
    public function formattedEffect(): string
    {
        return sprintf('+%s %s', rtrim(rtrim(number_format($this->effect, 1, ',', ''), '0'), ','), $this->unit);
    }
}
