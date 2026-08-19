<?php

namespace App\GameEngine\Housing;

use App\Entity\App\Guild;
use App\Entity\App\PlayerHouse;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Region\PlayerRegionResolver;
use App\Repository\SettlementRepository;
use Psr\Log\LoggerInterface;

/**
 * Ou va un loyer (FOY-19).
 *
 * GAME_WORLD § 12.6 c : *« dans une zone a foyer, le loyer part au **tresor de
 * la guilde controlante** de la region (le meme canal que la taxe HV) ; sans
 * guilde controlante, il reste un sink. Habiter chez quelqu'un est un acte
 * politique doux. »*
 *
 * ## La regle est plus generale que son illustration
 *
 * Le plan dit « Quartier des Jardins → sink toujours ». Ce n'est pas une
 * exception au nom du Quartier : c'est qu'il **n'a pas de foyer** — bati sur la
 * Voute, rien ne s'y depose (`settlements.yaml` le documente), et une zone sans
 * foyer n'a aucun corps politique pour percevoir. La regle s'ecrit donc sur
 * l'absence de foyer, et le Quartier en est **le cas**, pas la cause.
 *
 * Une regle illustree par son unique instance ne vieillit pas : le jour ou une
 * seconde zone residentielle naît hors foyer, elle sera traitee sans qu'on
 * revienne ici.
 *
 * ## Le sink reste un sink
 *
 * Sans foyer, ou avec un foyer que personne ne gouverne, les gils **sortent du
 * jeu**. C'est la meme regle qu'a l'hotel des ventes, a l'echoppe et a l'Autel
 * d'eveil, et pour la meme raison : les rendre au joueur en ferait une remise
 * deguisee, c'est-a-dire l'inverse d'un gold sink. On le journalise, sans quoi
 * une refonte pourrait les rendre en croyant corriger une fuite.
 */
class HouseRentRouting
{
    public function __construct(
        private readonly SettlementRepository $settlements,
        private readonly PlayerRegionResolver $regionResolver,
        private readonly TownControlManager $townControl,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * La guilde qui percoit ce loyer, ou `null` quand il part au sink.
     */
    public function beneficiaryOf(PlayerHouse $house): ?Guild
    {
        $zone = $house->getZone();

        // Pas de foyer, pas de percepteur. Le lotissement du Fanal est ce
        // cas-la : le plancher du logement reste un pur gold sink, ce qui est
        // aussi ce qui le rend **inconditionnel** — personne ne peut le fermer,
        // parce que personne n'en tire rien.
        if ($this->settlements->findOneByZone($zone) === null) {
            return null;
        }

        $region = $this->regionResolver->resolveForZone($zone);

        return $region !== null ? $this->townControl->getControllingGuild($region) : null;
    }

    /**
     * Verse le loyer a qui de droit — ou constate qu'il sort du jeu.
     */
    public function route(PlayerHouse $house, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $beneficiary = $this->beneficiaryOf($house);

        if (!$beneficiary instanceof Guild) {
            $this->logger->info('House rent burned (no settlement, or no ruling guild)', [
                'zone' => $house->getZone()->getSlug(),
                'amount' => $amount,
            ]);

            return;
        }

        $beneficiary->addRentToTreasury($amount);

        $this->logger->info('House rent transferred to guild treasury', [
            'zone' => $house->getZone()->getSlug(),
            'guild' => $beneficiary->getName(),
            'amount' => $amount,
        ]);
    }
}
