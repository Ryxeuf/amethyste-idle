<?php

namespace App\Event\Game;

use App\Entity\App\Player;
use App\Entity\Game\Item;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Une materia lue au Cercle (FAC-04b).
 *
 * GAME_WORLD § 12.3 : chaque lecture est versee au **Repertoire du serveur**
 * — le compteur collectif qui retrouvera un jour les gestes oublies. Le
 * Repertoire n'est pas jalonne (REP-01→06) : cet evenement est son crochet,
 * declare en avance et **sans abonne aujourd'hui** — meme doctrine que la
 * paire de tension de la Fonderie. Le jour ou REP arrive, il s'abonne ici
 * sans qu'on revienne toucher la lecture. Le Programme du Cercle (FAC-09)
 * ecoutera au meme endroit.
 */
class MateriaReadEvent extends Event
{
    final public const NAME = 'event.game.materia.read';

    public function __construct(
        private readonly Player $player,
        private readonly Item $materia,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getMateria(): Item
    {
        return $this->materia;
    }
}
