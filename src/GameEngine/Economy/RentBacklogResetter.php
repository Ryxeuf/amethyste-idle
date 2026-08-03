<?php

declare(strict_types=1);

namespace App\GameEngine\Economy;

use App\Entity\App\PlayerHouse;
use App\Entity\App\PlayerShop;
use App\GameEngine\Shop\ShopRentService;
use App\Repository\PlayerHouseRepository;
use App\Repository\PlayerShopRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Remet a zero l'arriere de loyers avant la premiere execution du planificateur
 * (tache 134, jalon F.0).
 *
 * ## Pourquoi ceci doit exister
 *
 * L'audit du jalon F a etabli qu'**aucun processus ne consomme le calendrier des
 * taches** : `app:house:rent` et `app:shop:rent` n'ont jamais tourne. Les
 * echeances de loyer, elles, ont continue d'etre posees a la creation de chaque
 * demeure et de chaque echoppe. Elles sont donc toutes dans le passe.
 *
 * Or les deux services rattrapent l'arriere **une periode par execution** :
 * `PlayerHouse::extendRent()` et `ShopRentService::extend()` avancent l'echeance
 * de sept jours **a partir de l'echeance precedente**, pas a partir de
 * maintenant. Brancher le planificateur tel quel prelevererait donc une semaine
 * de loyer par jour, a chaque joueur, jusqu'a rattraper le retard — un mois de
 * prelevements quotidiens pour six mois d'arriere, et la mise en sommeil des
 * demeures ou la fermeture des echoppes de ceux qui ne suivent pas.
 *
 * Personne n'a contracte cette dette : elle est l'effet d'une tache qui n'a
 * jamais tourne. On la remet a zero plutot que de la reclamer.
 *
 * ## Pourquoi une commande ponctuelle, et non un correctif dans le service
 *
 * Rattraper une periode par execution est le comportement **correct** en regime
 * normal : une panne de deux jours ne doit pas offrir deux jours de loyer. Ce
 * qu'on veut ici, c'est effacer un arriere precis, une fois, en connaissance de
 * cause. C'est une decision d'exploitation, pas une regle de jeu.
 *
 * ## Le seuil, et pourquoi il existe
 *
 * L'entrypoint du worker (`frankenphp/scheduler-entrypoint.sh`) appelle cette
 * remise a zero **a chaque demarrage**, pour que l'activation du planificateur
 * ne depende plus d'une etape manuelle qu'on peut oublier. Sans seuil, ce serait
 * une fuite : un redemarrage a 00 h 10 effacerait une echeance tombee a 00 h 00
 * que la tache de 00 h 15 s'appretait a prelever.
 *
 * D'ou `$minPeriodsLate`. En regime normal le planificateur preleve tous les
 * jours : aucune echeance ne depasse un jour de retard, donc un seuil de deux
 * periodes (14 jours) ne trouve **rien**. Au-dela, le retard ne peut venir que
 * d'une interruption longue — exactement la dette que personne n'a contractee.
 *
 * Le defaut reste `0` : lance a la main, la commande efface tout l'arriere,
 * comme avant.
 */
