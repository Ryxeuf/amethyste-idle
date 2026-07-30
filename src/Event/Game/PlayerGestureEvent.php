<?php

namespace App\Event\Game;

use App\Enum\QuestGesture;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Un joueur vient d'accomplir un geste (ONB-12a).
 *
 * L'evenement existe pour que le moteur de quete n'ait pas a entrer dans
 * l'equipement, le sertissage, le combat et l'expedition : quatre services qui
 * n'ont aucune raison de connaitre les quetes. Chacun annonce ce qu'il vient de
 * faire ; le suivi ecoute.
 *
 * `targets` porte **toutes** les lectures possibles de la cible — pour une
 * epee : son slug et sa famille d'arme. Une quete qui vise l'une ou l'autre est
 * satisfaite sans que l'emetteur ait a deviner laquelle sera demandee.
 */
class PlayerGestureEvent extends Event
{
    final public const NAME = 'event.game.player.gesture';

    /**
     * @param list<string> $targets
     */
    public function __construct(
        private readonly QuestGesture $gesture,
        private readonly array $targets = [],
    ) {
    }

    public function getGesture(): QuestGesture
    {
        return $this->gesture;
    }

    /**
     * @return list<string>
     */
    public function getTargets(): array
    {
        return $this->targets;
    }
}
