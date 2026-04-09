<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Pnj;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * PNJs du Village de Lumière — hub central entre les zones (zone safe).
 *
 * 7 PNJ : forgeron, alchimiste, marchand général, maître des quêtes, banquier, garde, guide tutoriel.
 * Chaque marchand a sa boutique et ses horaires.
 */
class VillageHubPnjFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $village = $this->getReference('map_2', Map::class);

        $pnjs = $this->getVillagePnjs();

        foreach ($pnjs as $index => $data) {
            $pnj = new Pnj();
            $pnj->setName($data['name']);
            $pnj->setLife(10);
            $pnj->setMaxLife(10);
            $pnj->setMap($village);
            $pnj->setCoordinates($data['coordinates']);
            $pnj->setClassType($data['classType']);

            if (isset($data['portrait'])) {
                $pnj->setPortrait($data['portrait']);
            }

            if (isset($data['shopItems'])) {
                $pnj->setShopItems($data['shopItems']);
            }
            if (isset($data['opensAt'])) {
                $pnj->setOpensAt($data['opensAt']);
            }
            if (isset($data['closesAt'])) {
                $pnj->setClosesAt($data['closesAt']);
            }
            if (isset($data['shopStock'])) {
                $pnj->setShopStock($data['shopStock']);
            }

            $pnj->setDialog($data['dialog']);
            $pnj->setCreatedAt(new \DateTime());
            $pnj->setUpdatedAt(new \DateTime());

            $manager->persist($pnj);
            $this->addReference('village_pnj_' . $index, $pnj);
        }

        $manager->flush();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getVillagePnjs(): array
    {
        return [
            // 0 — Aldric le Forgeron (NW, près du bâtiment 4,4)
            [
                'name' => 'Aldric le Forgeron',
                'coordinates' => '7.7',
                'classType' => 'blacksmith',
                'portrait' => '/styles/images/portraits/blacksmith.png',
                'shopItems' => ['short-sword', 'long-sword', 'iron-sword', 'wooden-shield', 'leather-armor', 'leather-boots', 'leather-hat', 'leather-helmet'],
                'opensAt' => 7,
                'closesAt' => 20,
                'shopStock' => [
                    'short-sword' => ['stock' => 5, 'maxStock' => 5, 'restockInterval' => 3600],
                    'long-sword' => ['stock' => 3, 'maxStock' => 3, 'restockInterval' => 7200],
                    'iron-sword' => ['stock' => 2, 'maxStock' => 2, 'restockInterval' => 7200],
                    'wooden-shield' => ['stock' => 4, 'maxStock' => 4, 'restockInterval' => 3600],
                    'leather-armor' => ['stock' => 4, 'maxStock' => 4, 'restockInterval' => 3600],
                    'leather-boots' => ['stock' => 5, 'maxStock' => 5, 'restockInterval' => 3600],
                    'leather-hat' => ['stock' => 5, 'maxStock' => 5, 'restockInterval' => 3600],
                    'leather-helmet' => ['stock' => 3, 'maxStock' => 3, 'restockInterval' => 7200],
                ],
                'dialog' => [
                    [
                        'text' => 'Bienvenue dans ma forge, voyageur ! Le Village de Lumière est réputé pour la qualité de ses armes. Que puis-je faire pour vous ?',
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Parlez-moi du village',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Au revoir',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'Le Village de Lumière est le cœur de cette région. Fondé il y a des siècles, il sert de refuge aux aventuriers entre deux expéditions. Vous y trouverez tout ce dont vous avez besoin : armes, potions, et conseils.',
                        'choices' => [
                            [
                                'text' => 'Merci pour ces informations',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],
            // 1 — Iris l'Alchimiste (NE, près du bâtiment 33,4)
            [
                'name' => 'Iris l\'Alchimiste',
                'coordinates' => '33.8',
                'classType' => 'mage',
                'portrait' => '/styles/images/portraits/herbalist.png',
                'shopItems' => ['life-potion', 'healing-potion-small', 'healing-potion-medium', 'healing-potion-major', 'antidote', 'energy-potion-small', 'crafted-potion-base'],
                'opensAt' => 6,
                'closesAt' => 22,
                'shopStock' => [
                    'life-potion' => ['stock' => 20, 'maxStock' => 20, 'restockInterval' => 1800],
                    'healing-potion-small' => ['stock' => 15, 'maxStock' => 15, 'restockInterval' => 1800],
                    'healing-potion-medium' => ['stock' => 10, 'maxStock' => 10, 'restockInterval' => 3600],
                    'healing-potion-major' => ['stock' => 5, 'maxStock' => 5, 'restockInterval' => 7200],
                    'antidote' => ['stock' => 10, 'maxStock' => 10, 'restockInterval' => 3600],
                    'energy-potion-small' => ['stock' => 8, 'maxStock' => 8, 'restockInterval' => 3600],
                ],
                'dialog' => [
                    [
                        'text' => 'Ah, un visiteur ! Entrez, entrez. Mon laboratoire déborde de potions et d\'élixirs. Quelque chose vous intéresse ?',
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Quels conseils pour un aventurier ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Au revoir',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'Emportez toujours des potions de soin avec vous. Les monstres dans les environs sont dangereux, et une bonne préparation peut faire la différence entre la vie et la mort. Et n\'oubliez pas les antidotes — certaines créatures sont venimeuses !',
                        'choices' => [
                            [
                                'text' => 'Bien noté, merci',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],
            // 2 — Marcellin le Marchand (W, près du bâtiment 4,20)
            [
                'name' => 'Marcellin le Marchand',
                'coordinates' => '7.23',
                'classType' => 'merchant',
                'portrait' => '/styles/images/portraits/merchant.png',
                'shopItems' => ['pickaxe', 'fishing-rod', 'bread', 'grilled-meat', 'stew', 'mushroom', 'beer-pint', 'scroll-teleport'],
                'opensAt' => 8,
                'closesAt' => 21,
                'shopStock' => [
                    'scroll-teleport' => ['stock' => 5, 'maxStock' => 5, 'restockInterval' => 7200],
                    'grilled-meat' => ['stock' => 10, 'maxStock' => 10, 'restockInterval' => 1800],
                    'stew' => ['stock' => 8, 'maxStock' => 8, 'restockInterval' => 1800],
                ],
                'dialog' => [
                    [
                        'text' => 'Holà, aventurier ! Marcellin, marchand général du Village de Lumière, pour vous servir. J\'ai de tout : outils, nourriture, parchemins... Faites votre choix !',
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Qu\'est-ce qui se vend bien en ce moment ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Au revoir',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'Les parchemins de téléportation se vendent comme des petits pains ! Avec les monstres qui rôdent, les aventuriers préfèrent pouvoir rentrer rapidement au village. Et la nourriture, bien sûr — on ne combat pas le ventre vide.',
                        'choices' => [
                            [
                                'text' => 'Intéressant, merci',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],
            // 3 — Oriane la Maîtresse des Quêtes (E, près du bâtiment 33,20)
            [
                'name' => 'Oriane la Maîtresse des Quêtes',
                'coordinates' => '33.23',
                'classType' => 'noble',
                'portrait' => '/styles/images/portraits/sage.png',
                'dialog' => [
                    [
                        'text' => 'Bienvenue au tableau des quêtes du Village de Lumière, {{player_name}}. Je coordonne les missions pour les aventuriers de la région. Consultez régulièrement votre journal de quêtes — de nouvelles missions sont ajoutées fréquemment.',
                        'choices' => [
                            [
                                'text' => 'Des conseils pour progresser ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Au revoir',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'Explorez les environs, combattez les monstres, et accomplissez les quêtes des habitants. Chaque mission accomplie renforce votre réputation et vous rapproche de la vérité sur les mystères de cette terre. N\'hésitez pas à parler à tous les PNJ que vous croisez — certains ont des tâches à confier.',
                        'choices' => [
                            [
                                'text' => 'Compris, je vais explorer',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],
            // 4 — Théodore le Banquier (N, près du bâtiment 18,4)
            [
                'name' => 'Théodore le Banquier',
                'coordinates' => '20.8',
                'classType' => 'noble',
                'portrait' => '/styles/images/portraits/knight.png',
                'dialog' => [
                    [
                        'text' => 'Bonjour, {{player_name}}. La Banque de Lumière est à votre service. Votre coffre personnel est en sécurité ici. Utilisez votre inventaire pour accéder à la banque et y déposer vos objets précieux.',
                        'choices' => [
                            [
                                'text' => 'Comment fonctionne la banque ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Merci, au revoir',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'Ouvrez votre inventaire et sélectionnez l\'onglet « Banque ». Vous pourrez y stocker armes, armures, matériaux et objets précieux. Un aventurier avisé ne se promène pas avec tous ses trésors — le risque de tout perdre est trop grand.',
                        'choices' => [
                            [
                                'text' => 'Bien compris, merci',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],
            // 5 — Gareth le Garde (près de l'entrée sud, 20,35)
            [
                'name' => 'Gareth le Garde',
                'coordinates' => '20.35',
                'classType' => 'guard',
                'portrait' => '/styles/images/portraits/guard.png',
                'dialog' => [
                    [
                        'text' => 'Halte ! Bienvenue au Village de Lumière, {{player_name}}. Ce village est une zone sûre — aucun monstre ne peut y pénétrer. Reposez-vous ici avant de repartir à l\'aventure.',
                        'choices' => [
                            [
                                'text' => 'Que peut-on trouver dans ce village ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Merci, bonne garde',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'Le forgeron Aldric se trouve au nord-ouest, l\'alchimiste Iris au nord-est. Le marchand Marcellin est à l\'ouest, et la maîtresse des quêtes Oriane à l\'est. Le banquier Théodore est au nord, près de la place centrale. Bonne exploration !',
                        'choices' => [
                            [
                                'text' => 'Merci pour les directions',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],
            // 6 — Lyra la Guide (près du spawn joueur, 20,18)
            [
                'name' => 'Lyra la Guide',
                'coordinates' => '20.18',
                'classType' => 'healer',
                'portrait' => '/styles/images/portraits/sage.png',
                'dialog' => $this->getTutorialGuideDialog(),
            ],
        ];
    }

    /**
     * Contextual dialog for the tutorial guide NPC.
     * Uses conditional_next to branch based on the player's current tutorial step.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTutorialGuideDialog(): array
    {
        return [
            // 0 — Branching hub: routes to the right sentence based on tutorial step
            [
                'text' => '',
                'conditional_next' => [
                    ['next' => 1, 'next_condition' => ['tutorial_step' => [0]]],
                    ['next' => 2, 'next_condition' => ['tutorial_step' => [1]]],
                    ['next' => 3, 'next_condition' => ['tutorial_step' => [2]]],
                    ['next' => 4, 'next_condition' => ['tutorial_step' => [3]]],
                    ['next' => 5, 'next_condition' => ['tutorial_step' => [4]]],
                    ['next' => 6, 'next_condition' => ['tutorial_completed' => true]],
                    ['next' => 6],
                ],
            ],
            // 1 — Step Movement
            [
                'text' => 'Bienvenue dans le Village de Lumière, {{player_name}} ! Je suis Lyra, et je suis là pour vous guider dans vos premiers pas. Commencez par vous déplacer : cliquez sur une case adjacente sur la carte pour bouger.',
                'choices' => [
                    ['text' => 'Comment je me déplace ?', 'action' => 'next', 'next' => 7],
                    ['text' => 'Compris, merci !', 'action' => 'close'],
                ],
            ],
            // 2 — Step Combat
            [
                'text' => 'Bravo pour vos premiers pas, {{player_name}} ! Maintenant, il est temps d\'affronter votre premier monstre. Quittez le village et explorez les environs — des créatures rôdent dans les zones sauvages.',
                'choices' => [
                    ['text' => 'Des conseils pour le combat ?', 'action' => 'next', 'next' => 8],
                    ['text' => 'J\'y vais !', 'action' => 'close'],
                ],
            ],
            // 3 — Step Inventory
            [
                'text' => 'Excellent combat, {{player_name}} ! Après chaque victoire, vous pouvez récupérer du butin. N\'oubliez pas de collecter vos récompenses sur l\'écran de butin !',
                'choices' => [
                    ['text' => 'Où va mon butin ?', 'action' => 'next', 'next' => 9],
                    ['text' => 'Compris !', 'action' => 'close'],
                ],
            ],
            // 4 — Step Quests
            [
                'text' => 'Votre inventaire se remplit bien ! Il est temps de vous lancer dans une quête. Parlez aux habitants du village — certains ont des missions à vous confier. Cherchez les icônes au-dessus des PNJ.',
                'choices' => [
                    ['text' => 'Qui donne des quêtes ici ?', 'action' => 'next', 'next' => 10],
                    ['text' => 'Je vais explorer', 'action' => 'close'],
                ],
            ],
            // 5 — Step Craft
            [
                'text' => 'Bientôt un véritable aventurier, {{player_name}} ! Il vous reste à découvrir l\'artisanat. Rendez-vous à un atelier pour fabriquer votre premier objet à partir des matériaux que vous avez collectés.',
                'choices' => [
                    ['text' => 'Comment fonctionne l\'artisanat ?', 'action' => 'next', 'next' => 11],
                    ['text' => 'J\'y vais !', 'action' => 'close'],
                ],
            ],
            // 6 — Tutorial completed
            [
                'text' => '{{player_name}}, vous avez accompli le tutoriel avec brio ! Vous êtes maintenant prêt pour la grande aventure. N\'hésitez pas à revenir me voir si vous avez besoin de conseils.',
                'choices' => [
                    ['text' => 'Des conseils pour la suite ?', 'action' => 'next', 'next' => 12],
                    ['text' => 'Merci Lyra !', 'action' => 'close'],
                ],
            ],
            // 7 — Detail: Movement help
            [
                'text' => 'Cliquez sur une case voisine de votre personnage pour vous y déplacer. Vous pouvez aussi cliquer sur une case plus éloignée — votre personnage trouvera le chemin tout seul. Le village est une zone sûre, profitez-en pour vous familiariser !',
                'choices' => [
                    ['text' => 'Merci !', 'action' => 'close'],
                ],
            ],
            // 8 — Detail: Combat help
            [
                'text' => 'En combat, vous attaquez automatiquement avec votre arme. Gardez un œil sur vos points de vie et utilisez des potions si nécessaire. Iris l\'Alchimiste, au nord-est du village, vend des potions de soin.',
                'choices' => [
                    ['text' => 'Compris, merci !', 'action' => 'close'],
                ],
            ],
            // 9 — Detail: Inventory help
            [
                'text' => 'Votre butin est stocké dans votre sac. Ouvrez votre inventaire pour voir vos objets, les équiper ou les utiliser. Vous pouvez aussi déposer des objets à la banque chez Théodore, au nord du village.',
                'choices' => [
                    ['text' => 'Bien noté !', 'action' => 'close'],
                ],
            ],
            // 10 — Detail: Quest help
            [
                'text' => 'Oriane la Maîtresse des Quêtes se trouve à l\'est du village. D\'autres habitants peuvent aussi avoir des missions. Cherchez les icônes de quête au-dessus de leur tête — un point d\'exclamation signifie qu\'une quête est disponible.',
                'choices' => [
                    ['text' => 'J\'y vais, merci !', 'action' => 'close'],
                ],
            ],
            // 11 — Detail: Craft help
            [
                'text' => 'Pour fabriquer un objet, il vous faut des matériaux et une recette. Les matériaux se trouvent en explorant ou en vainquant des monstres. Rendez-vous à un atelier de craft pour combiner vos ressources.',
                'choices' => [
                    ['text' => 'Compris !', 'action' => 'close'],
                ],
            ],
            // 12 — Detail: Post-tutorial tips
            [
                'text' => 'Explorez les différentes zones autour du village, progressez dans les arbres de talent, et collectez des materia pour débloquer de nouveaux sorts. Le monde est vaste — bonne aventure !',
                'choices' => [
                    ['text' => 'Merci pour tout, Lyra !', 'action' => 'close'],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
        ];
    }
}
