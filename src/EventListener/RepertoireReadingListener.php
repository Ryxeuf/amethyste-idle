<?php

namespace App\EventListener;

use App\Event\Game\MateriaReadEvent;
use App\GameEngine\Repertoire\RepertoireLedger;
use App\GameEngine\Retention\WeekKey;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Le premier abonne du crochet de lecture (REP-01).
 *
 * `MateriaReadEvent` a ete declare par FAC-04b **sans abonne**, avec cette
 * phrase : *« le jour ou REP arrive, il s'abonne ici sans qu'on revienne
 * toucher la lecture »*. C'est ce jour-la — a une correction pres, que le
 * crochet ne pouvait pas prevoir : il lui manquait la provenance.
 *
 * La semaine vient de `WeekKey`, l'horloge unique du projet (RET-07) : la
 * recopier ici ferait du Repertoire une sixieme mecanique hebdomadaire avec sa
 * propre idee du lundi.
 */
#[AsEventListener(event: MateriaReadEvent::NAME, method: 'onMateriaRead')]
class RepertoireReadingListener
{
    public function __construct(
        private readonly RepertoireLedger $ledger,
    ) {
    }

    public function onMateriaRead(MateriaReadEvent $event): void
    {
        $now = new \DateTimeImmutable();

        $this->ledger->record(
            $event->getPlayer(),
            $event->getMateria()->getElement(),
            $event->getProvenanceZoneId(),
            WeekKey::of($now),
            $now->format('Y-m-d'),
        );
    }
}