final class RentBacklogResetter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerHouseRepository $houses,
        private readonly PlayerShopRepository $shops,
    ) {
    }

    /**
     * Mesure l'arriere sans rien ecrire.
     *
     * @param int $minPeriodsLate ne compte que les echeances en retard d'au
     *                            moins ce nombre de periodes (0 = tout l'arriere)
     */
    public function inspect(?\DateTimeImmutable $now = null, int $minPeriodsLate = 0): RentBacklogReport
    {
        $now ??= new \DateTimeImmutable();

        $houses = $this->overdueHouses($now, $minPeriodsLate);
        $shops = $this->overdueShops($now, $minPeriodsLate);

        return new RentBacklogReport(
            houseCount: \count($houses),
            shopCount: \count($shops),
            worstHousePeriods: $this->worstPeriods($houses, $now, PlayerHouse::RENT_PERIOD_DAYS),
            worstShopPeriods: $this->worstPeriods($shops, $now, ShopRentService::RENT_PERIOD_DAYS),
        );
    }

    /**
     * Repousse toute echeance retenue a `maintenant + une periode`.
     *
     * Volontairement idempotent : relancer la commande sur une base deja
     * assainie ne trouve plus rien d'echu et n'ecrit rien.
     *
     * @param int $minPeriodsLate ne repousse que les echeances en retard d'au
     *                            moins ce nombre de periodes (0 = tout l'arriere)
     */
    public function reset(?\DateTimeImmutable $now = null, int $minPeriodsLate = 0): RentBacklogReport
    {
        $now ??= new \DateTimeImmutable();
        $report = $this->inspect($now, $minPeriodsLate);

        foreach ($this->overdueHouses($now, $minPeriodsLate) as $house) {
            $house->setRentDueAt($now->modify(sprintf('+%d days', PlayerHouse::RENT_PERIOD_DAYS)));
        }

        foreach ($this->overdueShops($now, $minPeriodsLate) as $shop) {
            $shop->setRentDueAt($now->modify(sprintf('+%d days', ShopRentService::RENT_PERIOD_DAYS)));
        }

        $this->entityManager->flush();

        return $report;
    }

    /**
     * @return list<PlayerHouse>
     */
    private function overdueHouses(\DateTimeImmutable $now, int $minPeriodsLate): array
    {
        return $this->atLeastLate(
            $this->houses->findWithRentDue($now),
            $now,
            PlayerHouse::RENT_PERIOD_DAYS,
            $minPeriodsLate,
        );
    }

    /**
     * @return list<PlayerShop>
     */
    private function overdueShops(\DateTimeImmutable $now, int $minPeriodsLate): array
    {
        return $this->atLeastLate(
            $this->shops->findWithRentDue($now),
            $now,
            ShopRentService::RENT_PERIOD_DAYS,
            $minPeriodsLate,
        );
    }

    /**
     * Ne garde que ce qui est en retard d'au moins `$minPeriodsLate` periodes.
     *
     * Le filtre est en PHP et non en DQL : les deux depots ont chacun leur
     * requete d'echeance (celle des echoppes exclut deja le statut « en
     * defaut »), et un seuil n'a pas a se dupliquer dans deux QueryBuilder.
     * La volumetrie — les proprietaires echus d'un serveur — ne le justifie pas.
     *
     * @template T of PlayerHouse|PlayerShop
     *
     * @param array<int, T> $entities
     *
     * @return list<T>
     */
    private function atLeastLate(array $entities, \DateTimeImmutable $now, int $periodDays, int $minPeriodsLate): array
    {
        if ($minPeriodsLate <= 0) {
            return array_values($entities);
        }

        $kept = [];
        foreach ($entities as $entity) {
            $due = $entity->getRentDueAt();
            if (null === $due) {
                continue;
            }
            if (intdiv((int) $due->diff($now)->days, $periodDays) >= $minPeriodsLate) {
                $kept[] = $entity;
            }
        }

        return $kept;
    }

    /**
     * Nombre de periodes de retard du plus mauvais eleve.
     *
     * C'est le chiffre qui dit combien de prelevements quotidiens auraient eu
     * lieu si on avait branche le planificateur sans rien faire.
     *
     * @param array<int, PlayerHouse|PlayerShop> $entities
     */
    private function worstPeriods(array $entities, \DateTimeImmutable $now, int $periodDays): int
    {
        $worst = 0;
        foreach ($entities as $entity) {
            $due = $entity->getRentDueAt();
            if (null === $due) {
                continue;
            }
            $lateDays = (int) $due->diff($now)->days;
            $worst = max($worst, intdiv($lateDays, $periodDays));
        }

        return $worst;
    }
}
