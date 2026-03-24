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

        // Delivery/Exploration/Choice quests mapped to specific PNJ indices
        $specialQuests = [
            10 => 'quest_deliver_mushroom',   // Henri le Fermier
            16 => 'quest_choice_alliance',    // Michel le Garde
            21 => 'quest_explore_forest',     // Mathilde la Cartographe
        ];

        // Chaîne Acte 1 — PNJ narratifs spéciaux
        $acte1QuestRefs = [
            'reveil' => 'quest_acte1_reveil',
            'premiers_pas' => 'quest_acte1_premiers_pas',
            'bapteme' => 'quest_acte1_bapteme_du_feu',
            'recolte' => 'quest_acte1_recolte',
            'cristal' => 'quest_acte1_cristal',
        ];
        $acte1QuestIds = [];
        foreach ($acte1QuestRefs as $key => $ref) {
            /** @var \App\Entity\Game\Quest $q */
            $q = $this->getReference($ref, \App\Entity\Game\Quest::class);
            $acte1QuestIds[$key] = $q->getId();
        }

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
            if (15 === $i) {
                // Claire la Sage : guide Acte 1 (quêtes 1, 2 et 5)
                $dialog = $this->createClaireSageDialog($acte1QuestIds);
            } elseif (0 === $i) {
                // Gérard le Forgeron : quête Acte 1 baptême + boutique
                $dialog = $this->createGerardActe1Dialog($acte1QuestIds, $shopConfigs[0], $questId);
            } elseif (7 === $i) {
                // Marie la Herboriste : quête Acte 1 récolte + boutique
                $dialog = $this->createMarieActe1Dialog($acte1QuestIds, $shopConfigs[7]);
            } elseif ($specialQuestRef) {
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
        $isChoice = str_contains($questRef, 'choice');

        $greetings = [
            'quest_deliver_mushroom' => 'Bonjour voyageur ! J\'ai besoin d\'aide pour une livraison importante.',
            'quest_explore_forest' => 'Salutations, aventurier ! J\'aurais besoin de vos talents d\'explorateur.',
            'quest_choice_alliance' => 'Halte, aventurier ! J\'ai une affaire délicate à vous confier.',
        ];
        $descriptions = [
            'quest_deliver_mushroom' => 'J\'ai besoin que quelqu\'un m\'apporte des champignons frais. Marie l\'herboriste en a besoin pour ses remèdes. Pouvez-vous m\'aider ?',
            'quest_explore_forest' => 'Je travaille sur une carte de la région mais il me manque des données sur la forêt. Pourriez-vous vous rendre à la clairière et confirmer ce que j\'y ai noté ?',
            'quest_choice_alliance' => 'Un convoi de ressources a été abandonné sur la route. Allez le trouver et revenez me voir — mais sachez que le marchand le convoite aussi. Vous devrez choisir à qui les remettre.',
        ];

        $greeting = $greetings[$questRef] ?? 'Bonjour, aventurier !';
        $description = $descriptions[$questRef] ?? 'J\'ai une mission pour vous.';

        if ($isChoice) {
            // Choice quest: different completion dialogs based on player choice
            $dialog = [
                [
                    'next' => 1,
                    'text' => $greeting,
                ],
                [
                    'conditional_next' => [
                        [
                            'next' => 5,
                            'next_condition' => [
                                'quest_not' => [$questId],
                            ],
                        ],
                        [
                            'next' => 2,
                            'next_condition' => [
                                'quest' => [$questId],
                                'quest_choice' => [$questId => 'help_guard'],
                            ],
                        ],
                        [
                            'next' => 3,
                            'next_condition' => [
                                'quest' => [$questId],
                            ],
                        ],
                        [
                            'next' => 4,
                        ],
                    ],
                    'text' => 'Que puis-je faire pour vous ?',
                ],
                // Quest completed — chose guard (index 2)
                [
                    'text' => 'Vous avez fait le bon choix en remettant ces ressources à la garde. Le village vous en est reconnaissant, soldat !',
                ],
                // Quest completed — chose merchant (index 3)
                [
                    'text' => 'Je vois que vous avez préféré remettre les ressources au marchand... C\'est votre droit, mais la garde n\'oublie pas.',
                ],
                // In-progress (index 4)
                [
                    'text' => 'Avez-vous trouvé le convoi ? Revenez quand vous l\'aurez localisé, et vous devrez faire votre choix.',
                ],
                // Quest offer (index 5)
                [
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
                ],
            ];

            return $dialog;
        }

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

    /**
     * Claire la Sage — guide narratif Acte 1 (quêtes 1 Réveil, 2 Premiers pas, 5 Cristal).
     */
    private function createClaireSageDialog(array $acte1QuestIds): array
    {
        $qReveil = $acte1QuestIds['reveil'];
        $qPremiersPas = $acte1QuestIds['premiers_pas'];
        $qCristal = $acte1QuestIds['cristal'];

        return [
            // 0 — Accueil
            [
                'next' => 1,
                'text' => 'Vous êtes enfin réveillé, {{player_name}}... Vous étiez inconscient quand nous vous avons trouvé au bord du chemin. Personne ne sait d\'où vous venez.',
            ],
            // 1 — Aiguillage conditionnel
            [
                'conditional_next' => [
                    // Toute la chaîne terminée (cristal fait) → dialogue final
                    ['next' => 8, 'next_condition' => ['quest' => [$qCristal]]],
                    // Cristal proposable (récolte terminée, cristal pas encore fait)
                    ['next' => 7, 'next_condition' => ['quest_prerequisites_met' => [$qCristal], 'quest_not' => [$qCristal]]],
                    // Premiers pas terminé → encouragement combat
                    ['next' => 5, 'next_condition' => ['quest' => [$qPremiersPas]]],
                    // Réveil terminé → proposer Premiers pas
                    ['next' => 4, 'next_condition' => ['quest' => [$qReveil], 'quest_not' => [$qPremiersPas]]],
                    // En cours (réveil accepté mais pas terminé)
                    ['next' => 3, 'next_condition' => ['quest_active' => [$qReveil]]],
                    // Rien fait → proposer Réveil
                    ['next' => 2],
                ],
                'text' => 'Que puis-je faire pour vous, voyageur ?',
            ],
            // 2 — Proposer quête Réveil
            [
                'text' => 'Vous semblez désorienté. Commencez par explorer la place du village pour reprendre vos repères. Cela vous aidera peut-être à retrouver la mémoire.',
                'choices' => [
                    ['text' => 'D\'accord, je vais explorer', 'action' => 'quest_offer', 'data' => ['quest' => $qReveil]],
                    ['text' => 'Pas maintenant', 'action' => 'close'],
                ],
            ],
            // 3 — Réveil en cours
            [
                'text' => 'Allez explorer la place du village, {{player_name}}. Prenez le temps d\'observer les alentours.',
            ],
            // 4 — Proposer Premiers pas
            [
                'text' => 'Bien, vous avez l\'air d\'aller mieux. Maintenant, vous aurez besoin d\'une arme. Allez voir Gérard le Forgeron, il vous équipera.',
                'choices' => [
                    ['text' => 'J\'y vais', 'action' => 'quest_offer', 'data' => ['quest' => $qPremiersPas]],
                    ['text' => 'Plus tard', 'action' => 'close'],
                ],
            ],
            // 5 — Après Premiers pas, encouragement
            [
                'next' => 6,
                'text' => 'Vous avez une arme maintenant, c\'est un bon début. Gérard ou Marie pourront vous guider pour la suite. Revenez me voir quand vous aurez fait vos preuves.',
            ],
            // 6 — Fin temporaire
            [
                'text' => 'Que les cristaux veillent sur vous, {{player_name}}.',
            ],
            // 7 — Proposer quête Cristal
            [
                'text' => 'Vous avez fait du chemin, {{player_name}}. Je sens que vous êtes prêt. Il existe un cristal d\'améthyste caché dans une clairière au sud... Il pourrait détenir la clé de vos souvenirs perdus.',
                'choices' => [
                    ['text' => 'Je veux le trouver', 'action' => 'quest_offer', 'data' => ['quest' => $qCristal]],
                    ['text' => 'Je ne suis pas encore prêt', 'action' => 'close'],
                ],
            ],
            // 8 — Cristal trouvé (fin Acte 1)
            [
                'next' => 9,
                'text' => 'Vous l\'avez trouvé ! Le Cristal d\'Améthyste... Je savais que vous en étiez capable.',
            ],
            // 9 — Épilogue
            [
                'text' => 'Ce cristal est lié à votre passé, j\'en suis certaine. Gardez-le précieusement. Votre aventure ne fait que commencer, {{player_name}}.',
            ],
        ];
    }

    /**
     * Gérard le Forgeron — quête Acte 1 « Baptême du feu » + boutique + quête zombie existante.
     */
    private function createGerardActe1Dialog(array $acte1QuestIds, array $shopConfig, ?int $existingQuestId): array
    {
        $qBapteme = $acte1QuestIds['bapteme'];

        return [
            // 0 — Accueil
            [
                'next' => 1,
                'text' => $shopConfig['greeting'],
            ],
            // 1 — Aiguillage
            [
                'conditional_next' => [
                    // Baptême terminé → remerciement + boutique
                    ['next' => 5, 'next_condition' => ['quest' => [$qBapteme]]],
                    // Baptême proposable (premiers pas terminés)
                    ['next' => 3, 'next_condition' => ['quest_prerequisites_met' => [$qBapteme], 'quest_not' => [$qBapteme]]],
                    // Baptême en cours
                    ['next' => 4, 'next_condition' => ['quest_active' => [$qBapteme]]],
                    // Par défaut → boutique
                    ['next' => 2],
                ],
                'text' => 'Que puis-je faire pour vous ?',
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Autre chose...', 'action' => 'next'],
                ],
            ],
            // 2 — Boutique simple (pas encore dans l'Acte 1)
            [
                'text' => 'N\'hésitez pas à revenir si vous avez besoin d\'équipement.',
            ],
            // 3 — Proposer Baptême du feu
            [
                'text' => 'Alors c\'est vous, le nouveau que Claire m\'a envoyé ? Vous avez une épée maintenant, mais il faut apprendre à s\'en servir. Allez donc éliminer quelques slimes aux abords du village.',
                'choices' => [
                    ['text' => 'Je suis prêt !', 'action' => 'quest_offer', 'data' => ['quest' => $qBapteme]],
                    ['text' => 'Pas encore', 'action' => 'close'],
                ],
            ],
            // 4 — En cours
            [
                'text' => 'Alors, ces slimes ? Revenez quand vous en aurez éliminé deux. Ce n\'est pas sorcier !',
            ],
            // 5 — Baptême terminé
            [
                'text' => 'Bien joué ! Vous vous débrouillez mieux que je ne le pensais. Ma boutique est à votre disposition.',
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci, à bientôt', 'action' => 'close'],
                ],
            ],
        ];
    }

    /**
     * Marie la Herboriste — quête Acte 1 « Récolte » + boutique.
     */
    private function createMarieActe1Dialog(array $acte1QuestIds, array $shopConfig): array
    {
        $qRecolte = $acte1QuestIds['recolte'];

        return [
            // 0 — Accueil
            [
                'next' => 1,
                'text' => $shopConfig['greeting'],
            ],
            // 1 — Aiguillage
            [
                'conditional_next' => [
                    // Récolte terminée → remerciement + boutique
                    ['next' => 5, 'next_condition' => ['quest' => [$qRecolte]]],
                    // Récolte proposable (baptême terminé)
                    ['next' => 3, 'next_condition' => ['quest_prerequisites_met' => [$qRecolte], 'quest_not' => [$qRecolte]]],
                    // Récolte en cours
                    ['next' => 4, 'next_condition' => ['quest_active' => [$qRecolte]]],
                    // Par défaut → boutique
                    ['next' => 2],
                ],
                'text' => 'Que cherchez-vous aujourd\'hui ?',
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Autre chose...', 'action' => 'next'],
                ],
            ],
            // 2 — Boutique simple
            [
                'text' => 'Mes herbes et outils sont à votre disposition.',
            ],
            // 3 — Proposer Récolte
            [
                'text' => 'Oh, un aventurier ! J\'ai justement besoin d\'aide. Mes réserves de champignons sont épuisées et j\'en ai besoin pour mes remèdes. Pourriez-vous m\'en rapporter trois ?',
                'choices' => [
                    ['text' => 'Bien sûr, je m\'en occupe', 'action' => 'quest_offer', 'data' => ['quest' => $qRecolte]],
                    ['text' => 'Pas pour le moment', 'action' => 'close'],
                ],
            ],
            // 4 — En cours
            [
                'text' => 'Avez-vous trouvé les champignons ? Il m\'en faut trois pour préparer mes remèdes.',
            ],
            // 5 — Récolte terminée
            [
                'text' => 'Merveilleux ! Ces champignons sont parfaits. Tenez, ce parchemin vous initiera aux secrets de l\'herboristerie. Ma boutique est ouverte si vous avez besoin de quoi que ce soit.',
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci Marie !', 'action' => 'close'],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
            QuestFixtures::class,
        ];
    }
}
