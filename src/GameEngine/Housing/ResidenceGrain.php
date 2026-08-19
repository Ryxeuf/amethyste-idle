<?php

namespace App\GameEngine\Housing;

use App\GameEngine\Settlement\SettlementDepositService;
use App\Repository\PlayerHouseRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les cheminees (FOY-20).
 *
 * GAME_WORLD § 12.6 d : *« chaque demeure habitee depose un petit grain de
 * residence quotidien au foyer de sa zone — la population residente soutient la
 * ville, faiblement mais structurellement »*.
 *
 * **Un plancher de sediment, pas un revenu.** Le grain est petit et il tombe du
 * seul fait d'habiter : c'est ce qui permet a un foyer residentiel de ne pas
 * s'effondrer les jours ou personne ne chasse, sans que s'installer devienne une
 * facon de farmer une ville.
 *
 * **Habitee** veut dire *loyer a jour*. Une demeure en arriere a cesse de rendre
 * service (HOU-04), et elle cesse aussi de soutenir sa ville : sans cette
 * condition, on entretiendrait un foyer avec des logis vides — *la population
 * residente soutient la ville*, pas les murs.
 *
 * La cle de jour vit sur la demeure, et c'est ce qui rend la commande
 * **idempotente** : le calendrier ne rejoue rien, mais une relance a la main ne
 * doit pas deposer deux fois.
 *
 * **Une cheminee sans ville ne fume pas** (FOY-21). Le plancher du logement est
 * le seul endroit du jeu ou une demeure existe **sans foyer** — c'est meme ce
 * qui le rend inconditionnel (personne n'en tire rien, donc personne ne peut le
 * fermer). Le depot y rend zero, et la cle de jour n'est **pas** posee : un
 * compteur qui compterait des grains qui ne sont pas tombes ne mesurerait plus
 * rien, et c'est ce compteur qu'un operateur lit pour savoir que le systeme
 * tourne.
 */
class ResidenceGrain
{
    public const ACTION = 'residence';

    public function __construct(
        private readonly PlayerHouseRepository $houses,
        private readonly SettlementDepositService $deposits,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Fait fumer les cheminees du jour.
     *
     * @return array{burned: int, skipped: int}
     */
    public function burnHearths(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $dayKey = $now->format('Y-m-d');

        $burned = 0;
        $skipped = 0;

        foreach ($this->houses->findAll() as $house) {
            if ($house->hasBurnedItsHearthOn($dayKey) || !$house->isRentUpToDate($now)) {
                ++$skipped;
                continue;
            }

            // Le depot passe par le service commun : le grain de residence est
            // une ligne de la table `sediment`, pas un chemin a part. C'est ce
            // qui garantit qu'il obeit aux memes regles que les autres gestes —
            // multiplicateurs de doctrine compris.
            $grains = $this->deposits->deposit($house->getOwner(), self::ACTION, $house->getZone(), $now);

            // Zone sans foyer : il n'y a pas de ville a soutenir. On ne pose pas
            // la cle — rien n'a eu lieu, et le compteur doit le dire.
            if ($grains <= 0) {
                ++$skipped;
                continue;
            }

            $house->recordResidenceGrain($dayKey);
            ++$burned;
        }

        if ($burned > 0) {
            $this->entityManager->flush();
        }

        return ['burned' => $burned, 'skipped' => $skipped];
    }
}
