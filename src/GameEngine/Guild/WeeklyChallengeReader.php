<?php

namespace App\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildChallengeProgress;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\WeeklyChallenge;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Lecture des defis de guilde d'une saison (RET-01, extrait par RET-08).
 *
 * Ces defis se lisaient depuis **le controleur de guilde**, dans une methode
 * privee. Tant qu'un seul ecran les affichait, cela se defendait ; le bloc « La
 * semaine » du hub (GAME_DASHBOARD § 3) en a besoin aussi, et un ecran de jeu
 * ne peut pas dependre du controleur d'un autre ecran.
 *
 * Le service ne fait que **lire**. Il ne decide de rien, ne persiste rien, et
 * ne connait pas le rendu : c'est la meme donnee servie a deux ecrans qui en
 * font deux lectures differentes — l'ecran de guilde detaille les barres, le
 * hub n'en garde qu'un agregat (§ 3 : « 1 reussi / 3, le plus proche de sa
 * cible mis en avant »).
 */
class WeeklyChallengeReader
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Progression de la guilde sur les defis d'une saison, repartie entre ce qui
     * court encore et ce qui est clos (complete ou expire).
     *
     * @return array{active: list<array<string, mixed>>, completed: list<array<string, mixed>>}
     */
    public function entriesFor(Guild $guild, InfluenceSeason $season, ?\DateTimeInterface $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        $challenges = $this->entityManager->getRepository(WeeklyChallenge::class)
            ->createQueryBuilder('wc')
            ->where('wc.season = :season')
            ->setParameter('season', $season)
            ->orderBy('wc.weekNumber', 'DESC')
            ->addOrderBy('wc.startsAt', 'ASC')
            ->getQuery()
            ->getResult();

        if ($challenges === []) {
            return ['active' => [], 'completed' => []];
        }

        $progressMap = [];
        $progressRecords = $this->entityManager->getRepository(GuildChallengeProgress::class)
            ->createQueryBuilder('gcp')
            ->where('gcp.guild = :guild')
            ->andWhere('gcp.challenge IN (:challenges)')
            ->setParameter('guild', $guild)
            ->setParameter('challenges', $challenges)
            ->getQuery()
            ->getResult();

        foreach ($progressRecords as $p) {
            $progressMap[$p->getChallenge()->getId()] = $p;
        }

        $active = [];
        $completed = [];

        foreach ($challenges as $challenge) {
            $progress = $progressMap[$challenge->getId()] ?? null;
            $entry = [
                'challenge' => $challenge,
                'progress' => $progress,
                'current' => $progress ? $progress->getProgress() : 0,
                'target' => $challenge->getTarget(),
                'percentage' => $progress ? $progress->getPercentage() : 0,
                'completed' => $progress && $progress->isCompleted(),
                'remaining' => self::humanizeRemaining($challenge->getEndsAt(), $now),
            ];

            if ($challenge->getEndsAt() >= $now && !($progress && $progress->isCompleted())) {
                $active[] = $entry;
            } else {
                $completed[] = $entry;
            }
        }

        return ['active' => $active, 'completed' => $completed];
    }

    /**
     * Temps restant avant l'echeance d'un defi, en unite + quantite.
     *
     * L'unite est rendue cote Twig : la traduction choisit le pluriel, pas le
     * PHP.
     *
     * @return array{unit: string, count: int}
     */
    public static function humanizeRemaining(\DateTimeInterface $endsAt, \DateTimeInterface $now): array
    {
        $seconds = $endsAt->getTimestamp() - $now->getTimestamp();
        if ($seconds <= 0) {
            return ['unit' => 'ended', 'count' => 0];
        }

        $days = intdiv($seconds, 86400);
        if ($days >= 1) {
            return ['unit' => 'days', 'count' => $days];
        }

        $hours = intdiv($seconds, 3600);
        if ($hours >= 1) {
            return ['unit' => 'hours', 'count' => $hours];
        }

        return ['unit' => 'minutes', 'count' => max(1, intdiv($seconds, 60))];
    }
}
