<?php

namespace App\GameEngine\Reputation;

/**
 * Porter ces couleurs est refuse (FAC-01).
 *
 * Le refus porte un motif parce qu'il a deux causes tres differentes — « pas
 * encore assez proche » se leve en jouant, « pas pendant un combat » se leve en
 * finissant le tour. Les confondre enverrait le joueur chercher la mauvaise
 * chose.
 */
class PatronageException extends \RuntimeException
{
    public const REASON_TIER = 'tier';
    public const REASON_IN_COMBAT = 'in_combat';

    public function __construct(
        public readonly string $reason,
        string $message,
    ) {
        parent::__construct($message);
    }
}
