<?php

namespace App\GameEngine\Guild;

use App\Entity\App\GuildChallengeProgress;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Parameter;
use App\Entity\App\WeeklyChallenge;
use App\Enum\InfluenceActivityType;
use App\GameEngine\Realtime\Guild\InfluenceMercurePublisher;
use App\Repository\GuildChallengeProgressRepository;
use App\Repository\WeeklyChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Rotation hebdomadaire des defis de guilde (RET-01).
 *
 * Le systeme etait complet — criteres, bonus, `weekNumber`, suivi par guilde —
 * mais **rien ne le faisait tourner** : les fixtures posaient les semaines 1 et
 * 2, apres quoi l'ecran de guilde restait vide pour toujours. Cette classe est
 * la piece manquante, appelee chaque lundi 00h00 par le scheduler.
 *
 * Trois gestes, dans cet ordre :
 *
 * 1. **Cloturer la semaine ecoulee.** Les bonus sont normalement verses en
 *    temps reel par `ChallengeTracker` des que le compteur atteint sa cible ;
 *    la cloture est le filet : une progression qui a atteint sa cible sans
 *    avoir ete marquee (cible abaissee, incrementation par un chemin qui n'a
 *    pas conclu) est reglee ici plutot que perdue en silence.
 * 2. **Activer la semaine qui commence** — les defis dont la fenetre la
 *    recouvre.
 * 3. **En creer** si la saison n'en a plus, depuis le pool declaratif
 *    (`config/game/weekly_challenges.yaml`). C'est ce qui empeche le systeme
 *    de s'eteindre passe les semaines posees par les fixtures.
 *
 * **Idempotence** : la semaine ISO deja traitee est memorisee dans un
 * `Parameter`. Relancer la commande le meme lundi ne fait rien — ni double
 * versement, ni doublon de defi.
 */
class WeeklyChallengeRotator
{
    /**
     * Cle du `Parameter` qui memorise la derniere semaine ISO traitee.
     */
    public const string PARAMETER_NAME = 'weekly_challenge_rotation';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WeeklyChallengeRepository $challengeRepository,
        private readonly GuildChallengeProgressRepository $progressRepository,
        private readonly SeasonManager $seasonManager,
        private readonly InfluenceManager $influenceManager,
        private readonly WeeklyChallengeTemplateLoader $templateLoader,
        private readonly InfluenceMercurePublisher $mercurePublisher,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function rotate(?\DateTimeImmutable $now = null, bool $force = false): WeeklyChallengeRotationResult
    {
        $now ??= new \DateTimeImmutable();

        $weekStart = $now->modify('monday this week')->setTime(0, 0, 0);
        $weekEnd = $weekStart->modify('+6 days')->setTime(23, 59, 59);
        $weekKey = $weekStart->format('o-\WW');

        $parameter = $this->entityManager->getRepository(Parameter::class)->findOneBy([
            'name' => self::PARAMETER_NAME,
        ]);

        if (!$force && $parameter !== null && $parameter->getValue() === $weekKey) {
            return WeeklyChallengeRotationResult::skipped($weekKey, sprintf('Rotation deja effectuee pour la semaine %s.', $weekKey));
        }

        $season = $this->seasonManager->getCurrentSeason();
        if ($season === null) {
            // Volontairement **sans** memoriser la semaine : la rotation doit
            // repasser des qu'une saison demarre, pas attendre lundi prochain.
            return WeeklyChallengeRotationResult::skipped($weekKey, 'Aucune saison active : rien a faire cette semaine.');
        }

        $closure = $this->closePreviousWeek($weekStart, $season);
        $active = $this->challengeRepository->findOverlapping($season, $weekStart, $weekEnd);

        $created = [];
        if ($active === []) {
            $created = $this->createChallengesForWeek($season, $weekStart, $weekEnd);
            $active = $created;
        }

        $this->rememberWeek($parameter, $weekKey);
        $this->entityManager->flush();

        if ($active !== []) {
            $this->mercurePublisher->publishChallengeRotation($season, $active, $weekStart, $weekEnd);
        }

        $this->logger->info('Weekly challenge rotation {week}: {closed} closed, {created} created, {active} active', [
            'week' => $weekKey,
            'closed' => $closure['challenges'],
            'created' => \count($created),
            'active' => \count($active),
        ]);

        return new WeeklyChallengeRotationResult(
            weekKey: $weekKey,
            rotated: true,
            reason: sprintf('Semaine %s ouverte.', $weekKey),
            closedChallenges: $closure['challenges'],
            settledProgress: $closure['settled'],
            awardedBonusPoints: $closure['points'],
            createdChallenges: \count($created),
            activeChallenges: $active,
            weekStart: $weekStart,
            weekEnd: $weekEnd,
        );
    }

    /**
     * Cloture les defis dont l'echeance tombe dans la semaine ecoulee.
     *
     * @return array{challenges: int, settled: int, points: int}
     */
    private function closePreviousWeek(\DateTimeImmutable $weekStart, InfluenceSeason $season): array
    {
        $previousStart = $weekStart->modify('-7 days');
        $ended = $this->challengeRepository->findEndingBetween($previousStart, $weekStart);

        if ($ended === []) {
            return ['challenges' => 0, 'settled' => 0, 'points' => 0];
        }

        $settled = 0;
        $points = 0;

        foreach ($this->progressRepository->findForChallenges($ended) as $progress) {
            if ($progress->isCompleted()) {
                continue;
            }

            $challenge = $progress->getChallenge();
            if ($progress->getProgress() < $challenge->getTarget()) {
                continue;
            }

            $progress->setCompletedAt(new \DateTime());
            $progress->setUpdatedAt(new \DateTime());
            ++$settled;
            $points += $this->awardClosureBonus($progress, $challenge, $season);
        }

        return ['challenges' => \count($ended), 'settled' => $settled, 'points' => $points];
    }

