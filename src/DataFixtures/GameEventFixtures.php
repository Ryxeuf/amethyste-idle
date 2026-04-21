<?php

namespace App\DataFixtures;

use App\Entity\App\GameEvent;
use App\Entity\Game\Item;
use App\Entity\Game\Quest;
use App\Enum\ItemRarity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GameEventFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // --- Base game events (bonus, festivals) ---
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

        // --- Cosmetic items for the festival ---
        $cosmeticItems = [
            'cosmetic-festival-crown' => [
                'name' => 'Couronne du Festival',
                'slug' => 'cosmetic-festival-crown',
                'description' => 'Une couronne dorée ornée de fleurs, symbole du Festival de la Lune.',
                'type' => Item::TYPE_STUFF,
                'rarity' => ItemRarity::Rare,
                'price' => 0,
                'level' => 1,
                'is_cosmetic' => true,
                'bound_to_player' => true,
            ],
            'cosmetic-festival-cape' => [
                'name' => 'Cape Stellaire',
                'slug' => 'cosmetic-festival-cape',
                'description' => 'Une cape scintillante aux reflets d\'étoiles, récompense exclusive du Festival.',
                'type' => Item::TYPE_STUFF,
                'rarity' => ItemRarity::Epic,
                'price' => 0,
                'level' => 1,
                'is_cosmetic' => true,
                'bound_to_player' => true,
            ],
        ];

        foreach ($cosmeticItems as $key => $data) {
            $item = new Item();
            $item->setName($data['name']);
            $item->setSlug($data['slug']);
            $item->setDescription($data['description']);
            $item->setType($data['type']);
            $item->setRarity($data['rarity']);
            $item->setPrice($data['price']);
            $item->setLevel($data['level']);
            $item->setIsCosmetic($data['is_cosmetic']);
            $item->setBoundToPlayer($data['bound_to_player']);
            $item->setSpace(1);
            $item->setCreatedAt(new \DateTime());
            $item->setUpdatedAt(new \DateTime());
            $manager->persist($item);
            $this->addReference($key, $item);
        }

        $manager->flush();

        // --- Event Quests (linked to Festival de la Lune) ---
        $eventQuests = [
            'quest_festival_hunt' => [
                'name' => 'Chasse aux Etoiles',
                'description' => 'Pendant le Festival de la Lune, les monstres libèrent de l\'énergie stellaire. Eliminez 5 monstres pour collecter cette énergie.',
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Zombie', 'slug' => 'zombie', 'count' => 3],
                        ['name' => 'Squelette', 'slug' => 'skeleton', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'gold' => 50,
                    'xp' => 100,
                    'items' => [
                        ['genericItemSlug' => 'cosmetic-festival-crown', 'count' => 1],
                    ],
                ],
            ],
            'quest_festival_collect' => [
                'name' => 'Offrande Stellaire',
                'description' => 'Récoltez des herbes rares pour préparer l\'offrande du Festival. Collectez 3 champignons et 2 herbes.',
                'requirements' => [
                    'collect' => [
                        ['name' => 'Champignon', 'slug' => 'mushroom', 'count' => 3],
                        ['name' => 'Herbe médicinale', 'slug' => 'medicinal-herb', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'gold' => 75,
                    'xp' => 150,
                    'items' => [
                        ['genericItemSlug' => 'cosmetic-festival-cape', 'count' => 1],
                    ],
                ],
            ],
        ];

        foreach ($eventQuests as $key => $data) {
            $quest = new Quest();
            $quest->setName($data['name']);
            $quest->setDescription($data['description']);
            $quest->setRequirements($data['requirements']);
            $quest->setRewards($data['rewards']);
            $quest->setGameEvent($this->getReference('event_festival_lune', GameEvent::class));
            $quest->setCreatedAt(new \DateTime());
            $quest->setUpdatedAt(new \DateTime());
            $manager->persist($quest);
            $this->addReference($key, $quest);
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
            'event_recolte_abondante' => [
                'name' => 'Recolte abondante',
                'type' => GameEvent::TYPE_GATHERING_BONUS,
                'description' => 'Les ressources naturelles poussent avec vigueur : peche, depecage et herboristerie rendent plus de materiaux pendant l\'evenement.',
                'status' => GameEvent::STATUS_SCHEDULED,
                'starts_at' => '+5 days',
                'ends_at' => '+8 days',
                'parameters' => ['multiplier' => 1.5],
            ],
            'event_world_boss_wyrm' => [
                'name' => 'Apparition du Wyrm Ancien',
                'type' => GameEvent::TYPE_BOSS_SPAWN,
                'description' => 'Un Wyrm Ancien emerge des profondeurs ! Les aventuriers les plus braves peuvent l\'affronter au village. Vainquez-le avant qu\'il ne disparaisse !',
                'status' => GameEvent::STATUS_SCHEDULED,
                'starts_at' => '+1 day',
                'ends_at' => '+2 days',
                'parameters' => [
                    'monster_slug' => 'ancient_wyrm',
                    'map_id' => 2,
                    'coordinates' => '20.20',
                    'level' => 30,
                ],
                'recurring' => true,
                'recurrence_interval' => 10080, // 7 jours en minutes
            ],
            'event_invasion_goblin' => [
                'name' => 'Invasion gobeline',
                'type' => GameEvent::TYPE_INVASION,
                'description' => 'Des hordes de gobelins et de squelettes deferlent sur le village ! Repoussez-les avant qu\'ils ne submergent la zone.',
                'status' => GameEvent::STATUS_SCHEDULED,
                'starts_at' => '+3 days',
                'ends_at' => '+3 days 15 minutes',
                'parameters' => [
                    'mob_slugs' => ['goblin', 'skeleton'],
                    'count_per_wave' => 4,
                    'map_id' => 2,
                    'spawn_coordinates' => ['15.10', '16.10', '17.10', '15.11', '16.11', '17.11'],
                    'wave_count' => 3,
                    'wave_interval_seconds' => 120,
                    'kill_objective' => 8,
                    'rewards' => ['gold' => 150, 'xp' => 300],
                ],
                'recurring' => true,
                'recurrence_interval' => 4320, // 3 jours en minutes
            ],
        ];
    }
}
