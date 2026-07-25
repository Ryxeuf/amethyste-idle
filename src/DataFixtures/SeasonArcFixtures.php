<?php

namespace App\DataFixtures;

use App\Entity\App\GameEvent;
use App\Entity\App\InfluenceSeason;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Arc de la Saison 1 (NAR-08) : le theme « Éveil » decline en 4 beats dates
 * (amorce / montee / climax / resolution), chacun un GameEvent rattache a la
 * saison avec une fenetre de 7 jours, contiguë et contenue dans la saison.
 * Composition purement declarative — ajouter une saison = ajouter de la donnee.
 */
class SeasonArcFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var InfluenceSeason $season */
        $season = $this->getReference('influence_season_1', InfluenceSeason::class);

        // Fenetres derivees des bornes reelles de la saison (contiguës, contenues
        // dans [startsAt, endsAt] = 28 jours), pour rester exactement dans l'arc.
        $base = \DateTime::createFromInterface($season->getStartsAt());

        $beats = [
            [
                'beat' => GameEvent::BEAT_AMORCE,
                'order' => 1,
                'name' => 'Éveil — Amorce : les cloches sonnent',
                'description' => 'Un présage traverse les cités : quelque chose s\'éveille. Les guildes prennent la mesure de la menace.',
                'startDay' => 0,
                'endDay' => 7,
            ],
            [
                'beat' => GameEvent::BEAT_MONTEE,
                'order' => 2,
                'name' => 'Éveil — Montée : la pression grandit',
                'description' => 'Les incursions se multiplient. Chaque effort des guildes nourrit la lutte pour le contrôle des régions.',
                'startDay' => 7,
                'endDay' => 14,
            ],
            [
                'beat' => GameEvent::BEAT_CLIMAX,
                'order' => 3,
                'name' => 'Éveil — Climax : la Faille',
                'description' => 'La menace atteint son paroxysme. Un événement de zone appelle tous les aventuriers à l\'assaut.',
                'startDay' => 14,
                'endDay' => 21,
                // Boss de saison (NAR-10) : spawn asynchrone sur la fenetre de climax,
                // combat partage a la contribution (WorldBossManager + WorldBossLootDistributor).
                'parameters' => [
                    'monster_slug' => 'forest_guardian',
                    'map_id' => 3,
                    'coordinates' => '20.20',
                ],
            ],
            [
                'beat' => GameEvent::BEAT_RESOLUTION,
                'order' => 4,
                'name' => 'Éveil — Résolution : l\'accalmie',
                'description' => 'La saison se referme. La guilde ayant tenu la région en récolte les crédits narratifs.',
                'startDay' => 21,
                'endDay' => 28,
            ],
        ];

        foreach ($beats as $data) {
            $event = new GameEvent();
            $event->setName($data['name']);
            $event->setDescription($data['description']);
            $event->setType(GameEvent::TYPE_CUSTOM);
            $event->setStatus(GameEvent::STATUS_SCHEDULED);
            $event->setStartsAt((clone $base)->modify(sprintf('+%d days', $data['startDay'])));
            $event->setEndsAt((clone $base)->modify(sprintf('+%d days', $data['endDay'])));
            $event->setSeason($season);
            $event->setBeat($data['beat']);
            $event->setBeatOrder($data['order']);
            if (isset($data['parameters'])) {
                $event->setParameters($data['parameters']);
            }
            $event->setCreatedAt(new \DateTime());
            $event->setUpdatedAt(new \DateTime());

            $manager->persist($event);
            $this->addReference('season1_beat_' . $data['beat'], $event);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            InfluenceSeasonFixtures::class,
        ];
    }
}