    /**
     * Verse le bonus d'un defi regle a la cloture.
     *
     * Il n'y a pas de « joueur declencheur » a minuit un lundi : le versement
     * est credite au chef de guilde, dans la region ou il se tient. Si aucune
     * region n'est resolvable, le defi est tout de meme marque complete — une
     * progression laissee ouverte pour toujours serait pire — et l'anomalie est
     * tracee plutot que passee sous silence.
     */
    private function awardClosureBonus(GuildChallengeProgress $progress, WeeklyChallenge $challenge, InfluenceSeason $season): int
    {
        $guild = $progress->getGuild();
        $leader = $guild->getLeader();
        $region = $this->influenceManager->getPlayerRegion($leader);

        if ($region === null) {
            $this->logger->warning('Weekly challenge "{title}" settled for guild {guild} without influence credit: no region resolvable.', [
                'title' => $challenge->getTitle(),
                'guild' => $guild->getName(),
            ]);

            return 0;
        }

        $this->influenceManager->addPoints(
            $guild,
            $region,
            $season,
            $challenge->getBonusPoints(),
            $leader,
            InfluenceActivityType::Challenge,
            ['challenge' => $challenge->getTitle(), 'bonus_points' => $challenge->getBonusPoints(), 'settled_at_rotation' => true],
        );

        return $challenge->getBonusPoints();
    }

    /**
     * @return list<WeeklyChallenge>
     */
    private function createChallengesForWeek(InfluenceSeason $season, \DateTimeImmutable $weekStart, \DateTimeImmutable $weekEnd): array
    {
        $pool = $this->templateLoader->load();
        $weekNumber = $this->challengeRepository->maxWeekNumber($season) + 1;
        $templates = $this->selectTemplates($pool['challenges'], $pool['per_week'], $weekNumber);

        $created = [];
        foreach ($templates as $template) {
            $challenge = new WeeklyChallenge();
            $challenge->setSeason($season);
            $challenge->setTitle($template->title);
            $challenge->setTitleTranslations($template->titleEn !== null ? ['en' => $template->titleEn] : null);
            $challenge->setDescription($template->description);
            $challenge->setDescriptionTranslations($template->descriptionEn !== null ? ['en' => $template->descriptionEn] : null);
            $challenge->setActivityType($template->activity);
            $challenge->setCriteria(['target' => $template->target, 'template' => $template->slug]);
            $challenge->setBonusPoints($template->bonusPoints);
            $challenge->setWeekNumber($weekNumber);
            $challenge->setStartsAt(\DateTime::createFromImmutable($weekStart));
            $challenge->setEndsAt(\DateTime::createFromImmutable($weekEnd));
            $challenge->setCreatedAt(new \DateTime());
            $challenge->setUpdatedAt(new \DateTime());

            $this->entityManager->persist($challenge);
            $created[] = $challenge;
        }

        return $created;
    }

    /**
     * Choisit les gabarits de la semaine, **sans aleatoire**.
     *
     * Deux exigences se rencontrent ici : les defis doivent changer d'une
     * semaine sur l'autre, et deux serveurs a la meme semaine doivent tomber sur
     * la meme selection (c'est ce qui rend la rotation reproductible en test et
     * rejouable apres incident). Le numero de semaine sert donc de curseur : on
     * balaie les activites en rond, en descendant d'un cran dans le pool de
     * chaque activite a chaque tour complet.
     *
     * @param list<WeeklyChallengeTemplate> $templates
     *
     * @return list<WeeklyChallengeTemplate>
     */
    private function selectTemplates(array $templates, int $perWeek, int $weekNumber): array
    {
        /** @var array<string, list<WeeklyChallengeTemplate>> $byActivity */
        $byActivity = [];
        foreach ($templates as $template) {
            $byActivity[$template->activity->value][] = $template;
        }
        ksort($byActivity);

        $activities = array_keys($byActivity);
        $activityCount = \count($activities);
        $wanted = min($perWeek, \count($templates));

        $selected = [];
        $seen = [];
        // Borne dure : chaque activite peut etre visitee autant de fois qu'elle
        // a de gabarits, plus un tour de garde. Sans elle, un pool trop pauvre
        // pour `per_week` ferait tourner la boucle indefiniment.
        $maxVisits = \count($templates) + $activityCount;

        for ($visit = 0; $visit < $maxVisits && \count($selected) < $wanted; ++$visit) {
            $activity = $activities[($weekNumber - 1 + $visit) % $activityCount];
            $pool = $byActivity[$activity];
            $depth = intdiv($weekNumber - 1, $activityCount) + intdiv($visit, $activityCount);
            $candidate = $pool[$depth % \count($pool)];

            if (isset($seen[$candidate->slug])) {
                continue;
            }
            $seen[$candidate->slug] = true;
            $selected[] = $candidate;
        }

        return $selected;
    }

    private function rememberWeek(?Parameter $parameter, string $weekKey): void
    {
        if ($parameter === null) {
            $parameter = new Parameter();
            $parameter->setName(self::PARAMETER_NAME);
            $parameter->setCreatedAt(new \DateTime());
            $this->entityManager->persist($parameter);
        }

        $parameter->setValue($weekKey);
        $parameter->setUpdatedAt(new \DateTime());
    }
}
