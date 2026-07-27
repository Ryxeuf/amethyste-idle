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
 * cause. C'est une decision d'exploitation, pas une regle de jeu — elle se prend
 * a la main.
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
     */
    public function inspect(?\DateTimeImmutable $now = null): RentBacklogReport
    {
        $now ??= new \DateTimeImmutable();

        $houses = $this->houses->findWithRentDue($now);
        $shops = $this->shops->findWithRentDue($now);

        return new RentBacklogReport(
            houseCount: \count($houses),
            shopCount: \count($shops),
            worstHousePeriods: $this->worstPeriods($houses, $now, PlayerHouse::RENT_PERIOD_DAYS),
            worstShopPeriods: $this->worstPeriods($shops, $now, ShopRentService::RENT_PERIOD_DAYS),
        );
    }

    /**
     * Repousse toute echeance echue a `maintenant + une periode`.
     *
     * Volontairement idempotent : relancer la commande sur une base deja
     * assainie ne trouve plus rien d'echu et n'ecrit rien.
     */
    public function reset(?\DateTimeImmutable $now = null): RentBacklogReport
    {
        $now ??= new \DateTimeImmutable();
        $report = $this->inspect($now);

        foreach ($this->houses->findWithRentDue($now) as $house) {
            $house->setRentDueAt($now->modify(sprintf('+%d days', PlayerHouse::RENT_PERIOD_DAYS)));
        }

        foreach ($this->shops->findWithRentDue($now) as $shop) {
            $shop->setRentDueAt($now->modify(sprintf('+%d days', ShopRentService::RENT_PERIOD_DAYS)));
        }

        $this->entityManager->flush();

        return $report;
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
