<?php

namespace App\GameEngine\Retention;

use App\Entity\App\Player;
use App\Entity\App\WeeklyChallenge;
use App\Enum\WeeklyCommissionStatus;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\SeasonManager;
use App\GameEngine\Guild\WeeklyChallengeReader;
use App\GameEngine\Player\HubWeekRecap;
use App\GameEngine\Player\HubWeekRecapLine;
use App\GameEngine\Settlement\SettlementChronicleService;
use App\Repository\PlayerWeeklyAttendanceRepository;
use App\Repository\PlayerWeeklyCommissionRepository;
use App\Repository\SettlementWeeklyWorkContributionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le lundi est un **etat**, pas un evenement (RET-09, GAME_DASHBOARD § 4).
 *
 * Rien ne se planifie ici : la rotation se constate a la lecture, en comparant
 * la semaine de derniere visite — stockee sur le joueur — a la semaine
 * courante. C'est la forme la plus forte du contrat transverse RET-07, deja
 * tenue par l'assiduite : pas « un seul point de rotation », mais **pas de
 * rotation du tout**.
 *
 * **Ou marquer la semaine comme vue.** C'est le seul point delicat du jalon, et
 * il n'a que deux mauvaises reponses : marquer avant que le recap soit
 * construit, et il disparait sans avoir ete lu ; marquer sur un geste du joueur
 * (un bouton « j'ai lu »), et le recap redevient une modale a congedier — ce
 * que le § 4 refuse en toutes lettres. La reponse tenue ici : **lire, c'est
 * consommer**. Le meme appel construit le recap et deplace la marque, dans la
 * meme requete ; la visite suivante rend le bloc compact.
 *
 * **Une semaine jamais vue ne se raconte pas.** Une marque nulle — personnage
 * neuf, ou personnage anterieur a la colonne — inscrit la semaine courante et
 * s'arrete la. Le recap dirait sinon d'une semaine qu'elle n'a rien depose,
 * alors qu'il ne sait rien d'elle.
 */
class WeeklyRecapService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerWeeklyAttendanceRepository $attendanceRepository,
        private readonly WeeklyAttendanceService $attendanceService,
        private readonly PlayerWeeklyCommissionRepository $commissionRepository,
        private readonly SettlementWeeklyWorkContributionRepository $workContributionRepository,
        private readonly GuildManager $guildManager,
        private readonly SeasonManager $seasonManager,
        private readonly WeeklyChallengeReader $challengeReader,
        private readonly SettlementChronicleService $chronicleService,
    ) {
    }

    /**
     * Le recap de la semaine close, une fois — et la marque avance.
     *
     * Rend `null` quand il n'y a rien a ouvrir : semaine deja vue, personnage
     * qui decouvre le hub, ou semaine ou rien ne s'est depose et ou le monde
     * n'a rien dit.
     */
    public function consume(Player $player, ?\DateTimeImmutable $now = null): ?HubWeekRecap
    {
        $now ??= new \DateTimeImmutable();
        $current = WeekKey::of($now);
        $seen = $player->getHubWeekKey();

        if ($seen === $current) {
            return null;
        }

        $player->setHubWeekKey($current);
        $this->entityManager->flush();

        if (null === $seen || '' === $seen) {
            return null;
        }

        $recap = $this->build($player, $seen, $now);

        return $recap->isEmpty() ? null : $recap;
    }

    /**
     * Ce qui s'est depose pendant la semaine close.
     *
     * L'ordre est celui du § 4 et ne se negocie pas : assiduite, commission,
     * defis de guilde, chantier du foyer. Il va de ce que le joueur a fait seul
     * a ce qu'il a fait avec les autres, ce qui est aussi l'ordre dans lequel
     * il s'en souvient.
     */
    private function build(Player $player, string $closedWeek, \DateTimeImmutable $now): HubWeekRecap
    {
        $lines = [];

        $attendance = $this->attendanceLine($player, $closedWeek);
        if (null !== $attendance) {
            $lines[] = $attendance;
        }

        $commission = $this->commissionLine($player, $closedWeek);
        if (null !== $commission) {
            $lines[] = $commission;
        }

        $challenges = $this->challengeLine($player, $closedWeek, $now);
        if (null !== $challenges) {
            $lines[] = $challenges;
        }

        $units = $this->workContributionRepository->sumUnitsForWeek($player, $closedWeek);
        if ($units > 0) {
            $lines[] = new HubWeekRecapLine(
                'settlement_work',
                ['%count%' => $units, '%plural%' => $units > 1 ? 's' : ''],
                HubWeekRecapLine::TONE_GAIN,
            );
        }

        $home = $player->getHomeZone();

        return new HubWeekRecap(
            $lines,
            $closedWeek,
            null !== $home ? $this->chronicleService->latestFor($home) : null,
        );
    }

    /**
     * Le palier d'assiduite atteint, **et ce qu'il a paye**.
     *
     * L'energie du palier est calculee depuis RET-04 et n'etait affichee nulle
     * part avant le bloc « La semaine » ; le recap la redit ici, parce qu'une
     * recompense qu'on ne voit pas passer n'a pas ete recue.
     *
     * Aucune ligne quand aucun palier n'a ete franchi : dire « aucun palier »
     * serait un reproche, et le recap n'en fait pas.
     */
    private function attendanceLine(Player $player, string $closedWeek): ?HubWeekRecapLine
    {
        $attendance = $this->attendanceRepository->findOneForWeek($player, $closedWeek);
        if (null === $attendance) {
            return null;
        }

        $granted = $attendance->getGrantedTierDays();
        if ($granted <= 0) {
            return null;
        }

        $tier = null;
        foreach ($this->attendanceService->tiers() as $candidate) {
            if ($candidate->days === $granted) {
                $tier = $candidate;
            }
        }

        if (null === $tier) {
            return null;
        }

        return new HubWeekRecapLine(
            'attendance',
            [
                '%days%' => $tier->days,
                '%gils%' => $tier->gils,
                '%energy%' => $tier->energy,
            ],
            HubWeekRecapLine::TONE_GAIN,
        );
    }

    /**
     * La commission livree, ou repartie sans vous.
     *
     * Le second cas est un **constat** : la commission n'a pas ete manquee, elle
     * est repartie, et une autre s'ouvre. C'est la nuance que le § 4 demande, et
     * le ton neutre du type la porte — il n'existe pas de ton du reproche.
     */
    private function commissionLine(Player $player, string $closedWeek): ?HubWeekRecapLine
    {
        $commission = $this->commissionRepository->findOneForWeek($player, $closedWeek);
        if (null === $commission) {
            return null;
        }

        if (WeeklyCommissionStatus::Delivered === $commission->getStatus()) {
            return new HubWeekRecapLine('commission_delivered', [], HubWeekRecapLine::TONE_GAIN);
        }

        return new HubWeekRecapLine('commission_gone', [], HubWeekRecapLine::TONE_NEUTRAL);
    }

    /**
     * Les defis de guilde reussis **pendant la semaine close**.
     *
     * Les defis sont dates, pas indexes par semaine : on retient ceux dont
     * l'echeance tombe dans la semaine close. Les bornes se demandent a
     * `WeekKey`, seul endroit du projet qui sait lire une clef de semaine.
     */
    private function challengeLine(Player $player, string $closedWeek, \DateTimeImmutable $now): ?HubWeekRecapLine
    {
        $guild = $this->guildManager->getPlayerMembership($player)?->getGuild();
        $season = $this->seasonManager->getCurrentSeason();

        if (null === $guild || null === $season) {
            return null;
        }

        $monday = WeekKey::mondayOfKey($closedWeek);
        if (null === $monday) {
            return null;
        }

        $nextMonday = $monday->modify('+7 days');
        $entries = $this->challengeReader->entriesFor($guild, $season, $now);

        $done = 0;
        foreach ([...$entries['active'], ...$entries['completed']] as $entry) {
            if (true !== ($entry['completed'] ?? false)) {
                continue;
            }

            $challenge = $entry['challenge'] ?? null;
            if (!$challenge instanceof WeeklyChallenge) {
                continue;
            }

            $endsAt = $challenge->getEndsAt();
            if ($endsAt >= $monday && $endsAt < $nextMonday) {
                ++$done;
            }
        }

        if ($done <= 0) {
            return null;
        }

        return new HubWeekRecapLine(
            'guild_challenges',
            ['%count%' => $done, '%plural%' => $done > 1 ? 's' : ''],
            HubWeekRecapLine::TONE_GAIN,
        );
    }
}
