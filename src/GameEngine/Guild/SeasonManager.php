<?php

namespace App\GameEngine\Guild;

use App\Entity\App\InfluenceSeason;
use App\Enum\SeasonStatus;
use Doctrine\ORM\EntityManagerInterface;

class SeasonManager
{
    public const SEASON_DURATION_DAYS = 28;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getCurrentSeason(): ?InfluenceSeason
    {
        return $this->entityManager->getRepository(InfluenceSeason::class)->findOneBy(
            ['status' => SeasonStatus::Active],
        );
    }

    public function getOrCreateNextSeason(): InfluenceSeason
    {
        $scheduled = $this->entityManager->getRepository(InfluenceSeason::class)->findOneBy(
            ['status' => SeasonStatus::Scheduled],
            ['seasonNumber' => 'ASC'],
        );

        if ($scheduled) {
            return $scheduled;
        }

        $lastSeason = $this->entityManager->getRepository(InfluenceSeason::class)->findOneBy(
            [],
            ['seasonNumber' => 'DESC'],
        );

        $nextNumber = $lastSeason ? $lastSeason->getSeasonNumber() + 1 : 1;

        $startsAt = $lastSeason
            ? new \DateTime($lastSeason->getEndsAt()->format('Y-m-d H:i:s'))
            : new \DateTime('next monday 00:00:00');

        $endsAt = (clone $startsAt)->modify('+' . self::SEASON_DURATION_DAYS . ' days');

        $season = new InfluenceSeason();
        $season->setName(sprintf('Saison %d', $nextNumber));
        $season->setSlug(sprintf('saison-%d', $nextNumber));
        $season->setSeasonNumber($nextNumber);
        $season->setStartsAt($startsAt);
        $season->setEndsAt($endsAt);
        $season->setStatus(SeasonStatus::Scheduled);
        $season->setCreatedAt(new \DateTime());
        $season->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($season);
        $this->entityManager->flush();

        return $season;
    }

    /**
     * @throws \LogicException
     */
    public function startSeason(InfluenceSeason $season): void
    {
        if (!$season->isScheduled()) {
            throw new \LogicException(sprintf('Impossible de démarrer la saison "%s" : statut actuel = %s.', $season->getName(), $season->getStatus()->value));
        }

        $current = $this->getCurrentSeason();
        if ($current) {
            throw new \LogicException(sprintf('Une saison est déjà active : "%s".', $current->getName()));
        }

        $season->setStatus(SeasonStatus::Active);
        $season->setStartsAt(new \DateTime());
        $season->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();
    }

    /**
     * @throws \LogicException
     */
    public function endSeason(InfluenceSeason $season): void
    {
        if (!$season->isActive()) {
            throw new \LogicException(sprintf('Impossible de terminer la saison "%s" : statut actuel = %s.', $season->getName(), $season->getStatus()->value));
        }

        $season->setStatus(SeasonStatus::Completed);
        $season->setEndsAt(new \DateTime());
        $season->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();
    }
}
