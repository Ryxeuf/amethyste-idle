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

            // Salamandre
            ['slug' => 'kill-salamander-10', 'title' => 'Chasseur de flammes', 'description' => 'Tuer 10 salamandres', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'salamander', 'count' => 10], 'reward' => ['gils' => 100]],
            ['slug' => 'kill-salamander-50', 'title' => 'Dompteur de salamandres', 'description' => 'Tuer 50 salamandres', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'salamander', 'count' => 50], 'reward' => ['gils' => 300]],
            ['slug' => 'kill-salamander-100', 'title' => 'Exterminateur de salamandres', 'description' => 'Tuer 100 salamandres', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'salamander', 'count' => 100], 'reward' => ['gils' => 700, 'title' => 'Exterminateur de salamandres']],
            // Ondine
            ['slug' => 'kill-undine-10', 'title' => 'Pêcheur d\'ondines', 'description' => 'Tuer 10 ondines', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'undine', 'count' => 10], 'reward' => ['gils' => 75]],
            ['slug' => 'kill-undine-50', 'title' => 'Chasseur d\'ondines', 'description' => 'Tuer 50 ondines', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'undine', 'count' => 50], 'reward' => ['gils' => 250]],
            ['slug' => 'kill-undine-100', 'title' => 'Exterminateur d\'ondines', 'description' => 'Tuer 100 ondines', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'undine', 'count' => 100], 'reward' => ['gils' => 600, 'title' => 'Exterminateur d\'ondines']],
            // Sylphe
            ['slug' => 'kill-sylph-10', 'title' => 'Briseur de vents', 'description' => 'Tuer 10 sylphes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'sylph', 'count' => 10], 'reward' => ['gils' => 150]],
            ['slug' => 'kill-sylph-50', 'title' => 'Chasseur de sylphes', 'description' => 'Tuer 50 sylphes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'sylph', 'count' => 50], 'reward' => ['gils' => 400]],
            ['slug' => 'kill-sylph-100', 'title' => 'Exterminateur de sylphes', 'description' => 'Tuer 100 sylphes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'sylph', 'count' => 100], 'reward' => ['gils' => 800, 'title' => 'Exterminateur de sylphes']],
            // Golem d'argile
            ['slug' => 'kill-clay-golem-10', 'title' => 'Briseur de golems', 'description' => 'Tuer 10 golems d\'argile', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'clay_golem', 'count' => 10], 'reward' => ['gils' => 200]],
            ['slug' => 'kill-clay-golem-50', 'title' => 'Chasseur de golems d\'argile', 'description' => 'Tuer 50 golems d\'argile', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'clay_golem', 'count' => 50], 'reward' => ['gils' => 500]],
            ['slug' => 'kill-clay-golem-100', 'title' => 'Exterminateur de golems d\'argile', 'description' => 'Tuer 100 golems d\'argile', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'clay_golem', 'count' => 100], 'reward' => ['gils' => 1000, 'title' => 'Exterminateur de golems d\'argile']],
            // Automate rouillé
            ['slug' => 'kill-rusty-automaton-10', 'title' => 'Ferrailleur', 'description' => 'Tuer 10 automates rouillés', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'rusty_automaton', 'count' => 10], 'reward' => ['gils' => 100]],
            ['slug' => 'kill-rusty-automaton-50', 'title' => 'Chasseur d\'automates', 'description' => 'Tuer 50 automates rouillés', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'rusty_automaton', 'count' => 50], 'reward' => ['gils' => 300]],
            ['slug' => 'kill-rusty-automaton-100', 'title' => 'Exterminateur d\'automates', 'description' => 'Tuer 100 automates rouillés', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'rusty_automaton', 'count' => 100], 'reward' => ['gils' => 700, 'title' => 'Exterminateur d\'automates']],
            // Loup alpha
            ['slug' => 'kill-alpha-wolf-10', 'title' => 'Tueur de meute', 'description' => 'Tuer 10 loups alpha', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'alpha_wolf', 'count' => 10], 'reward' => ['gils' => 150]],
            ['slug' => 'kill-alpha-wolf-50', 'title' => 'Chasseur de loups alpha', 'description' => 'Tuer 50 loups alpha', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'alpha_wolf', 'count' => 50], 'reward' => ['gils' => 400]],
            ['slug' => 'kill-alpha-wolf-100', 'title' => 'Exterminateur de loups alpha', 'description' => 'Tuer 100 loups alpha', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'alpha_wolf', 'count' => 100], 'reward' => ['gils' => 800, 'title' => 'Exterminateur de loups alpha']],
            // Feu follet
            ['slug' => 'kill-will-o-wisp-10', 'title' => 'Chasseur de lumières', 'description' => 'Tuer 10 feux follets', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'will_o_wisp', 'count' => 10], 'reward' => ['gils' => 75]],
            ['slug' => 'kill-will-o-wisp-50', 'title' => 'Éteigneur de feux follets', 'description' => 'Tuer 50 feux follets', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'will_o_wisp', 'count' => 50], 'reward' => ['gils' => 250]],
            ['slug' => 'kill-will-o-wisp-100', 'title' => 'Exterminateur de feux follets', 'description' => 'Tuer 100 feux follets', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'will_o_wisp', 'count' => 100], 'reward' => ['gils' => 600, 'title' => 'Exterminateur de feux follets']],
            // Ombre rampante
            ['slug' => 'kill-creeping-shadow-10', 'title' => 'Pourchasseur d\'ombres', 'description' => 'Tuer 10 ombres rampantes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'creeping_shadow', 'count' => 10], 'reward' => ['gils' => 200]],
            ['slug' => 'kill-creeping-shadow-50', 'title' => 'Chasseur d\'ombres rampantes', 'description' => 'Tuer 50 ombres rampantes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'creeping_shadow', 'count' => 50], 'reward' => ['gils' => 500]],
            ['slug' => 'kill-creeping-shadow-100', 'title' => 'Exterminateur d\'ombres', 'description' => 'Tuer 100 ombres rampantes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'creeping_shadow', 'count' => 100], 'reward' => ['gils' => 1000, 'title' => 'Exterminateur d\'ombres']],

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
