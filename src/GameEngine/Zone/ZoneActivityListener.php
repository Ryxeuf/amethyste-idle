<?php

namespace App\GameEngine\Zone;

use App\Event\Zone\ZoneGatherEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Ce qui compte comme du travail dans une zone (ONB-13).
 *
 * **La recolte, et elle seule pour l'instant.** Ce n'est pas un choix par
 * defaut : c'est le geste que l'acte I demande explicitement d'aller faire
 * ailleurs (etape 7), donc le seul qui distingue reellement une zone d'une
 * autre au moment ou le foyer se constate. L'exemple du canon le dit tel quel :
 * *« les mineurs des Mines Profondes ont remarque votre travail »*.
 *
 * **Ce qui n'y est pas, et pourquoi.** La chasse et l'exploration remettent
 * elles aussi quelque chose au joueur, mais `ExploreService` et `HuntService`
 * n'annoncent rien — c'est la dette que `ZoneActionObservabilityTest` tient
 * comptable, en attente de FOY-02. Les brancher ici demanderait de leur faire
 * emettre un evenement, ce qui est le sujet de ce jalon-la, pas de celui-ci.
 * Le jour ou ils l'emettront, ils s'abonneront ici sans rien changer d'autre.
 *
 * Le voyage, lui, ne comptera jamais : passer n'est pas travailler, et c'est ce
 * qui distingue le foyer d'attache de la derniere zone visitee.
 */
class ZoneActivityListener implements EventSubscriberInterface
{
    public function __construct(private readonly ZoneActivityRecorder $recorder)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ZoneGatherEvent::NAME => 'onGather',
        ];
    }

    public function onGather(ZoneGatherEvent $event): void
    {
        $this->recorder->record($event->getPlayer(), $event->getZone());
    }
}
