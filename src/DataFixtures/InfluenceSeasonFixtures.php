<?php

namespace App\DataFixtures;

use App\Entity\App\InfluenceSeason;
use App\Enum\SeasonStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class InfluenceSeasonFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTime();

        // Saison 1 : active (commencée il y a 7 jours, se termine dans 21 jours)
        $season1 = new InfluenceSeason();
        $season1->setName('Saison 1 — L\'Éveil des guildes');
        $season1->setSlug('saison-1');
        $season1->setSeasonNumber(1);
        $season1->setStartsAt((clone $now)->modify('-7 days'));
        $season1->setEndsAt((clone $now)->modify('+21 days'));
        $season1->setStatus(SeasonStatus::Active);
        $season1->setTheme('Éveil');
        $season1->setParameters([
            'multipliers' => [
                'mob_kill' => 1.0,
                'craft' => 1.2,
                'harvest' => 1.0,
                'quest' => 1.0,
            ],
        ]);
        $season1->setCreatedAt((clone $now)->modify('-14 days'));
        $season1->setUpdatedAt((clone $now)->modify('-7 days'));
        $manager->persist($season1);
        $this->addReference('influence_season_1', $season1);

        $manager->flush();
    }
}
