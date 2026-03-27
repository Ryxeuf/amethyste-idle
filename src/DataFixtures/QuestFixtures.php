<?php

namespace App\DataFixtures;

use App\Entity\App\GameEvent;
use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class QuestFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Création des quêtes basées sur le contenu de pnj_quest.yaml
        $quests = [
            'quest_zombie_1' => [
                'name' => 'Sus aux zombies',
                'description' => 'Les zombies deviennent gênants dans la region, pourrais-tu m\'en débarrasser ? Je t\'apprendrai comment miner.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Zombie',
                            'slug' => 'zombie',
                            'count' => 2,
                        ],
                    ],
                ],
                'rewards' => [
                    'gold' => 1,
                    'items' => [
                        [
                            'item' => 3,
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'beer-pint',
                        ],
                        [
                            'item' => 10,
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'miner-domain-parchment',
                        ],
                    ],
                ],
            ],
            'quest_skeleton_1' => [
                'name' => 'Sus aux squelettes',
                'description' => 'Les squelettes deviennent gênants dans la region, pourrais-tu m\'en débarrasser ?',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Squelette',
                            'slug' => 'skeleton',
                            'count' => 2,
                        ],
                    ],
                ],
                'rewards' => [
                    'gold' => 1,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'beer-pint',
                        ],
                    ],
                ],
            ],
            'quest_taiju_1' => [
                'name' => 'Le Taiju menaçant',
                'description' => 'Un Taiju dangereux a été aperçu dans la forêt. Éliminez-le pour assurer la sécurité des villageois.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Taiju',
                            'slug' => 'taiju',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 50,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'liana-whip',
                        ],
                    ],
                ],
            ],
            'quest_mushroom_1' => [
                'name' => 'Cueillette de champignons',
                'description' => 'Récoltez 5 champignons pour l\'apothicaire du village.',
                'requirements' => [
                    'collect' => [
                        'mushroom' => 5,
                    ],
                ],
                'rewards' => [
                    'xp' => 50,
                    'gold' => 30,
                    'items' => [
                        'materia_soin' => 1,
                    ],
                ],
            ],
            'quest_goblin_1' => [
                'name' => 'Menace gobeline',
                'description' => 'Les gobelins pillent les fermes environnantes. Éliminez-en quelques-uns pour protéger les villageois.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Gobelin',
                            'slug' => 'goblin',
                            'count' => 3,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 75,
                    'gold' => 40,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'leather-boots',
                        ],
                    ],
                ],
            ],
            'quest_troll_1' => [
                'name' => 'Le troll du pont',
                'description' => 'Un troll a élu domicile sous le pont principal et empêche les marchands de passer. Débarrassez-vous de cette menace.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Troll',
                            'slug' => 'troll',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 120,
                    'gold' => 80,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'wooden-shield',
                        ],
                    ],
                ],
            ],
            'quest_werewolf_1' => [
                'name' => 'Hurlements nocturnes',
                'description' => 'Des hurlements terrifiants résonnent dans la forêt les nuits de pleine lune. Traquez et éliminez le loup-garou responsable.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Loup-garou',
                            'slug' => 'werewolf',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 150,
                    'gold' => 100,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'leather-armor',
                        ],
                    ],
                ],
            ],
            'quest_banshee_griffin_1' => [
                'name' => 'Créatures de la nuit',
                'description' => 'Des créatures mystérieuses terrorisent les voyageurs. Éliminez une banshee et un griffon pour sécuriser les routes.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Banshee',
                            'slug' => 'banshee',
                            'count' => 1,
                        ],
                        [
                            'name' => 'Griffon',
                            'slug' => 'griffin',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 150,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'magic-amulet',
                        ],
                    ],
                ],
            ],
            'quest_wood_collection' => [
                'name' => 'Bûcheron en herbe',
                'description' => 'Le menuisier du village a besoin de bois pour ses créations. Récoltez des bûches pour l\'aider.',
                'requirements' => [
                    'collect' => [
                        'wood_log' => 8,
                    ],
                ],
                'rewards' => [
                    'xp' => 60,
                    'gold' => 45,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 2,
                            'genericItemSlug' => 'life-potion',
                        ],
                    ],
                ],
            ],
            'quest_dragon_1' => [
                'name' => 'Le dragon de la montagne',
                'description' => 'Un dragon terrorise la région depuis sa tanière dans la montagne. Cette quête est extrêmement dangereuse, mais la récompense est à la hauteur du risque.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Dragon',
                            'slug' => 'dragon',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 500,
                    'gold' => 300,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'iron-sword',
                        ],
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'iron-armor',
                        ],
                    ],
                ],
            ],
            // --- Quete de livraison ---
            'quest_deliver_mushroom' => [
                'name' => 'Livraison de champignons',
                'description' => 'L\'herboriste a besoin de champignons frais pour ses remèdes. Récoltez-en et apportez-les-lui.',
                'requirements' => [
                    'deliver' => [
                        [
                            'item_slug' => 'mushroom',
                            'pnj_id' => 8,
                            'quantity' => 3,
                            'name' => 'Champignon',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 80,
                    'gold' => 40,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 2,
                            'genericItemSlug' => 'life-potion',
                        ],
                    ],
                ],
            ],
            // --- Quete d'exploration ---
            'quest_explore_forest' => [
                'name' => 'Cartographier la forêt',
                'description' => 'La cartographe du village a besoin que quelqu\'un explore la forêt pour compléter ses cartes. Rendez-vous aux coordonnées indiquées.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '15.20',
                            'name' => 'Clairière de la forêt',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 60,
                    'gold' => 35,
                ],
            ],
            // --- Quete a choix ---
            'quest_choice_alliance' => [
                'name' => 'Allégeance contestée',
                'description' => 'Vous avez découvert un convoi abandonné contenant des ressources précieuses. Le garde et le marchand du village vous demandent chacun de leur remettre. À vous de choisir.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '10.6',
                            'name' => 'Convoi abandonné',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 30,
                ],
                'choiceOutcome' => [
                    [
                        'key' => 'help_guard',
                        'label' => 'Remettre au garde',
                        'bonusRewards' => [
                            'gold' => 20,
                            'items' => [
                                ['genericItemSlug' => 'wooden-shield', 'count' => 1],
                            ],
                        ],
                    ],
                    [
                        'key' => 'help_merchant',
                        'label' => 'Remettre au marchand',
                        'bonusRewards' => [
                            'gold' => 80,
                            'items' => [
                                ['genericItemSlug' => 'life-potion', 'count' => 3],
                            ],
                        ],
                    ],
                ],
            ],
            // --- Quetes quotidiennes ---
            'daily_kill_slimes' => [
                'name' => 'Purge de slimes',
                'description' => 'Les slimes prolifèrent ! Éliminez-en quelques-uns pour garder les alentours propres.',
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Slime', 'slug' => 'slime', 'count' => 3],
                    ],
                ],
                'rewards' => [
                    'xp' => 30,
                    'gold' => 20,
                ],
                'isDaily' => true,
                'dailyPool' => 'combat',
            ],
            'daily_kill_bats' => [
                'name' => 'Chasse aux chauve-souris',
                'description' => 'Les chauve-souris gênent les mineurs dans les grottes. Chassez-en quelques-unes.',
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Chauve-souris', 'slug' => 'bat', 'count' => 3],
                    ],
                ],
                'rewards' => [
                    'xp' => 30,
                    'gold' => 20,
                ],
                'isDaily' => true,
                'dailyPool' => 'combat',
            ],
            'daily_kill_spiders' => [
                'name' => 'Toiles indésirables',
                'description' => 'Les araignées bloquent les sentiers forestiers. Nettoyez le passage.',
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Araignée', 'slug' => 'spider', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 35,
                    'gold' => 25,
                ],
                'isDaily' => true,
                'dailyPool' => 'combat',
            ],
            'daily_collect_herbs' => [
                'name' => 'Cueillette du jour',
                'description' => 'L\'herboriste a besoin de plantes fraîches pour ses potions quotidiennes.',
                'requirements' => [
                    'collect' => [
                        'plant-mint' => 3,
                    ],
                ],
                'rewards' => [
                    'xp' => 25,
                    'gold' => 15,
                ],
                'isDaily' => true,
                'dailyPool' => 'recolte',
            ],
            'daily_collect_ore' => [
                'name' => 'Minerai pour la forge',
                'description' => 'Le forgeron a toujours besoin de minerai de cuivre. Rapportez-en de la mine.',
                'requirements' => [
                    'collect' => [
                        'ore-copper' => 3,
                    ],
                ],
                'rewards' => [
                    'xp' => 25,
                    'gold' => 15,
                ],
                'isDaily' => true,
                'dailyPool' => 'recolte',
            ],
            'daily_kill_rats' => [
                'name' => 'Rats des champs',
                'description' => 'Les rats géants envahissent les réserves. Éliminez-en avant qu\'ils ne dévorent tout.',
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Rat géant', 'slug' => 'giant_rat', 'count' => 3],
                    ],
                ],
                'rewards' => [
                    'xp' => 30,
                    'gold' => 20,
                ],
                'isDaily' => true,
                'dailyPool' => 'combat',
            ],
            // --- Chaine de quetes : La Menace Rampante (3 quetes) ---
            'quest_chain_guard_1' => [
                'name' => 'La Menace Rampante - Partie 1',
                'description' => 'Le capitaine de la garde a remarqué une activité inhabituelle de gobelins près du village. Éliminez-en quelques-uns pour évaluer la menace.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Gobelin',
                            'slug' => 'goblin',
                            'count' => 2,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 50,
                    'gold' => 25,
                ],
                'prerequisiteQuests' => null,
            ],
            'quest_chain_guard_2' => [
                'name' => 'La Menace Rampante - Partie 2',
                'description' => 'Les gobelins étaient des éclaireurs ! Le capitaine vous envoie éliminer les squelettes qu\'ils ont réveillés dans les ruines voisines.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Squelette',
                            'slug' => 'skeleton',
                            'count' => 3,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 50,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 2,
                            'genericItemSlug' => 'life-potion',
                        ],
                    ],
                ],
                // prerequisiteQuests set after flush (needs ID of quest_chain_guard_1)
            ],
            'quest_chain_guard_3' => [
                'name' => 'La Menace Rampante - Partie 3',
                'description' => 'Le vrai meneur est un troll qui contrôlait les gobelins et les squelettes. Mettez fin à cette menace une bonne fois pour toutes !',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Troll',
                            'slug' => 'troll',
                            'count' => 1,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 100,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'wooden-shield',
                        ],
                    ],
                ],
                // prerequisiteQuests set after flush (needs ID of quest_chain_guard_2)
            ],
            // --- Chaîne narrative Acte 1 : L'Éveil (5 quêtes tutoriel) ---
            'quest_acte1_reveil' => [
                'name' => 'L\'Éveil — Réveil',
                'description' => 'Vous ouvrez les yeux dans un lieu inconnu, sans aucun souvenir. Une femme sage se tient devant vous. Explorez les alentours du village pour retrouver vos esprits.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '80.34',
                            'name' => 'Place du village',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 20,
                    'gold' => 10,
                ],
                'prerequisiteQuests' => null,
            ],
            'quest_acte1_premiers_pas' => [
                'name' => 'L\'Éveil — Premiers pas',
                'description' => 'Claire vous conseille d\'aller voir Gérard le Forgeron pour vous équiper. Rendez-vous à sa forge et recevez votre première arme.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '1.5',
                            'name' => 'Forge de Gérard',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 30,
                    'gold' => 15,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'short-sword',
                        ],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte1_bapteme_du_feu' => [
                'name' => 'L\'Éveil — Baptême du feu',
                'description' => 'Gérard vous a remis une épée. Il est temps de prouver votre valeur ! Éliminez deux slimes qui rôdent aux abords du village.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Slime',
                            'slug' => 'slime',
                            'count' => 2,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 40,
                    'gold' => 20,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 2,
                            'genericItemSlug' => 'life-potion',
                        ],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte1_recolte' => [
                'name' => 'L\'Éveil — Récolte',
                'description' => 'Marie la Herboriste a besoin de champignons pour préparer des remèdes. Récoltez-en dans les environs et rapportez-les-lui.',
                'requirements' => [
                    'collect' => [
                        'mushroom' => 3,
                    ],
                ],
                'rewards' => [
                    'xp' => 35,
                    'gold' => 25,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'herbalist-domain-parchment',
                        ],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte1_cristal' => [
                'name' => 'L\'Éveil — Le Cristal d\'Améthyste',
                'description' => 'Claire la Sage vous parle d\'un cristal mystérieux caché dans une clairière au sud. Trouvez-le : il pourrait détenir la clé de vos souvenirs perdus.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '85.50',
                            'name' => 'Clairière du Cristal',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 60,
                    'gold' => 30,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 1,
                            'genericItemSlug' => 'm1-life',
                        ],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            // --- Quetes cachees (decouverte) ---
            'quest_hidden_secret_clearing' => [
                'name' => 'Le secret de la clairiere',
                'description' => 'En explorant une clairiere isolee, vous decouvrez des traces anciennes au sol. Quelque chose est enterre ici... Explorez les alentours pour trouver d\'autres indices.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 1,
                            'coordinates' => '25.40',
                            'name' => 'Pierre gravee',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 80,
                    'gold' => 50,
                ],
                'isHidden' => true,
                'triggerCondition' => [
                    'type' => 'explore',
                    'map_id' => 1,
                    'coordinates' => '20.38',
                ],
            ],
            'quest_hidden_rare_slime' => [
                'name' => 'La gelee doree',
                'description' => 'En eliminant une gelee, vous remarquez un etrange eclat dore dans ses restes. Les villageois parlent d\'une gelee rare au coeur brillant. Eliminez-en davantage pour la trouver.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Gelée',
                            'slug' => 'slime',
                            'count' => 5,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 100,
                    'gold' => 75,
                    'items' => [
                        [
                            'type' => 'stuff',
                            'count' => 3,
                            'genericItemSlug' => 'life-potion',
                        ],
                    ],
                ],
                'isHidden' => true,
                'triggerCondition' => [
                    'type' => 'kill',
                    'monster_slug' => 'slime',
                ],
            ],
            'quest_hidden_herb_lore' => [
                'name' => 'Savoir ancestral',
                'description' => 'En recoltant des champignons, vous trouvez un vieux parchemin cachant une recette oubliee. Recoltez d\'autres ingredients pour la reconstituer.',
                'requirements' => [
                    'collect' => [
                        'mushroom' => 8,
                    ],
                ],
                'rewards' => [
                    'xp' => 70,
                    'gold' => 40,
                    'items' => [
                        'materia_soin' => 1,
                    ],
                ],
                'isHidden' => true,
                'triggerCondition' => [
                    'type' => 'harvest',
                    'item_slug' => 'mushroom',
                ],
            ],
            'quest_hidden_goblin_cache' => [
                'name' => 'La planque des gobelins',
                'description' => 'Sur le cadavre d\'un gobelin, vous trouvez une carte menant a une cache secrete. Eliminez d\'autres gobelins pour reunir les morceaux de la carte.',
                'requirements' => [
                    'monsters' => [
                        [
                            'name' => 'Gobelin',
                            'slug' => 'goblin',
                            'count' => 4,
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 120,
                    'gold' => 100,
                    'items' => [
                        [
                            'type' => 'gear',
                            'count' => 1,
                            'genericItemSlug' => 'leather-boots',
                        ],
                    ],
                ],
                'isHidden' => true,
                'triggerCondition' => [
                    'type' => 'kill',
                    'monster_slug' => 'goblin',
                ],
            ],
            // --- Chaîne narrative Acte 2 : Fragment Forêt (4 quêtes) ---
            'quest_acte2_foret_murmures' => [
                'name' => 'Les Fragments — Les Murmures s\'intensifient',
                'description' => 'Depuis que vous avez touché le Cristal d\'Améthyste, vous percevez des échos étranges venant de la Forêt des murmures. Thadeus l\'Ermite, qui vit au nord de la forêt, pourrait avoir des réponses.',
                'requirements' => [
                    'talk_to' => [
                        ['pnj_id' => 0, 'name' => 'Thadeus l\'Ermite'],
                    ],
                ],
                'rewards' => [
                    'xp' => 80,
                    'gold' => 40,
                ],
                // prerequisiteQuests set after flush (needs quest_acte1_cristal ID)
            ],
            'quest_acte2_foret_purification' => [
                'name' => 'Les Fragments — Purifier la Corruption',
                'description' => 'Thadeus a senti une corruption ancienne se réveiller dans la forêt. Des créatures corrompues rôdent près de l\'Arbre-Mère. Éliminez-les pour affaiblir la corruption.',
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Ondine', 'slug' => 'forest_undine', 'count' => 2],
                        ['name' => 'Ochu', 'slug' => 'forest_ochu', 'count' => 2],
                        ['name' => 'Feu follet', 'slug' => 'will_o_wisp', 'count' => 1],
                    ],
                ],
                'rewards' => [
                    'xp' => 150,
                    'gold' => 80,
                    'items' => [
                        ['type' => 'stuff', 'count' => 3, 'genericItemSlug' => 'life-potion'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_foret_remede' => [
                'name' => 'Les Fragments — Le Remède Ancestral',
                'description' => 'La corruption a été affaiblie, mais l\'Arbre-Mère reste malade. Thadeus a besoin de sauge et de mandragore pour préparer un remède ancestral. Récoltez-les et apportez-les à Elara l\'Herboriste qui saura les préparer.',
                'requirements' => [
                    'collect' => [
                        'plant-sage' => 3,
                        'plant-mandrake' => 2,
                    ],
                ],
                'rewards' => [
                    'xp' => 120,
                    'gold' => 60,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'antidote'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_foret_fragment' => [
                'name' => 'Les Fragments — Le Fragment Sylvestre',
                'description' => 'Le remède a guéri l\'Arbre-Mère. En remerciement, ses racines ont révélé un éclat de cristal vert enfoui depuis des siècles. Rendez-vous au cœur de la forêt pour le récupérer.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 3,
                            'coordinates' => '30.15',
                            'name' => 'Racines de l\'Arbre-Mère',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 100,
                    'items' => [
                        ['type' => 'quest', 'count' => 1, 'genericItemSlug' => 'quest-fragment-foret'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            // --- Chaîne narrative Acte 2 : Fragment Mines (4 quêtes) ---
            'quest_acte2_mines_tremblements' => [
                'name' => 'Les Fragments — Tremblements souterrains',
                'description' => 'Depuis votre contact avec le Cristal d\'Améthyste, vous percevez des vibrations sourdes venant des Mines profondes. Grimmur le Contremaître, posté à l\'entrée, pourrait en savoir plus.',
                'requirements' => [
                    'talk_to' => [
                        ['pnj_id' => 0, 'name' => 'Grimmur le Contremaître'],
                    ],
                ],
                'rewards' => [
                    'xp' => 80,
                    'gold' => 40,
                ],
                // prerequisiteQuests set after flush (needs quest_acte1_cristal ID)
            ],
            'quest_acte2_mines_minerai' => [
                'name' => 'Les Fragments — Le Minerai Ancien',
                'description' => 'Grimmur a senti une énergie étrange émaner des filons profonds. Il vous demande de récolter du minerai de fer et de l\'or enfoui pour analyser la source de ces vibrations.',
                'requirements' => [
                    'collect' => [
                        'ore-iron' => 5,
                        'ore-gold' => 3,
                    ],
                ],
                'rewards' => [
                    'xp' => 150,
                    'gold' => 80,
                    'items' => [
                        ['type' => 'stuff', 'count' => 3, 'genericItemSlug' => 'healing-potion-small'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_mines_forge' => [
                'name' => 'Les Fragments — Le Seigneur de la Forge',
                'description' => 'L\'énergie provient des profondeurs, là où règne le Seigneur de la Forge. Ce gardien devenu fou protège quelque chose d\'ancien. Vous devez le vaincre pour atteindre la source des vibrations.',
                'requirements' => [
                    'boss_challenge' => [
                        [
                            'monster_slug' => 'forge_lord',
                            'name' => 'Seigneur de la Forge',
                            'conditions' => [
                                'solo' => true,
                            ],
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 120,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'life-potion'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_mines_fragment' => [
                'name' => 'Les Fragments — Le Fragment de la Forge',
                'description' => 'La défaite du Seigneur de la Forge a révélé une fissure dans le mur de sa salle. Un éclat de cristal orangé pulse au fond, irradiant une chaleur ancienne. Récupérez-le.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 4,
                            'coordinates' => '55.5',
                            'name' => 'Salle secrète de la Forge',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 100,
                    'items' => [
                        ['type' => 'quest', 'count' => 1, 'genericItemSlug' => 'quest-fragment-mines'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            // --- Chaîne narrative Acte 2 : Fragment Marais (4 quêtes) ---
            'quest_acte2_marais_brumes' => [
                'name' => 'Les Fragments — Brumes inquiétantes',
                'description' => 'Depuis votre contact avec le Cristal d\'Améthyste, vous percevez des murmures portés par le brouillard du Marais Brumeux. Morwenna la Voyante, à l\'entrée du marais, semble vous attendre.',
                'requirements' => [
                    'talk_to' => [
                        ['pnj_id' => 0, 'name' => 'Morwenna la Voyante'],
                    ],
                ],
                'rewards' => [
                    'xp' => 80,
                    'gold' => 40,
                ],
                // prerequisiteQuests set after flush (needs quest_acte1_cristal ID)
            ],
            'quest_acte2_marais_creatures' => [
                'name' => 'Les Fragments — Purger les brumes',
                'description' => 'Morwenna a senti une corruption ancienne émaner du coeur du marais. Des créatures hostiles s\'y sont rassemblées, nourries par une énergie mystérieuse. Éliminez-les pour affaiblir la brume.',
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Banshee', 'slug' => 'banshee', 'count' => 3],
                        ['name' => 'Araignée', 'slug' => 'spider', 'count' => 3],
                        ['name' => 'Ochu', 'slug' => 'ochu', 'count' => 2],
                    ],
                ],
                'rewards' => [
                    'xp' => 150,
                    'gold' => 80,
                    'items' => [
                        ['type' => 'stuff', 'count' => 3, 'genericItemSlug' => 'antidote'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_marais_livraison' => [
                'name' => 'Les Fragments — L\'Offrande au marais',
                'description' => 'Morwenna a besoin d\'ingrédients rares pour accomplir un rituel de divination et localiser la source de l\'énergie. Récoltez de la sauge et de la mandragore et apportez-les-lui.',
                'requirements' => [
                    'collect' => [
                        'plant-sage' => 4,
                        'plant-mandrake' => 3,
                    ],
                ],
                'rewards' => [
                    'xp' => 120,
                    'gold' => 60,
                    'items' => [
                        ['type' => 'stuff', 'count' => 2, 'genericItemSlug' => 'life-potion'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            'quest_acte2_marais_fragment' => [
                'name' => 'Les Fragments — Le Fragment des Brumes',
                'description' => 'Le rituel de Morwenna a révélé l\'emplacement d\'un éclat de cristal bleuté, enfoui sous l\'îlot central du marais. Rendez-vous là-bas pour le récupérer avant que les brumes ne le recouvrent à jamais.',
                'requirements' => [
                    'explore' => [
                        [
                            'map_id' => 6,
                            'coordinates' => '25.25',
                            'name' => 'Îlot central du marais',
                        ],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 100,
                    'items' => [
                        ['type' => 'quest', 'count' => 1, 'genericItemSlug' => 'quest-fragment-marais'],
                    ],
                ],
                // prerequisiteQuests set after flush
            ],
            // --- Quêtes avancées : enquête et défi boss ---
            'quest_enquete_herboriste' => [
                'name' => 'L\'Herboriste disparue',
                'description' => 'Marie la Herboriste a disparu. Interrogez Claire la Sage, Antoine le Mage et Élise la Guérisseuse pour retrouver sa trace.',
                'requirements' => [
                    'talk_to' => [
                        ['pnj_id' => 16, 'name' => 'Claire la Sage'],
                        ['pnj_id' => 19, 'name' => 'Antoine le Mage'],
                        ['pnj_id' => 2, 'name' => 'Élise la Guérisseuse'],
                    ],
                ],
                'rewards' => [
                    'gold' => 80,
                    'xp' => 120,
                    'items' => [
                        ['genericItemSlug' => 'life-potion', 'count' => 3],
                    ],
                ],
            ],
            'quest_defi_gardien_foret' => [
                'name' => 'Défi du Gardien',
                'description' => 'Prouvez votre valeur en vainquant le Gardien de la Forêt en solo, sans utiliser de soin et en moins de 5 minutes.',
                'requirements' => [
                    'boss_challenge' => [
                        [
                            'monster_slug' => 'forest_guardian',
                            'name' => 'Gardien de la Forêt',
                            'conditions' => [
                                'no_heal' => true,
                                'solo' => true,
                                'time_limit' => 300,
                            ],
                        ],
                    ],
                ],
                'rewards' => [
                    'gold' => 200,
                    'xp' => 300,
                    'items' => [
                        ['genericItemSlug' => 'life-potion', 'count' => 5],
                    ],
                ],
            ],
            // --- Quêtes d'événement ---
            'quest_event_lunar_hunt' => [
                'name' => 'Chasse sous la Lune',
                'description' => 'Pendant le Festival de la Lune, les creatures nocturnes sont plus agitees. Eliminez des monstres pour gagner une recompense exclusive du festival.',
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Zombie', 'slug' => 'zombie', 'count' => 3],
                        ['name' => 'Squelette', 'slug' => 'skeleton', 'count' => 3],
                    ],
                ],
                'rewards' => [
                    'xp' => 150,
                    'gold' => 75,
                    'items' => [
                        ['genericItemSlug' => 'cosmetic-lunar-crown', 'count' => 1],
                    ],
                ],
                'gameEvent' => 'event_festival_lune',
            ],
            'quest_event_shadow_purge' => [
                'name' => 'Purge des Ombres',
                'description' => 'La Nuit des Ombres attire des creatures malfaisantes. Repoussez-les et reclamez votre recompense.',
                'requirements' => [
                    'monsters' => [
                        ['name' => 'Gobelin', 'slug' => 'goblin', 'count' => 4],
                        ['name' => 'Troll', 'slug' => 'troll', 'count' => 1],
                    ],
                ],
                'rewards' => [
                    'xp' => 200,
                    'gold' => 100,
                    'items' => [
                        ['genericItemSlug' => 'cosmetic-shadow-cloak', 'count' => 1],
                    ],
                ],
                'gameEvent' => 'event_nuit_ombres',
            ],
        ];

        foreach ($quests as $key => $data) {
            $quest = new Quest();
            $quest->setName($data['name']);
            $quest->setDescription($data['description']);
            $quest->setRequirements($data['requirements']);
            $quest->setRewards($data['rewards']);
            if (isset($data['prerequisiteQuests'])) {
                $quest->setPrerequisiteQuests($data['prerequisiteQuests']);
            }
            if (isset($data['choiceOutcome'])) {
                $quest->setChoiceOutcome($data['choiceOutcome']);
            }
            if (isset($data['isDaily'])) {
                $quest->setIsDaily($data['isDaily']);
            }
            if (isset($data['dailyPool'])) {
                $quest->setDailyPool($data['dailyPool']);
            }
            if (isset($data['isHidden'])) {
                $quest->setIsHidden($data['isHidden']);
            }
            if (isset($data['triggerCondition'])) {
                $quest->setTriggerCondition($data['triggerCondition']);
            }
            if (isset($data['gameEvent'])) {
                $quest->setGameEvent($this->getReference($data['gameEvent'], GameEvent::class));
            }
            $quest->setCreatedAt(new \DateTime());
            $quest->setUpdatedAt(new \DateTime());

            $manager->persist($quest);
            $this->addReference($key, $quest);
        }

        $manager->flush();

        // Set prerequisite quest IDs (needs IDs from flush)
        /** @var Quest $chainGuard1 */
        $chainGuard1 = $this->getReference('quest_chain_guard_1', Quest::class);
        /** @var Quest $chainGuard2 */
        $chainGuard2 = $this->getReference('quest_chain_guard_2', Quest::class);
        /** @var Quest $chainGuard3 */
        $chainGuard3 = $this->getReference('quest_chain_guard_3', Quest::class);

        $chainGuard2->setPrerequisiteQuests([$chainGuard1->getId()]);
        $chainGuard3->setPrerequisiteQuests([$chainGuard2->getId()]);

        // Chaîne Acte 1 : L'Éveil (5 quêtes séquentielles)
        /** @var Quest $acte1Reveil */
        $acte1Reveil = $this->getReference('quest_acte1_reveil', Quest::class);
        /** @var Quest $acte1PremiersPas */
        $acte1PremiersPas = $this->getReference('quest_acte1_premiers_pas', Quest::class);
        /** @var Quest $acte1Bapteme */
        $acte1Bapteme = $this->getReference('quest_acte1_bapteme_du_feu', Quest::class);
        /** @var Quest $acte1Recolte */
        $acte1Recolte = $this->getReference('quest_acte1_recolte', Quest::class);
        /** @var Quest $acte1Cristal */
        $acte1Cristal = $this->getReference('quest_acte1_cristal', Quest::class);

        $acte1PremiersPas->setPrerequisiteQuests([$acte1Reveil->getId()]);
        $acte1Bapteme->setPrerequisiteQuests([$acte1PremiersPas->getId()]);
        $acte1Recolte->setPrerequisiteQuests([$acte1Bapteme->getId()]);
        $acte1Cristal->setPrerequisiteQuests([$acte1Recolte->getId()]);

        // Chaîne Acte 2 : Fragment Forêt (4 quêtes séquentielles, après Acte 1)
        /** @var Quest $acte2ForetMurmures */
        $acte2ForetMurmures = $this->getReference('quest_acte2_foret_murmures', Quest::class);
        /** @var Quest $acte2ForetPurification */
        $acte2ForetPurification = $this->getReference('quest_acte2_foret_purification', Quest::class);
        /** @var Quest $acte2ForetRemede */
        $acte2ForetRemede = $this->getReference('quest_acte2_foret_remede', Quest::class);
        /** @var Quest $acte2ForetFragment */
        $acte2ForetFragment = $this->getReference('quest_acte2_foret_fragment', Quest::class);

        $acte2ForetMurmures->setPrerequisiteQuests([$acte1Cristal->getId()]);
        $acte2ForetPurification->setPrerequisiteQuests([$acte2ForetMurmures->getId()]);
        $acte2ForetRemede->setPrerequisiteQuests([$acte2ForetPurification->getId()]);
        $acte2ForetFragment->setPrerequisiteQuests([$acte2ForetRemede->getId()]);

        // Chaîne Acte 2 : Fragment Mines (4 quêtes séquentielles, après Acte 1)
        /** @var Quest $acte2MinesTremblements */
        $acte2MinesTremblements = $this->getReference('quest_acte2_mines_tremblements', Quest::class);
        /** @var Quest $acte2MinesMinerai */
        $acte2MinesMinerai = $this->getReference('quest_acte2_mines_minerai', Quest::class);
        /** @var Quest $acte2MinesForge */
        $acte2MinesForge = $this->getReference('quest_acte2_mines_forge', Quest::class);
        /** @var Quest $acte2MinesFragment */
        $acte2MinesFragment = $this->getReference('quest_acte2_mines_fragment', Quest::class);

        $acte2MinesTremblements->setPrerequisiteQuests([$acte1Cristal->getId()]);
        $acte2MinesMinerai->setPrerequisiteQuests([$acte2MinesTremblements->getId()]);
        $acte2MinesForge->setPrerequisiteQuests([$acte2MinesMinerai->getId()]);
        $acte2MinesFragment->setPrerequisiteQuests([$acte2MinesForge->getId()]);

        // Chaîne Acte 2 : Fragment Marais (4 quêtes séquentielles, après Acte 1)
        /** @var Quest $acte2MaraisBrumes */
        $acte2MaraisBrumes = $this->getReference('quest_acte2_marais_brumes', Quest::class);
        /** @var Quest $acte2MaraisCreatures */
        $acte2MaraisCreatures = $this->getReference('quest_acte2_marais_creatures', Quest::class);
        /** @var Quest $acte2MaraisLivraison */
        $acte2MaraisLivraison = $this->getReference('quest_acte2_marais_livraison', Quest::class);
        /** @var Quest $acte2MaraisFragment */
        $acte2MaraisFragment = $this->getReference('quest_acte2_marais_fragment', Quest::class);

        $acte2MaraisBrumes->setPrerequisiteQuests([$acte1Cristal->getId()]);
        $acte2MaraisCreatures->setPrerequisiteQuests([$acte2MaraisBrumes->getId()]);
        $acte2MaraisLivraison->setPrerequisiteQuests([$acte2MaraisCreatures->getId()]);
        $acte2MaraisFragment->setPrerequisiteQuests([$acte2MaraisLivraison->getId()]);

        // Fix PNJ ID in talk_to requirements (needs PnjFixtures loaded first)
        /** @var Pnj $thadeus */
        $thadeus = $this->getReference('forest_pnj_2', Pnj::class);
        $requirements = $acte2ForetMurmures->getRequirements();
        $requirements['talk_to'][0]['pnj_id'] = $thadeus->getId();
        $acte2ForetMurmures->setRequirements($requirements);

        /** @var Pnj $grimmur */
        $grimmur = $this->getReference('mines_pnj_0', Pnj::class);
        $requirementsMines = $acte2MinesTremblements->getRequirements();
        $requirementsMines['talk_to'][0]['pnj_id'] = $grimmur->getId();
        $acte2MinesTremblements->setRequirements($requirementsMines);

        /** @var Pnj $morwenna */
        $morwenna = $this->getReference('marais_pnj_0', Pnj::class);
        $requirementsMarais = $acte2MaraisBrumes->getRequirements();
        $requirementsMarais['talk_to'][0]['pnj_id'] = $morwenna->getId();
        $acte2MaraisBrumes->setRequirements($requirementsMarais);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            GameEventFixtures::class,
            ForestPnjFixtures::class,
            MinesPnjFixtures::class,
            MaraisPnjFixtures::class,
        ];
    }
}
