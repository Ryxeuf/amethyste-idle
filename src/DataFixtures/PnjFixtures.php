<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Pnj;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PnjFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Configuration des boutiques PNJ : index PNJ => liste de slugs items.
     */
    private function getShopConfigs(): array
    {
        return [
            // Gérard le Forgeron — armes et armures (ouvert de 8h à 20h)
            0 => [
                'items' => ['short-sword', 'long-sword', 'leather-armor', 'leather-boots', 'leather-hat'],
                'greeting' => 'Bienvenue dans ma forge ! J\'ai les meilleures armes et armures de la région.',
                'shop_prompt' => 'Voyons ce que j\'ai en stock pour vous.',
                'opensAt' => 8,
                'closesAt' => 20,
            ],
            // Élise la Guérisseuse — potions et soins (toujours ouverte)
            1 => [
                'items' => ['life-potion', 'healing-potion-major', 'antidote', 'mushroom', 'beer-pint'],
                'greeting' => 'Bonjour, voyageur. Vous avez l\'air fatigué... J\'ai ce qu\'il vous faut pour reprendre des forces.',
                'shop_prompt' => 'Voici mes remèdes et potions.',
            ],
            // Pierre le Tavernier — consommables et boissons (ouvert de 10h à 2h)
            4 => [
                'items' => ['beer-pint', 'bread', 'grilled-meat', 'stew', 'mushroom', 'life-potion'],
                'greeting' => 'Holà, aventurier ! Installez-vous au comptoir. Qu\'est-ce que je vous sers ?',
                'shop_prompt' => 'Voici la carte de ma taverne.',
                'opensAt' => 10,
                'closesAt' => 2,
            ],
            // Marie la Herboriste — plantes et outils herboristerie (ouverte de 6h à 18h)
            7 => [
                'items' => ['plant-mint', 'plant-sage', 'plant-lavender', 'plant-thyme', 'plant-rosemary', 'sickle-bronze', 'sickle-iron'],
                'greeting' => 'Bonjour ! Mon jardin regorge de plantes médicinales. Vous en cherchez ?',
                'shop_prompt' => 'Regardez mes herbes et mes outils de récolte.',
                'opensAt' => 6,
                'closesAt' => 18,
            ],
            // Émilie la Marchande — outils variés et ressources de base (ouverte de 8h à 22h)
            13 => [
                'items' => ['pickaxe-bronze', 'pickaxe-iron', 'sickle-bronze', 'fishing-rod-bronze', 'fishing-rod-iron', 'skinning-knife-bronze', 'skinning-knife-iron'],
                'greeting' => 'Bienvenue chez moi ! J\'ai tout ce dont un aventurier a besoin pour ses expéditions.',
                'shop_prompt' => 'Voici mon inventaire d\'outils et de matériel.',
                'opensAt' => 8,
                'closesAt' => 22,
            ],
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Liste des quêtes disponibles dans QuestFixtures
        $questReferences = [
            'quest_zombie_1',
            'quest_skeleton_1',
            'quest_taiju_1',
            'quest_mushroom_1',
            'quest_goblin_1',
            'quest_troll_1',
            'quest_werewolf_1',
            'quest_banshee_griffin_1',
            'quest_wood_collection',
            'quest_dragon_1',
        ];

        // Delivery/Exploration quests mapped to specific PNJ indices
        $specialQuests = [
            10 => 'quest_deliver_mushroom',   // Henri le Fermier
            21 => 'quest_explore_forest',     // Mathilde la Cartographe
        ];

        // Noms de PNJ français
        $pnjNames = [
            'Gérard le Forgeron', 'Élise la Guérisseuse', 'Martin le Bûcheron', 'Jeanne la Tisserande',
            'Pierre le Tavernier', 'Sophie la Boulangère', 'Louis le Pêcheur', 'Marie la Herboriste',
            'François le Chasseur', 'Lucie la Couturière', 'Henri le Fermier', 'Camille la Potière',
            'Bernard l\'Alchimiste', 'Émilie la Marchande', 'Thomas le Menuisier', 'Claire la Sage',
            'Michel le Garde', 'Aurélie l\'Archère', 'Antoine le Mage', 'Céline la Prêtresse',
            'Julien le Troubadour', 'Mathilde la Cartographe', 'Nicolas le Mineur', 'Élodie la Voyante',
            'Sébastien le Chevalier', 'Chloé l\'Exploratrice', 'Romain le Druide', 'Léa la Danseuse',
            'Vincent le Sculpteur', 'Amandine l\'Astronome', 'Benoît le Cuisinier', 'Pauline la Bibliothécaire',
            'Thierry le Charpentier', 'Mélanie la Brodeuse', 'Olivier le Tonnelier', 'Nathalie la Parfumeuse',
            'Christophe le Tanneur', 'Stéphanie la Joaillière', 'Frédéric le Verrier', 'Audrey la Musicienne',
            'Guillaume le Messager', 'Sandrine la Teinturière', 'Maxime le Palefrenier', 'Virginie la Meunière',
            'Damien le Bourrelier', 'Caroline la Fleuriste', 'Ludovic le Cordier', 'Delphine la Vannière',
            'Jérôme le Maraîcher', 'Isabelle la Lavandière', 'Fabien le Charron', 'Laure la Fromagère',
            'Patrice le Vigneron', 'Sylvie la Sage-femme', 'Didier le Berger', 'Véronique la Fileuse',
            'Arnaud le Peintre', 'Hélène la Conteuse',
        ];

        // Types de classe pour les PNJ
        $classTypes = ['villager', 'merchant', 'guard', 'noble', 'warrior', 'mage', 'healer', 'blacksmith', 'farmer', 'hunter'];

        // Coordonnées possibles (simplifiées pour l'exemple)
        $coordinates = [
            '1.5', '2.3', '3.7', '4.2', '5.8', '6.1', '7.4', '8.9', '9.3', '10.6',
            '11.2', '12.7', '13.4', '14.8', '15.3', '16.9', '17.5', '18.2', '19.7', '20.1',
        ];

        // Portraits pour les PNJ narratifs principaux
        $portraits = [
            0 => '/styles/images/portraits/blacksmith.png',    // Gérard le Forgeron
            1 => '/styles/images/portraits/healer.png',        // Élise la Guérisseuse
            4 => '/styles/images/portraits/tavern.png',        // Pierre le Tavernier
            7 => '/styles/images/portraits/herbalist.png',     // Marie la Herboriste
            13 => '/styles/images/portraits/merchant.png',     // Émilie la Marchande
            15 => '/styles/images/portraits/sage.png',         // Claire la Sage
            16 => '/styles/images/portraits/guard.png',        // Michel le Garde
            18 => '/styles/images/portraits/mage.png',         // Antoine le Mage
            19 => '/styles/images/portraits/priestess.png',    // Céline la Prêtresse
            24 => '/styles/images/portraits/knight.png',       // Sébastien le Chevalier
        ];

        $shopConfigs = $this->getShopConfigs();

        // Création de 60 PNJ
        for ($i = 0; $i < 60; ++$i) {
            $pnj = new Pnj();
            $pnj->setName($pnjNames[$i] ?? 'PNJ #' . ($i + 1));
            $pnj->setLife(10);
            $pnj->setMaxLife(10);
            $pnj->setMap($this->getReference('map_1', Map::class));
            $pnj->setCoordinates($coordinates[$i % count($coordinates)]);
            $pnj->setClassType($classTypes[$i % count($classTypes)]);

            // Configurer le portrait si défini
            if (isset($portraits[$i])) {
                $pnj->setPortrait($portraits[$i]);
            }

            // Configurer la boutique si ce PNJ est un marchand
            if (isset($shopConfigs[$i])) {
                $pnj->setShopItems($shopConfigs[$i]['items']);
                if (isset($shopConfigs[$i]['opensAt'])) {
                    $pnj->setOpensAt($shopConfigs[$i]['opensAt']);
                }
                if (isset($shopConfigs[$i]['closesAt'])) {
                    $pnj->setClosesAt($shopConfigs[$i]['closesAt']);
                }
            }

            // Création d'un dialogue unique pour chaque PNJ
            $questId = $i < count($questReferences) ? $i + 1 : null;
            $specialQuestRef = $specialQuests[$i] ?? null;
            if ($specialQuestRef) {
                /** @var \App\Entity\Game\Quest $specialQuest */
                $specialQuest = $this->getReference($specialQuestRef, \App\Entity\Game\Quest::class);
                $dialog = $this->createSpecialQuestDialog($i, $specialQuest->getId(), $specialQuestRef);
            } else {
                $dialog = $this->createDialog($i, $questId, $shopConfigs[$i] ?? null);
            }
            $pnj->setDialog($dialog);

            $pnj->setCreatedAt(new \DateTime());
            $pnj->setUpdatedAt(new \DateTime());

            $manager->persist($pnj);
            $this->addReference('pnj_' . $i, $pnj);
        }

        $manager->flush();
    }

    /**
     * Crée un dialogue unique pour un PNJ.
     *
     * @param int        $pnjIndex   L'index du PNJ
     * @param int|null   $questId    L'ID de la quête à proposer (null si pas de quête)
     * @param array|null $shopConfig Config boutique (items, greeting, shop_prompt)
     *
     * @return array Le dialogue formaté
     */
    private function createDialog(int $pnjIndex, ?int $questId, ?array $shopConfig = null): array
    {
        // Phrases d'accueil variées
        $greetings = [
            'Bonjour voyageur ! Belle journée, n\'est-ce pas ?',
            'Ah, un nouveau visage ! Bienvenue dans notre contrée.',
            'Salutations, aventurier. Que puis-je faire pour vous ?',
            'Bien le bonjour ! Vous semblez venir de loin.',
            'Holà ! Ravi de faire votre connaissance.',
            'Bonjour à vous ! Que les dieux vous protègent.',
            'Bienvenue, étranger. Vous cherchez quelque chose en particulier ?',
            'Salut à toi, brave voyageur !',
            'Bonjour ! Vous tombez bien, j\'ai justement besoin d\'aide.',
            'Ah, enfin quelqu\'un ! J\'attendais de l\'aide.',
        ];

        // Phrases de dialogue générales
        $generalDialogs = [
            'Le temps est magnifique aujourd\'hui, n\'est-ce pas ?',
            'Avez-vous entendu parler des monstres qui rôdent dans les environs ?',
            'Notre village est petit, mais nous sommes accueillants.',
            'Méfiez-vous si vous allez dans la forêt, elle regorge de dangers.',
            'Les récoltes ont été bonnes cette année, nous avons de la chance.',
            'Vous devriez visiter la taverne, on y sert la meilleure bière de la région.',
            'Le forgeron du village fabrique les meilleures armes, allez le voir si vous en avez besoin.',
            'Avez-vous déjà rencontré notre chef de village ? C\'est un homme sage.',
            'Les nuits sont dangereuses par ici, restez vigilant.',
            'J\'ai entendu dire que des trésors sont cachés dans les montagnes au nord.',
            'Nous vivons en paix ici, mais pour combien de temps encore ?',
            'Connaissez-vous la légende du dragon qui sommeille sous la montagne ?',
            'Les marchands viennent rarement jusqu\'ici, nous manquons de certaines ressources.',
            'Si vous cherchez des herbes médicinales, allez voir notre herboriste.',
            'La rivière à l\'est est excellente pour la pêche, si cela vous intéresse.',
            'Avez-vous des nouvelles des royaumes voisins ?',
            'Notre village existe depuis des générations, nous sommes fiers de notre histoire.',
            'Les enfants adorent écouter les histoires des aventuriers comme vous.',
            'Prenez garde aux bandits sur les routes, ils sont de plus en plus nombreux.',
            'Avez-vous besoin d\'un endroit où vous reposer ? L\'auberge est confortable.',
        ];

        // Descriptions de quêtes variées
        $questDescriptions = [
            'J\'ai un problème avec des créatures qui menacent notre village. Pourriez-vous nous aider ?',
            'Nous avons besoin de quelqu\'un de courageux pour une mission dangereuse. Êtes-vous partant ?',
            'J\'ai une tâche qui nécessite les compétences d\'un aventurier comme vous.',
            'Notre communauté est en danger, et vous semblez capable de nous aider.',
            'J\'ai une requête importante qui pourrait vous intéresser, et la récompense est généreuse.',
            'Nous sommes désespérés, personne n\'a réussi à accomplir cette mission jusqu\'à présent.',
            'J\'ai besoin de votre aide pour une affaire délicate. Êtes-vous intéressé ?',
            'Une menace plane sur nous, et vous pourriez être notre sauveur.',
            'J\'ai une proposition qui pourrait vous rapporter gros, si vous êtes à la hauteur.',
            'Notre village a besoin d\'un héros, et vous semblez être la personne idéale.',
        ];

        // Dialogue marchand (sans quête)
        if ($shopConfig !== null && $questId === null) {
            return [
                [
                    'text' => $shopConfig['greeting'],
                    'choices' => [
                        [
                            'text' => 'Voir la boutique',
                            'action' => 'open_shop',
                            'datas' => [],
                        ],
                        [
                            'text' => 'Au revoir',
                            'action' => 'close',
                        ],
                    ],
                ],
            ];
        }

        // Dialogue marchand + quête
        if ($shopConfig !== null && $questId !== null) {
            return [
                [
                    'next' => 1,
                    'text' => $shopConfig['greeting'],
                ],
                [
                    'conditional_next' => [
                        [
                            'next' => 4,
                            'next_condition' => [
                                'quest_not' => [$questId],
                            ],
                        ],
                        [
                            'next' => 2,
                            'next_condition' => [
                                'quest' => [$questId],
                            ],
                        ],
                        [
                            'next' => 3,
                        ],
                    ],
                    'text' => $shopConfig['shop_prompt'],
                    'choices' => [
                        [
                            'text' => 'Voir la boutique',
                            'action' => 'open_shop',
                            'datas' => [],
                        ],
                        [
                            'text' => 'Autre chose...',
                            'action' => 'next',
                        ],
                    ],
                ],
                [
                    'text' => 'Merci d\'avoir accepté de m\'aider. Revenez me voir quand vous aurez terminé.',
                ],
                [
                    'text' => 'Avez-vous terminé la mission que je vous ai confiée ?',
                ],
                [
                    'text' => $questDescriptions[$pnjIndex % count($questDescriptions)],
                    'choices' => [
                        [
                            'text' => 'Oui, je vais vous aider',
                            'data' => [
                                'quest' => $questId,
                            ],
                            'action' => 'quest_offer',
                        ],
                        [
                            'text' => 'Non, pas maintenant',
                            'action' => 'close',
                        ],
                    ],
                ],
            ];
        }

        // Dialogue de base pour tous les PNJ
        $dialog = [
            [
                'next' => 1,
                'text' => $greetings[$pnjIndex % count($greetings)],
            ],
            [
                'text' => $generalDialogs[$pnjIndex % count($generalDialogs)],
            ],
        ];

        // Ajouter une quête uniquement aux 10 premiers PNJ (ou selon le nombre de quêtes disponibles)
        if ($questId !== null) {
            // Modifier le dialogue pour inclure une proposition de quête
            $dialog = [
                [
                    'next' => 1,
                    'text' => $greetings[$pnjIndex % count($greetings)],
                ],
                [
                    'conditional_next' => [
                        [
                            'next' => 4,
                            'next_condition' => [
                                'quest_not' => [$questId],
                            ],
                        ],
                        [
                            'next' => 2,
                            'next_condition' => [
                                'quest' => [$questId],
                            ],
                        ],
                        [
                            'next' => 3,
                        ],
                    ],
                    'text' => $generalDialogs[$pnjIndex % count($generalDialogs)],
                ],
                [
                    'text' => 'Merci d\'avoir accepté de m\'aider. Revenez me voir quand vous aurez terminé.',
                ],
                [
                    'text' => 'Avez-vous terminé la mission que je vous ai confiée ?',
                ],
                [
                    'text' => $questDescriptions[$pnjIndex % count($questDescriptions)],
                    'choices' => [
                        [
                            'text' => 'Oui, je vais vous aider',
                            'data' => [
                                'quest' => $questId,
                            ],
                            'action' => 'quest_offer',
                        ],
                        [
                            'text' => 'Non, pas maintenant',
                            'action' => 'close',
                        ],
                    ],
                ],
            ];
        }

        return $dialog;
    }

    /**
     * Creates a dialog for delivery/exploration quest PNJs.
     */
    private function createSpecialQuestDialog(int $pnjIndex, int $questId, string $questRef): array
    {
        $isDeliver = str_contains($questRef, 'deliver');

        $greetings = [
            'quest_deliver_mushroom' => 'Bonjour voyageur ! J\'ai besoin d\'aide pour une livraison importante.',
            'quest_explore_forest' => 'Salutations, aventurier ! J\'aurais besoin de vos talents d\'explorateur.',
        ];
        $descriptions = [
            'quest_deliver_mushroom' => 'J\'ai besoin que quelqu\'un m\'apporte des champignons frais. Marie l\'herboriste en a besoin pour ses remèdes. Pouvez-vous m\'aider ?',
            'quest_explore_forest' => 'Je travaille sur une carte de la région mais il me manque des données sur la forêt. Pourriez-vous vous rendre à la clairière et confirmer ce que j\'y ai noté ?',
        ];

        $greeting = $greetings[$questRef] ?? 'Bonjour, aventurier !';
        $description = $descriptions[$questRef] ?? 'J\'ai une mission pour vous.';

        $dialog = [
            [
                'next' => 1,
                'text' => $greeting,
            ],
            [
                'conditional_next' => [
                    [
                        'next' => 4,
                        'next_condition' => [
                            'quest_not' => [$questId],
                        ],
                    ],
                    [
                        'next' => 2,
                        'next_condition' => [
                            'quest' => [$questId],
                        ],
                    ],
                    [
                        'next' => 3,
                    ],
                ],
                'text' => 'Que puis-je faire pour vous ?',
            ],
            // Quest completed
            [
                'text' => 'Merci pour votre aide ! La mission est accomplie.',
            ],
        ];

        if ($isDeliver) {
            // In-progress: offer delivery action
            $dialog[] = [
                'text' => 'Avez-vous les items demandés ? Donnez-les-moi !',
                'choices' => [
                    [
                        'text' => 'Livrer les items',
                        'action' => 'quest_deliver',
                        'datas' => [],
                    ],
                    [
                        'text' => 'Pas encore...',
                        'action' => 'close',
                    ],
                ],
            ];
        } else {
            // In-progress: exploration reminder
            $dialog[] = [
                'text' => 'Avez-vous exploré la zone indiquée ? Revenez quand ce sera fait !',
            ];
        }

        // Quest offer (index 4)
        $dialog[] = [
            'text' => $description,
            'choices' => [
                [
                    'text' => 'D\'accord, je m\'en occupe',
                    'data' => [
                        'quest' => $questId,
                    ],
                    'action' => 'quest_offer',
                ],
                [
                    'text' => 'Non merci',
                    'action' => 'close',
                ],
            ],
        ];

        return $dialog;
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
            QuestFixtures::class,
        ];
    }
}
