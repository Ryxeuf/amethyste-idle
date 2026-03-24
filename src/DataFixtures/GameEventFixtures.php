<?php

namespace App\DataFixtures;

use App\Entity\App\GameEvent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GameEventFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $events = $this->getEventsData();

        foreach ($events as $key => $data) {
            $event = new GameEvent();
            $event->setName($data['name']);
            $event->setType($data['type']);
            $event->setDescription($data['description']);
            $event->setStatus($data['status']);
            $event->setStartsAt(new \DateTime($data['starts_at']));
            $event->setEndsAt(new \DateTime($data['ends_at']));
            $event->setParameters($data['parameters'] ?? null);
            $event->setRecurring($data['recurring'] ?? false);
            $event->setRecurrenceInterval($data['recurrence_interval'] ?? null);
            $event->setCreatedAt(new \DateTime());
            $event->setUpdatedAt(new \DateTime());

            $manager->persist($event);
            $this->addReference($key, $event);
        }

        $manager->flush();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getEventsData(): array
    {
        return [
            'event_festival_lune' => [
                'name' => 'Festival de la Lune',
                'type' => GameEvent::TYPE_XP_BONUS,
                'description' => 'La lumiere de la lune baigne le monde d\'Amethyste. Tous les gains d\'XP sont doubles pendant le festival !',
                'status' => GameEvent::STATUS_ACTIVE,
                'starts_at' => '-1 day',
                'ends_at' => '+6 days',
                'parameters' => ['multiplier' => 2],
                'recurring' => true,
                'recurrence_interval' => 43200, // 30 jours en minutes
            ],
            'event_chasse_abondante' => [
                'name' => 'Chasse abondante',
                'type' => GameEvent::TYPE_DROP_BONUS,
                'description' => 'Les monstres lachent plus de butin que d\'habitude. Profitez-en pour remplir vos sacs !',
                'status' => GameEvent::STATUS_SCHEDULED,
                'starts_at' => '+7 days',
                'ends_at' => '+10 days',
                'parameters' => ['multiplier' => 1.5],
            ],
            'event_nuit_ombres' => [
                'name' => 'La Nuit des Ombres',
                'type' => GameEvent::TYPE_DROP_BONUS,
                'description' => 'Des creatures d\'ombre envahissent le monde. Les drops rares sont plus frequents et des quetes speciales sont disponibles.',
                'status' => GameEvent::STATUS_ACTIVE,
                'starts_at' => '-2 days',
                'ends_at' => '+5 days',
                'parameters' => ['multiplier' => 1.75],
            ],
        ];
    }
}
