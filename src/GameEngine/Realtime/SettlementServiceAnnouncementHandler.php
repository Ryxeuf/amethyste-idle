<?php

namespace App\GameEngine\Realtime;

use App\Event\Zone\SettlementRankChangedEvent;
use App\GameEngine\Settlement\SettlementServiceDirectory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Le palier se voit (FOY-06).
 *
 * Un foyer qui passe au Bourg ouvre le marche de la region — et ce genre de
 * nouvelle n'a de valeur que si elle arrive **au moment ou elle se produit**, a
 * ceux qui sont sur place. Decouvrir trois jours plus tard qu'on a franchi le
 * palier qu'on batissait retire au chantier ce qui en faisait un chantier.
 *
 * L'annonce part **dans les deux sens**, comme l'evenement qui la declenche :
 * une montee se fete, une descente se dit. Taire la descente donnerait un monde
 * ou les villes ne font que grandir, c'est-a-dire l'inverse de ce que la
 * decroissance existe pour raconter.
 *
 * Meme topic que les evenements de zone (`zone/<id>/event`) : l'ecran de zone
 * est deja l'endroit ou l'on regarde ce qui arrive ici.
 */
class SettlementServiceAnnouncementHandler implements EventSubscriberInterface
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
        private readonly SettlementServiceDirectory $serviceDirectory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SettlementRankChangedEvent::NAME => 'onSettlementRankChanged',
        ];
    }

    public function onSettlementRankChanged(SettlementRankChangedEvent $event): void
    {
        $zone = $event->getSettlement()->getZone();
        $zoneId = $zone->getId();

        $crossed = $this->serviceDirectory->crossedBetween($event->getFrom(), $event->getTo());
        $promotion = $event->isPromotion();

        $topic = 'zone/' . $zoneId . '/event';

        $this->hub->publish(new Update(
            $topic,
            json_encode([
                'topic' => $topic,
                'type' => 'settlement_rank_changed',
                'settlement' => [
                    'zoneId' => $zoneId,
                    'zoneName' => $zone->getName(),
                    'from' => $event->getFrom()->value,
                    'to' => $event->getTo()->value,
                    'promotion' => $promotion,
                    // Les memes services, ranges selon le sens : la vue n'a pas
                    // a rejouer la comparaison de rangs pour savoir quoi dire.
                    'opened' => $promotion ? $crossed : [],
                    'closed' => $promotion ? [] : $crossed,
                ],
            ], JSON_THROW_ON_ERROR)
        ));

        $this->logger->info('Mercure published settlement rank change on zone {zoneId}: {from} -> {to}', [
            'zoneId' => $zoneId,
            'from' => $event->getFrom()->value,
            'to' => $event->getTo()->value,
            'services' => $crossed,
        ]);
    }
}
