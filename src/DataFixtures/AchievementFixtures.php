<?php

namespace App\DataFixtures;

use App\Entity\Game\Achievement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AchievementFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $achievements = [
            // === COMBAT: Mobs tués par type ===
            // Slime
            ['slug' => 'kill-slime-10', 'title' => 'Nettoyeur de gelées', 'description' => 'Tuer 10 gelées', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'slime', 'count' => 10], 'reward' => ['gils' => 50]],
            ['slug' => 'kill-slime-50', 'title' => 'Chasseur de gelées', 'description' => 'Tuer 50 gelées', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'slime', 'count' => 50], 'reward' => ['gils' => 200]],
            ['slug' => 'kill-slime-100', 'title' => 'Exterminateur de gelées', 'description' => 'Tuer 100 gelées', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'slime', 'count' => 100], 'reward' => ['gils' => 500, 'title' => 'Exterminateur de gelées']],
            // Goblin
            ['slug' => 'kill-goblin-10', 'title' => 'Nettoyeur de gobelins', 'description' => 'Tuer 10 gobelins', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'goblin', 'count' => 10], 'reward' => ['gils' => 50]],
            ['slug' => 'kill-goblin-50', 'title' => 'Chasseur de gobelins', 'description' => 'Tuer 50 gobelins', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'goblin', 'count' => 50], 'reward' => ['gils' => 200]],
            ['slug' => 'kill-goblin-100', 'title' => 'Exterminateur de gobelins', 'description' => 'Tuer 100 gobelins', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'goblin', 'count' => 100], 'reward' => ['gils' => 500, 'title' => 'Exterminateur de gobelins']],
            // Zombie
            ['slug' => 'kill-zombie-10', 'title' => 'Nettoyeur de zombies', 'description' => 'Tuer 10 zombies', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'zombie', 'count' => 10], 'reward' => ['gils' => 75]],
            ['slug' => 'kill-zombie-50', 'title' => 'Chasseur de zombies', 'description' => 'Tuer 50 zombies', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'zombie', 'count' => 50], 'reward' => ['gils' => 250]],
            ['slug' => 'kill-zombie-100', 'title' => 'Exterminateur de zombies', 'description' => 'Tuer 100 zombies', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'zombie', 'count' => 100], 'reward' => ['gils' => 600, 'title' => 'Exterminateur de zombies']],
            // Skeleton
            ['slug' => 'kill-skeleton-10', 'title' => 'Briseur d\'os', 'description' => 'Tuer 10 squelettes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'skeleton', 'count' => 10], 'reward' => ['gils' => 75]],
            ['slug' => 'kill-skeleton-50', 'title' => 'Chasseur de squelettes', 'description' => 'Tuer 50 squelettes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'skeleton', 'count' => 50], 'reward' => ['gils' => 250]],
            ['slug' => 'kill-skeleton-100', 'title' => 'Exterminateur de squelettes', 'description' => 'Tuer 100 squelettes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'skeleton', 'count' => 100], 'reward' => ['gils' => 600, 'title' => 'Exterminateur de squelettes']],
            // Spider
            ['slug' => 'kill-spider-10', 'title' => 'Arachnophobe', 'description' => 'Tuer 10 araignées', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'spider', 'count' => 10], 'reward' => ['gils' => 100]],
            ['slug' => 'kill-spider-50', 'title' => 'Chasseur d\'araignées', 'description' => 'Tuer 50 araignées', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'spider', 'count' => 50], 'reward' => ['gils' => 300]],
            ['slug' => 'kill-spider-100', 'title' => 'Exterminateur d\'araignées', 'description' => 'Tuer 100 araignées', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'spider', 'count' => 100], 'reward' => ['gils' => 700, 'title' => 'Exterminateur d\'araignées']],
            // Troll
            ['slug' => 'kill-troll-10', 'title' => 'Tueur de trolls', 'description' => 'Tuer 10 trolls', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'troll', 'count' => 10], 'reward' => ['gils' => 200]],
            ['slug' => 'kill-troll-50', 'title' => 'Chasseur de trolls', 'description' => 'Tuer 50 trolls', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'troll', 'count' => 50], 'reward' => ['gils' => 500]],
            ['slug' => 'kill-troll-100', 'title' => 'Exterminateur de trolls', 'description' => 'Tuer 100 trolls', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'troll', 'count' => 100], 'reward' => ['gils' => 1000, 'title' => 'Exterminateur de trolls']],
            // Werewolf
            ['slug' => 'kill-werewolf-10', 'title' => 'Tueur de loups-garous', 'description' => 'Tuer 10 loups-garous', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'werewolf', 'count' => 10], 'reward' => ['gils' => 150]],
            ['slug' => 'kill-werewolf-50', 'title' => 'Chasseur de loups-garous', 'description' => 'Tuer 50 loups-garous', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'werewolf', 'count' => 50], 'reward' => ['gils' => 400]],
            ['slug' => 'kill-werewolf-100', 'title' => 'Exterminateur de loups-garous', 'description' => 'Tuer 100 loups-garous', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'werewolf', 'count' => 100], 'reward' => ['gils' => 800, 'title' => 'Exterminateur de loups-garous']],
            // Dragon
            ['slug' => 'kill-dragon-10', 'title' => 'Tueur de dragons', 'description' => 'Tuer 10 dragons', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'dragon', 'count' => 10], 'reward' => ['gils' => 500]],
            ['slug' => 'kill-dragon-50', 'title' => 'Chasseur de dragons', 'description' => 'Tuer 50 dragons', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'dragon', 'count' => 50], 'reward' => ['gils' => 1500]],
            ['slug' => 'kill-dragon-100', 'title' => 'Pourfendeur de dragons', 'description' => 'Tuer 100 dragons', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'dragon', 'count' => 100], 'reward' => ['gils' => 5000, 'title' => 'Pourfendeur de dragons']],

            // === EXPLORATION: Monstres différents découverts ===
            ['slug' => 'discover-5', 'title' => 'Explorateur novice', 'description' => 'Combattre 5 types de monstres différents', 'category' => 'exploration', 'criteria' => ['type' => 'monster_discovery', 'count' => 5], 'reward' => ['gils' => 100]],
            ['slug' => 'discover-10', 'title' => 'Explorateur confirmé', 'description' => 'Combattre 10 types de monstres différents', 'category' => 'exploration', 'criteria' => ['type' => 'monster_discovery', 'count' => 10], 'reward' => ['gils' => 300]],
            ['slug' => 'discover-20', 'title' => 'Explorateur expert', 'description' => 'Combattre 20 types de monstres différents', 'category' => 'exploration', 'criteria' => ['type' => 'monster_discovery', 'count' => 20], 'reward' => ['gils' => 1000, 'title' => 'Explorateur expert']],

            // === QUETES ===
            ['slug' => 'quest-5', 'title' => 'Aventurier débutant', 'description' => 'Compléter 5 quêtes', 'category' => 'quests', 'criteria' => ['type' => 'quest_complete', 'count' => 5], 'reward' => ['gils' => 100]],
            ['slug' => 'quest-10', 'title' => 'Aventurier confirmé', 'description' => 'Compléter 10 quêtes', 'category' => 'quests', 'criteria' => ['type' => 'quest_complete', 'count' => 10], 'reward' => ['gils' => 300]],
            ['slug' => 'quest-25', 'title' => 'Aventurier aguerri', 'description' => 'Compléter 25 quêtes', 'category' => 'quests', 'criteria' => ['type' => 'quest_complete', 'count' => 25], 'reward' => ['gils' => 800]],
            ['slug' => 'quest-50', 'title' => 'Héros légendaire', 'description' => 'Compléter 50 quêtes', 'category' => 'quests', 'criteria' => ['type' => 'quest_complete', 'count' => 50], 'reward' => ['gils' => 2000, 'title' => 'Héros légendaire']],
        ];

        foreach ($achievements as $data) {
            $achievement = new Achievement();
            $achievement->setSlug($data['slug']);
            $achievement->setTitle($data['title']);
            $achievement->setDescription($data['description']);
            $achievement->setCategory($data['category']);
            $achievement->setCriteria($data['criteria']);
            $achievement->setReward($data['reward']);
            $achievement->setCreatedAt(new \DateTime());
            $achievement->setUpdatedAt(new \DateTime());

            $manager->persist($achievement);
            $this->addReference('achievement_' . $data['slug'], $achievement);
        }

        $manager->flush();
    }
}
