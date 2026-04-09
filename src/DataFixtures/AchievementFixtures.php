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
            // Bat
            ['slug' => 'kill-bat-10', 'title' => 'Chasseur nocturne', 'description' => 'Tuer 10 chauves-souris', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'bat', 'count' => 10], 'reward' => ['gils' => 50]],
            ['slug' => 'kill-bat-50', 'title' => 'Chasseur de chauves-souris', 'description' => 'Tuer 50 chauves-souris', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'bat', 'count' => 50], 'reward' => ['gils' => 200]],
            ['slug' => 'kill-bat-100', 'title' => 'Exterminateur de chauves-souris', 'description' => 'Tuer 100 chauves-souris', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'bat', 'count' => 100], 'reward' => ['gils' => 500, 'title' => 'Exterminateur de chauves-souris']],
            // Giant rat
            ['slug' => 'kill-giant_rat-10', 'title' => 'Dératiseur', 'description' => 'Tuer 10 rats géants', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'giant_rat', 'count' => 10], 'reward' => ['gils' => 50]],
            ['slug' => 'kill-giant_rat-50', 'title' => 'Chasseur de rats', 'description' => 'Tuer 50 rats géants', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'giant_rat', 'count' => 50], 'reward' => ['gils' => 200]],
            ['slug' => 'kill-giant_rat-100', 'title' => 'Exterminateur de rats', 'description' => 'Tuer 100 rats géants', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'giant_rat', 'count' => 100], 'reward' => ['gils' => 500, 'title' => 'Exterminateur de rats']],
            // Venom snake
            ['slug' => 'kill-venom_snake-10', 'title' => 'Tueur de serpents', 'description' => 'Tuer 10 serpents venimeux', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'venom_snake', 'count' => 10], 'reward' => ['gils' => 75]],
            ['slug' => 'kill-venom_snake-50', 'title' => 'Chasseur de serpents', 'description' => 'Tuer 50 serpents venimeux', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'venom_snake', 'count' => 50], 'reward' => ['gils' => 250]],
            ['slug' => 'kill-venom_snake-100', 'title' => 'Exterminateur de serpents', 'description' => 'Tuer 100 serpents venimeux', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'venom_snake', 'count' => 100], 'reward' => ['gils' => 600, 'title' => 'Exterminateur de serpents']],
            // Wolf (tâche 140)
            ['slug' => 'kill-wolf-10', 'title' => 'Tueur de loups', 'description' => 'Tuer 10 loups', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'wolf', 'count' => 10], 'reward' => ['gils' => 50]],
            ['slug' => 'kill-wolf-50', 'title' => 'Chasseur de loups', 'description' => 'Tuer 50 loups', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'wolf', 'count' => 50], 'reward' => ['gils' => 200]],
            ['slug' => 'kill-wolf-100', 'title' => 'Exterminateur de loups', 'description' => 'Tuer 100 loups', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'wolf', 'count' => 100], 'reward' => ['gils' => 500, 'title' => 'Exterminateur de loups']],
            // Scorpion (tâche 140)
            ['slug' => 'kill-scorpion-10', 'title' => 'Piqueur de scorpions', 'description' => 'Tuer 10 scorpions', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'scorpion', 'count' => 10], 'reward' => ['gils' => 75]],
            ['slug' => 'kill-scorpion-50', 'title' => 'Chasseur de scorpions', 'description' => 'Tuer 50 scorpions', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'scorpion', 'count' => 50], 'reward' => ['gils' => 250]],
            ['slug' => 'kill-scorpion-100', 'title' => 'Exterminateur de scorpions', 'description' => 'Tuer 100 scorpions', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'scorpion', 'count' => 100], 'reward' => ['gils' => 600, 'title' => 'Exterminateur de scorpions']],
            // Beetle (tâche 140)
            ['slug' => 'kill-beetle-10', 'title' => 'Écraseur de scarabées', 'description' => 'Tuer 10 scarabées', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'beetle', 'count' => 10], 'reward' => ['gils' => 50]],
            ['slug' => 'kill-beetle-50', 'title' => 'Chasseur de scarabées', 'description' => 'Tuer 50 scarabées', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'beetle', 'count' => 50], 'reward' => ['gils' => 200]],
            ['slug' => 'kill-beetle-100', 'title' => 'Exterminateur de scarabées', 'description' => 'Tuer 100 scarabées', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'beetle', 'count' => 100], 'reward' => ['gils' => 500, 'title' => 'Exterminateur de scarabées']],
            // Mushroom golem (tâche 140)
            ['slug' => 'kill-mushroom_golem-10', 'title' => 'Cueilleur de champignons', 'description' => 'Tuer 10 golems champignons', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'mushroom_golem', 'count' => 10], 'reward' => ['gils' => 75]],
            ['slug' => 'kill-mushroom_golem-50', 'title' => 'Chasseur de champignons', 'description' => 'Tuer 50 golems champignons', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'mushroom_golem', 'count' => 50], 'reward' => ['gils' => 250]],
            ['slug' => 'kill-mushroom_golem-100', 'title' => 'Exterminateur de champignons', 'description' => 'Tuer 100 golems champignons', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'mushroom_golem', 'count' => 100], 'reward' => ['gils' => 600, 'title' => 'Exterminateur de champignons']],
            // Ghost (tâche 140)
            ['slug' => 'kill-ghost-10', 'title' => 'Chasseur de fantômes', 'description' => 'Tuer 10 fantômes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'ghost', 'count' => 10], 'reward' => ['gils' => 75]],
            ['slug' => 'kill-ghost-50', 'title' => 'Exorciste', 'description' => 'Tuer 50 fantômes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'ghost', 'count' => 50], 'reward' => ['gils' => 250]],
            ['slug' => 'kill-ghost-100', 'title' => 'Exterminateur de fantômes', 'description' => 'Tuer 100 fantômes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'ghost', 'count' => 100], 'reward' => ['gils' => 600, 'title' => 'Exterminateur de fantômes']],
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

            // Wyverne
            ['slug' => 'kill-wyvern-10', 'title' => 'Chasseur de wyvernes', 'description' => 'Tuer 10 wyvernes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'wyvern', 'count' => 10], 'reward' => ['gils' => 300]],
            ['slug' => 'kill-wyvern-50', 'title' => 'Dompteur de wyvernes', 'description' => 'Tuer 50 wyvernes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'wyvern', 'count' => 50], 'reward' => ['gils' => 800]],
            ['slug' => 'kill-wyvern-100', 'title' => 'Exterminateur de wyvernes', 'description' => 'Tuer 100 wyvernes', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'wyvern', 'count' => 100], 'reward' => ['gils' => 1500, 'title' => 'Exterminateur de wyvernes']],
            // Chevalier maudit
            ['slug' => 'kill-cursed-knight-10', 'title' => 'Briseur de malédictions', 'description' => 'Tuer 10 chevaliers maudits', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'cursed_knight', 'count' => 10], 'reward' => ['gils' => 350]],
            ['slug' => 'kill-cursed-knight-50', 'title' => 'Chasseur de chevaliers maudits', 'description' => 'Tuer 50 chevaliers maudits', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'cursed_knight', 'count' => 50], 'reward' => ['gils' => 900]],
            ['slug' => 'kill-cursed-knight-100', 'title' => 'Exterminateur de chevaliers maudits', 'description' => 'Tuer 100 chevaliers maudits', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'cursed_knight', 'count' => 100], 'reward' => ['gils' => 1800, 'title' => 'Exterminateur de chevaliers maudits']],
            // Naga
            ['slug' => 'kill-naga-10', 'title' => 'Chasseur de nagas', 'description' => 'Tuer 10 nagas', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'naga', 'count' => 10], 'reward' => ['gils' => 350]],
            ['slug' => 'kill-naga-50', 'title' => 'Dompteur de nagas', 'description' => 'Tuer 50 nagas', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'naga', 'count' => 50], 'reward' => ['gils' => 900]],
            ['slug' => 'kill-naga-100', 'title' => 'Exterminateur de nagas', 'description' => 'Tuer 100 nagas', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'naga', 'count' => 100], 'reward' => ['gils' => 1800, 'title' => 'Exterminateur de nagas']],
            // Golem de cristal
            ['slug' => 'kill-crystal-golem-10', 'title' => 'Briseur de cristaux', 'description' => 'Tuer 10 golems de cristal', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'crystal_golem', 'count' => 10], 'reward' => ['gils' => 400]],
            ['slug' => 'kill-crystal-golem-50', 'title' => 'Chasseur de golems de cristal', 'description' => 'Tuer 50 golems de cristal', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'crystal_golem', 'count' => 50], 'reward' => ['gils' => 1000]],
            ['slug' => 'kill-crystal-golem-100', 'title' => 'Exterminateur de golems de cristal', 'description' => 'Tuer 100 golems de cristal', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'crystal_golem', 'count' => 100], 'reward' => ['gils' => 2000, 'title' => 'Exterminateur de golems de cristal']],

            // Archidruide corrompu
            ['slug' => 'kill-corrupted-archdruid-10', 'title' => 'Purificateur de druides', 'description' => 'Tuer 10 archidruides corrompus', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'corrupted_archdruid', 'count' => 10], 'reward' => ['gils' => 400]],
            ['slug' => 'kill-corrupted-archdruid-50', 'title' => 'Chasseur d\'archidruides', 'description' => 'Tuer 50 archidruides corrompus', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'corrupted_archdruid', 'count' => 50], 'reward' => ['gils' => 1000]],
            ['slug' => 'kill-corrupted-archdruid-100', 'title' => 'Exterminateur d\'archidruides', 'description' => 'Tuer 100 archidruides corrompus', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'corrupted_archdruid', 'count' => 100], 'reward' => ['gils' => 2000, 'title' => 'Exterminateur d\'archidruides']],
            // Liche mineure
            ['slug' => 'kill-lesser-lich-10', 'title' => 'Briseur de phylactères', 'description' => 'Tuer 10 liches mineures', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'lesser_lich', 'count' => 10], 'reward' => ['gils' => 450]],
            ['slug' => 'kill-lesser-lich-50', 'title' => 'Chasseur de liches', 'description' => 'Tuer 50 liches mineures', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'lesser_lich', 'count' => 50], 'reward' => ['gils' => 1200]],
            ['slug' => 'kill-lesser-lich-100', 'title' => 'Exterminateur de liches', 'description' => 'Tuer 100 liches mineures', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'lesser_lich', 'count' => 100], 'reward' => ['gils' => 2500, 'title' => 'Exterminateur de liches']],
            // Hydre des marais
            ['slug' => 'kill-swamp-hydra-10', 'title' => 'Trancheur de têtes', 'description' => 'Tuer 10 hydres des marais', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'swamp_hydra', 'count' => 10], 'reward' => ['gils' => 500]],
            ['slug' => 'kill-swamp-hydra-50', 'title' => 'Chasseur d\'hydres', 'description' => 'Tuer 50 hydres des marais', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'swamp_hydra', 'count' => 50], 'reward' => ['gils' => 1500]],
            ['slug' => 'kill-swamp-hydra-100', 'title' => 'Exterminateur d\'hydres', 'description' => 'Tuer 100 hydres des marais', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'swamp_hydra', 'count' => 100], 'reward' => ['gils' => 3000, 'title' => 'Exterminateur d\'hydres']],
            // Forgeron abyssal
            ['slug' => 'kill-abyssal-blacksmith-10', 'title' => 'Briseur de forges', 'description' => 'Tuer 10 forgerons abyssaux', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'abyssal_blacksmith', 'count' => 10], 'reward' => ['gils' => 600]],
            ['slug' => 'kill-abyssal-blacksmith-50', 'title' => 'Chasseur de forgerons', 'description' => 'Tuer 50 forgerons abyssaux', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'abyssal_blacksmith', 'count' => 50], 'reward' => ['gils' => 1800]],
            ['slug' => 'kill-abyssal-blacksmith-100', 'title' => 'Exterminateur de forgerons', 'description' => 'Tuer 100 forgerons abyssaux', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'abyssal_blacksmith', 'count' => 100], 'reward' => ['gils' => 3500, 'title' => 'Exterminateur de forgerons']],

            // === BOSS DE ZONE (tâche 66) ===
            // Gardien de la Forêt
            ['slug' => 'kill-forest-guardian-1', 'title' => 'Gardien terrassé', 'description' => 'Vaincre le Gardien de la Forêt', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'forest_guardian', 'count' => 1], 'reward' => ['gils' => 1000, 'title' => 'Protecteur de la forêt']],
            // Seigneur de la Forge
            ['slug' => 'kill-forge-lord-1', 'title' => 'Seigneur de la forge vaincu', 'description' => 'Vaincre le Seigneur de la Forge', 'category' => 'combat', 'criteria' => ['type' => 'mob_kill', 'monster_slug' => 'forge_lord', 'count' => 1], 'reward' => ['gils' => 1500, 'title' => 'Maître de la forge']],

            // === EXPLORATION: Monstres différents découverts ===
            ['slug' => 'discover-5', 'title' => 'Explorateur novice', 'description' => 'Combattre 5 types de monstres différents', 'category' => 'exploration', 'criteria' => ['type' => 'monster_discovery', 'count' => 5], 'reward' => ['gils' => 100]],
            ['slug' => 'discover-10', 'title' => 'Explorateur confirmé', 'description' => 'Combattre 10 types de monstres différents', 'category' => 'exploration', 'criteria' => ['type' => 'monster_discovery', 'count' => 10], 'reward' => ['gils' => 300]],
            ['slug' => 'discover-20', 'title' => 'Explorateur expert', 'description' => 'Combattre 20 types de monstres différents', 'category' => 'exploration', 'criteria' => ['type' => 'monster_discovery', 'count' => 20], 'reward' => ['gils' => 1000, 'title' => 'Explorateur expert']],

            // === QUETES ===
            ['slug' => 'quest-5', 'title' => 'Aventurier débutant', 'description' => 'Compléter 5 quêtes', 'category' => 'quests', 'criteria' => ['type' => 'quest_complete', 'count' => 5], 'reward' => ['gils' => 100]],
            ['slug' => 'quest-10', 'title' => 'Aventurier confirmé', 'description' => 'Compléter 10 quêtes', 'category' => 'quests', 'criteria' => ['type' => 'quest_complete', 'count' => 10], 'reward' => ['gils' => 300]],
            ['slug' => 'quest-25', 'title' => 'Aventurier aguerri', 'description' => 'Compléter 25 quêtes', 'category' => 'quests', 'criteria' => ['type' => 'quest_complete', 'count' => 25], 'reward' => ['gils' => 800]],
            ['slug' => 'quest-50', 'title' => 'Héros légendaire', 'description' => 'Compléter 50 quêtes', 'category' => 'quests', 'criteria' => ['type' => 'quest_complete', 'count' => 50], 'reward' => ['gils' => 2000, 'title' => 'Héros légendaire']],

            // === DONJONS (tache 84) ===
            ['slug' => 'dungeon-clear-1', 'title' => 'Premier donjon', 'description' => 'Terminer un donjon', 'category' => 'combat', 'criteria' => ['type' => 'dungeon_clear', 'count' => 1], 'reward' => ['gils' => 500]],
            ['slug' => 'dungeon-clear-5', 'title' => 'Explorateur de donjons', 'description' => 'Terminer 5 donjons', 'category' => 'combat', 'criteria' => ['type' => 'dungeon_clear', 'count' => 5], 'reward' => ['gils' => 1500]],
            ['slug' => 'dungeon-clear-10', 'title' => 'Maître des profondeurs', 'description' => 'Terminer 10 donjons', 'category' => 'combat', 'criteria' => ['type' => 'dungeon_clear', 'count' => 10], 'reward' => ['gils' => 3000, 'title' => 'Maître des profondeurs']],
            ['slug' => 'dungeon-clear-mythic-1', 'title' => 'Défi mythique', 'description' => 'Terminer un donjon en difficulté Mythique', 'category' => 'combat', 'criteria' => ['type' => 'dungeon_clear_mythic', 'count' => 1], 'reward' => ['gils' => 2000, 'title' => 'Conquérant mythique']],

            // === RECOLTE ===
            ['slug' => 'gather-10', 'title' => 'Cueilleur du dimanche', 'description' => 'Récolter 10 ressources', 'category' => 'gathering', 'criteria' => ['type' => 'gathering', 'count' => 10], 'reward' => ['gils' => 50]],
            ['slug' => 'gather-50', 'title' => 'Récolteur assidu', 'description' => 'Récolter 50 ressources', 'category' => 'gathering', 'criteria' => ['type' => 'gathering', 'count' => 50], 'reward' => ['gils' => 200]],
            ['slug' => 'gather-200', 'title' => 'Maître récolteur', 'description' => 'Récolter 200 ressources', 'category' => 'gathering', 'criteria' => ['type' => 'gathering', 'count' => 200], 'reward' => ['gils' => 800, 'title' => 'Maître récolteur']],
            ['slug' => 'gather-500', 'title' => 'Pilleur de la nature', 'description' => 'Récolter 500 ressources', 'category' => 'gathering', 'criteria' => ['type' => 'gathering', 'count' => 500], 'reward' => ['gils' => 2000, 'title' => 'Pilleur de la nature']],

            // === CRAFT ===
            ['slug' => 'craft-5', 'title' => 'Apprenti artisan', 'description' => 'Fabriquer 5 objets', 'category' => 'craft', 'criteria' => ['type' => 'craft', 'count' => 5], 'reward' => ['gils' => 50]],
            ['slug' => 'craft-25', 'title' => 'Artisan confirmé', 'description' => 'Fabriquer 25 objets', 'category' => 'craft', 'criteria' => ['type' => 'craft', 'count' => 25], 'reward' => ['gils' => 300]],
            ['slug' => 'craft-100', 'title' => 'Maître artisan', 'description' => 'Fabriquer 100 objets', 'category' => 'craft', 'criteria' => ['type' => 'craft', 'count' => 100], 'reward' => ['gils' => 1000, 'title' => 'Maître artisan']],
            ['slug' => 'craft-250', 'title' => 'Légende de la forge', 'description' => 'Fabriquer 250 objets', 'category' => 'craft', 'criteria' => ['type' => 'craft', 'count' => 250], 'reward' => ['gils' => 3000, 'title' => 'Légende de la forge']],

            // === TRAME NARRATIVE ===
            ['slug' => 'acte3-convergence', 'title' => 'La Convergence', 'description' => 'Terminer la trame de l\'Acte 3 — vaincre le Gardien de la Convergence et decouvrir la verite du cristal d\'Amethyste', 'category' => 'quests', 'criteria' => ['type' => 'quest_complete', 'quest_slug' => 'quest_acte3_epilogue', 'count' => 1], 'reward' => ['gils' => 10000, 'title' => 'Heros de la Convergence']],

            // === SECRETS (hidden achievements) ===
            ['slug' => 'secret-first-death', 'title' => 'Première chute', 'description' => 'Mourir pour la première fois', 'category' => 'secrets', 'criteria' => ['type' => 'player_death', 'count' => 1], 'reward' => ['gils' => 25], 'hidden' => true],
            ['slug' => 'secret-death-10', 'title' => 'Habitué de l\'au-delà', 'description' => 'Mourir 10 fois', 'category' => 'secrets', 'criteria' => ['type' => 'player_death', 'count' => 10], 'reward' => ['gils' => 100], 'hidden' => true],
            ['slug' => 'secret-flee-1', 'title' => 'La fuite est un art', 'description' => 'Fuir un combat', 'category' => 'secrets', 'criteria' => ['type' => 'combat_flee', 'count' => 1], 'reward' => ['gils' => 50], 'hidden' => true],
            ['slug' => 'secret-flee-10', 'title' => 'Couard professionnel', 'description' => 'Fuir 10 combats', 'category' => 'secrets', 'criteria' => ['type' => 'combat_flee', 'count' => 10], 'reward' => ['gils' => 200, 'title' => 'Couard professionnel'], 'hidden' => true],
            ['slug' => 'secret-gather-1000', 'title' => 'L\'infatigable', 'description' => 'Récolter 1000 ressources', 'category' => 'secrets', 'criteria' => ['type' => 'gathering', 'count' => 1000], 'reward' => ['gils' => 5000, 'title' => 'L\'infatigable'], 'hidden' => true],
            ['slug' => 'secret-craft-500', 'title' => 'Forgeron des dieux', 'description' => 'Fabriquer 500 objets', 'category' => 'secrets', 'criteria' => ['type' => 'craft', 'count' => 500], 'reward' => ['gils' => 5000, 'title' => 'Forgeron des dieux'], 'hidden' => true],
            ['slug' => 'secret-quest-100', 'title' => 'L\'éternel aventurier', 'description' => 'Compléter 100 quêtes', 'category' => 'secrets', 'criteria' => ['type' => 'quest_complete', 'count' => 100], 'reward' => ['gils' => 5000, 'title' => 'L\'éternel aventurier'], 'hidden' => true],

            // === TUTORIEL ===
            ['slug' => 'tutorial-complete', 'title' => 'Premiers pas', 'description' => 'Terminer le tutoriel d\'introduction.', 'category' => 'exploration', 'criteria' => ['type' => 'tutorial_complete', 'count' => 1], 'reward' => ['gils' => 100]],
        ];

        foreach ($achievements as $data) {
            $achievement = new Achievement();
            $achievement->setSlug($data['slug']);
            $achievement->setTitle($data['title']);
            $achievement->setDescription($data['description']);
            $achievement->setCategory($data['category']);
            $achievement->setCriteria($data['criteria']);
            $achievement->setReward($data['reward']);
            $achievement->setHidden($data['hidden'] ?? false);
            $achievement->setCreatedAt(new \DateTime());
            $achievement->setUpdatedAt(new \DateTime());

            $manager->persist($achievement);
            $this->addReference('achievement_' . $data['slug'], $achievement);
        }

        $manager->flush();
    }
}
