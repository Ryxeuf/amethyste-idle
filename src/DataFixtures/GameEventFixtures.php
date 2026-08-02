<?php

namespace App\DataFixtures;

use App\Entity\App\GameEvent;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\Entity\Game\Quest;
use App\Enum\BindType;
use App\Enum\ItemRarity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class GameEventFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Le graphe de zones doit exister : un evenement de zone (ZON-15) et un
     * boss de zone (ZON-18) sont rattaches a une `Zone`, et `ZoneBossManager`
     * ignore purement et simplement un evenement qui n'en a pas.
     */
    public function getDependencies(): array
    {
        return [ZoneGraphFixtures::class];
    }

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

            // Rattachement a une zone (ZON-15) : sans lui, un evenement de type
            // `boss_spawn` ne fait naitre aucun boss — `ZoneBossManager` sort
            // des la premiere ligne.
            if (isset($data['zone'])) {
                $zone = $manager->getRepository(Zone::class)->findOneBy(['slug' => $data['zone']]);
                if ($zone instanceof Zone) {
                    $event->setZone($zone);
                }
            }

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
            $item->setBindType(BindType::fromLegacyFlag((bool) $data['bound_to_player']));
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
                'name_translations' => ['en' => 'Star Hunt'],
                'description' => 'Pendant le Festival de la Lune, les monstres libèrent de l\'énergie stellaire. Eliminez 5 monstres pour collecter cette énergie.',
                'description_translations' => ['en' => 'During the Festival of the Moon, monsters release stellar energy. Defeat 5 monsters to collect this energy.'],
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
                'name_translations' => ['en' => 'Stellar Offering'],
                'description' => 'Récoltez des herbes rares pour préparer l\'offrande du Festival. Collectez 3 champignons et 2 herbes.',
                'description_translations' => ['en' => 'Gather rare herbs to prepare the Festival offering. Collect 3 mushrooms and 2 herbs.'],
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
            if (isset($data['name_translations']) && is_array($data['name_translations'])) {
                $quest->setNameTranslations($data['name_translations']);
            }
            $quest->setDescription($data['description']);
            if (isset($data['description_translations']) && is_array($data['description_translations'])) {
                $quest->setDescriptionTranslations($data['description_translations']);
            }
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
            // === Boss final de l'Acte 4 (tache 128d) ===
            //
            // Le boss de zone naît de l'**activation** de cet evenement
            // (`ZoneBossManager` ecoute `GameEventActivatedEvent`) : il faut
            // donc une zone et un `monster_slug`, sans quoi rien ne se passe.
            //
            // `boss_hp` est fixe ici et non sur le monstre : la barre d'un
            // combat collectif se regle par evenement, pour qu'un ajustement
            // d'affluence ne touche pas au bestiaire.
            //
            // Recurrent toutes les 72 h : un boss de zone qu'on ne peut affronter
            // qu'une fois est un contenu que la moitie du serveur manquera.
            'event_acte4_premier_silence' => [
                'name' => 'Le Premier Silence',
                'type' => GameEvent::TYPE_BOSS_SPAWN,
                'description' => 'Quelque chose se reveille sous le Glacier du Silence. La glace craque a des lieues a la ronde.',
                'status' => GameEvent::STATUS_SCHEDULED,
                'starts_at' => '+1 hour',
                'ends_at' => '+7 hours',
                'zone' => 'glacier-du-silence',
                'parameters' => [
                    'monster_slug' => 'the_first_silence',
                    'boss_hp' => 60000,
                ],
                'recurring' => true,
                'recurrence_interval' => 4320, // 72 h en minutes
            ],

            // === Boss des deux zones de depart hostiles ===
            //
            // Le Gardien de la Foret et le Seigneur de la Forge existaient dans
            // le bestiaire, peuplaient leur zone, et l'entree de Codex
            // `bestiary-gardien-foret` attendait un kill de boss — mais aucun
            // evenement ne les faisait naitre en combat collectif. Un joueur du
            // monde de depart n'avait donc jamais rencontre la mecanique de
            // boss de zone (ZON-18) avant l'Acte 4.
            //
            // Les barres sont taillees pour une poignee de joueurs, pas pour un
            // serveur : un assaut inflige environ la valeur de `hit` du joueur
            // et coute 10 d'energie, soit de l'ordre du millier de degats par
            // joueur et par jour au niveau de la zone.
            'event_boss_gardien_foret' => [
                'name' => 'Le Gardien s\'eveille',
                'type' => GameEvent::TYPE_BOSS_SPAWN,
                'description' => 'Les arbres de la Foret des murmures se sont tus d\'un coup. Quelque chose de vieux vient de se lever au cœur du bois.',
                'status' => GameEvent::STATUS_SCHEDULED,
                'starts_at' => '+2 hours',
                'ends_at' => '+14 hours',
                'zone' => 'foret-des-murmures',
                'parameters' => [
                    'monster_slug' => 'forest_guardian',
                    'boss_hp' => 4000,
                ],
                'recurring' => true,
                'recurrence_interval' => 1440, // 24 h en minutes
            ],
            'event_boss_seigneur_forge' => [
                'name' => 'La Forge rallumee',
                'type' => GameEvent::TYPE_BOSS_SPAWN,
                'description' => 'Une lueur rouge monte des galeries basses et le sol tiedit sous les pas. Le Seigneur de la Forge a rallume ses feux.',
                'status' => GameEvent::STATUS_SCHEDULED,
                'starts_at' => '+6 hours',
                'ends_at' => '+18 hours',
                'zone' => 'mines-profondes',
                'parameters' => [
                    'monster_slug' => 'forge_lord',
                    'boss_hp' => 6500,
                ],
                'recurring' => true,
                'recurrence_interval' => 2160, // 36 h en minutes
            ],
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
                    'tier' => 4,
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
