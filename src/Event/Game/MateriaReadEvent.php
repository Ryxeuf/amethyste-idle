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
 *
 * **REP-01 y a ajoute la provenance, et il fallait bien qu'il le fasse.** Un
 * crochet declare en avance porte ce que son auteur a devine, et celui-ci
 * portait exactement les deux choses qui **survivent** a la lecture : le
 * joueur et la fiche de materia. Ce qui ne survit pas, c'est la piece elle-meme
 * — `MateriaConversionService` la supprime une ligne avant le dispatch —, et
 * c'est elle qui savait d'ou elle venait. La provenance se capture donc **avant
 * la suppression** et voyage dans l'evenement.
 */
class MateriaReadEvent extends Event
{
    final public const NAME = 'event.game.materia.read';

    public function __construct(
        private readonly Player $player,
        private readonly Item $materia,
        private readonly ?int $provenanceZoneId = null,
    ) {
    }

    /**
     * La zone d'ou le monde avait sorti cette materia, si elle est connue.
     *
     * `null` = inconnu, et cela reste inconnu : une materia achetee ou
     * fabriquee n'a pas de provenance, et lui en inventer une remplirait un axe
     * du Repertoire avec la copie d'un autre.
     */
    public function getProvenanceZoneId(): ?int
    {
        return $this->provenanceZoneId;
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
