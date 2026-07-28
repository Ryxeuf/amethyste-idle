<?php

namespace App\GameEngine\Retention;

use App\Entity\App\Player;
use App\Entity\App\PlayerWeeklyAttendance;
use App\Repository\PlayerWeeklyAttendanceRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * On recompense la presence, on ne sanctionne jamais l'absence (RET-04).
 *
 * Le compteur est un nombre de **jours distincts** actifs dans la semaine ISO
 * courante ; les paliers (2 / 4 / 6) se franchissent une fois chacun et se
 * paient sur-le-champ. Une semaine nouvelle est une ligne nouvelle : il n'y a
 * rien a remettre a zero, et **aucune memoire des semaines ratees**. Une serie
 * qui casse transforme un PBBG en corvee — c'est l'inverse du contrat du genre.
 *
 * **Aucune horloge nouvelle.** La bascule du lundi est *derivee* de la semaine
 * ISO, la meme clef que la commission (RET-02) et le defi de guilde (RET-01).
 * Cinq mecaniques hebdomadaires ne doivent pas vouloir dire cinq crons qui
 * derivent (contrat transverse, RET-07).
 *
 * **Ce qui compte comme jour actif** : de l'energie depensee, jamais la simple
 * connexion — meme definition que la population effective (BALANCE § 22.5). On
 * compte la charge, pas les tetes.
 */
class WeeklyAttendanceService
{
    /**
     * @var list<WeeklyAttendanceTier>|null
     */
    private ?array $tiers = null;

    public function __construct(
        private readonly PlayerWeeklyAttendanceRepository $repository,
        private readonly WeeklyAttendanceDefinitionLoader $loader,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Enregistre une journee active, et paie le palier si elle en franchit un.
     *
     * Idempotent a la journee : appele vingt fois le meme mardi, il ne compte
     * qu'un jour et ne paie qu'une fois.
     *
     * @return WeeklyAttendanceTier|null le palier franchi par cet appel, s'il y en a un
     */
    public function record(Player $player, ?\DateTimeImmutable $now = null): ?WeeklyAttendanceTier
    {
        $now ??= new \DateTimeImmutable();
        $day = $now->format('Y-m-d');

        $attendance = $this->attendanceFor($player, $now);

        if ($attendance->getLastActiveDay() === $day) {
            return null;
        }

        $attendance->setLastActiveDay($day);
        $attendance->setActiveDays($attendance->getActiveDays() + 1);

        return $this->grantReachedTier($player, $attendance);
    }

    /**
     * Etat de la semaine en cours, **sans rien creer**.
     *
     * Le tableau de bord lit ; il ne doit pas inscrire une presence par le seul
     * fait qu'on regarde. Un joueur qui se connecte sans rien faire n'a pas
     * ete actif, et cette methode est le seul endroit ou la distinction
     * pourrait se perdre.
     */
    public function currentDays(Player $player, ?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();

        return $this->repository
            ->findOneForWeek($player, WeeklyCommissionGenerator::weekKey($now))
            ?->getActiveDays() ?? 0;
    }

    /**
     * Paliers declares, du plus bas au plus haut.
     *
     * @return list<WeeklyAttendanceTier>
     */
    public function tiers(): array
    {
        return $this->tiers ??= $this->loader->load();
    }

    /**
     * Le prochain palier a portee, ou `null` s'ils sont tous franchis.
     */
    public function nextTier(int $activeDays): ?WeeklyAttendanceTier
    {
        foreach ($this->tiers() as $tier) {
            if ($tier->days > $activeDays) {
                return $tier;
            }
        }

        return null;
    }

    private function attendanceFor(Player $player, \DateTimeImmutable $now): PlayerWeeklyAttendance
    {
        $weekKey = WeeklyCommissionGenerator::weekKey($now);

        $attendance = $this->repository->findOneForWeek($player, $weekKey);
        if ($attendance === null) {
            $attendance = new PlayerWeeklyAttendance($player, $weekKey);
            $this->entityManager->persist($attendance);
        }

        return $attendance;
    }

    /**
     * Le palier le plus haut atteint et pas encore paye.
     *
     * On paie **le plus haut** et non le suivant : un jalon futur qui
     * accorderait plusieurs jours d'un coup ne doit pas laisser un palier
     * derriere lui. Les paliers inferieurs sautes ne sont pas payes — ils
     * l'ont deja ete quand le compteur est passe par la, et si le compteur a
     * saute, c'est que la semaine a ete plus genereuse que prevu, pas que le
     * joueur a droit a un rattrapage.
     */
    private function grantReachedTier(Player $player, PlayerWeeklyAttendance $attendance): ?WeeklyAttendanceTier
    {
        $reached = null;
        foreach ($this->tiers() as $tier) {
            if ($tier->days <= $attendance->getActiveDays() && $tier->days > $attendance->getGrantedTierDays()) {
                $reached = $tier;
            }
        }

        if ($reached === null) {
            return null;
        }

        $attendance->setGrantedTierDays($reached->days);

        if ($reached->gils > 0) {
            $player->addGils($reached->gils);
        }
        if ($reached->energy > 0) {
            $player->setActionEnergy(min(
                $player->getMaxActionEnergy(),
                $player->getActionEnergy() + $reached->energy,
            ));
        }

        return $reached;
    }
}
