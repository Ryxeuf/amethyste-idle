<?php

namespace App\DataFixtures;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\WeeklyChallenge;
use App\Enum\InfluenceActivityType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class WeeklyChallengeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $season = $this->getReference('influence_season_1', InfluenceSeason::class);
        $now = new \DateTime();
        $weekStart = (clone $now)->modify('monday this week');
        $weekEnd = (clone $weekStart)->modify('+6 days 23:59:59');

        // Defi 1 : Chasse (semaine en cours)
        $challenge1 = new WeeklyChallenge();
        $challenge1->setSeason($season);
        $challenge1->setTitle('Chasseur infatigable');
        $challenge1->setDescription('Eliminez 50 monstres en equipe pour prouver la valeur de votre guilde.');
        $challenge1->setActivityType(InfluenceActivityType::MobKill);
        $challenge1->setCriteria(['target' => 50]);
        $challenge1->setBonusPoints(100);
        $challenge1->setWeekNumber(1);
        $challenge1->setStartsAt($weekStart);
        $challenge1->setEndsAt($weekEnd);
        $challenge1->setCreatedAt(new \DateTime());
        $challenge1->setUpdatedAt(new \DateTime());
        $manager->persist($challenge1);
        $this->addReference('weekly_challenge_1', $challenge1);

        // Defi 2 : Artisanat (semaine en cours)
        $challenge2 = new WeeklyChallenge();
        $challenge2->setSeason($season);
        $challenge2->setTitle('Forge ardente');
        $challenge2->setDescription('Fabriquez 20 objets pour renforcer l\'arsenal de votre guilde.');
        $challenge2->setActivityType(InfluenceActivityType::Craft);
        $challenge2->setCriteria(['target' => 20]);
        $challenge2->setBonusPoints(80);
        $challenge2->setWeekNumber(1);
        $challenge2->setStartsAt($weekStart);
        $challenge2->setEndsAt($weekEnd);
        $challenge2->setCreatedAt(new \DateTime());
        $challenge2->setUpdatedAt(new \DateTime());
        $manager->persist($challenge2);
        $this->addReference('weekly_challenge_2', $challenge2);

        // Defi 3 : Recolte (semaine en cours)
        $challenge3 = new WeeklyChallenge();
        $challenge3->setSeason($season);
        $challenge3->setTitle('Moisson abondante');
        $challenge3->setDescription('Recoltez 100 ressources dans les champs et forets environnants.');
        $challenge3->setActivityType(InfluenceActivityType::Harvest);
        $challenge3->setCriteria(['target' => 100]);
        $challenge3->setBonusPoints(60);
        $challenge3->setWeekNumber(1);
        $challenge3->setStartsAt($weekStart);
        $challenge3->setEndsAt($weekEnd);
        $challenge3->setCreatedAt(new \DateTime());
        $challenge3->setUpdatedAt(new \DateTime());
        $manager->persist($challenge3);
        $this->addReference('weekly_challenge_3', $challenge3);

        // Defi 4 : Quetes (semaine prochaine, inactif)
        $nextWeekStart = (clone $weekStart)->modify('+7 days');
        $nextWeekEnd = (clone $nextWeekStart)->modify('+6 days 23:59:59');

        $challenge4 = new WeeklyChallenge();
        $challenge4->setSeason($season);
        $challenge4->setTitle('Heros du peuple');
        $challenge4->setDescription('Completez 10 quetes pour faire rayonner le nom de votre guilde.');
        $challenge4->setActivityType(InfluenceActivityType::Quest);
        $challenge4->setCriteria(['target' => 10]);
        $challenge4->setBonusPoints(120);
        $challenge4->setWeekNumber(2);
        $challenge4->setStartsAt($nextWeekStart);
        $challenge4->setEndsAt($nextWeekEnd);
        $challenge4->setCreatedAt(new \DateTime());
        $challenge4->setUpdatedAt(new \DateTime());
        $manager->persist($challenge4);
        $this->addReference('weekly_challenge_4', $challenge4);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            InfluenceSeasonFixtures::class,
        ];
    }
}
