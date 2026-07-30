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
            // ONB-16 : le slug est la cle d'idempotence des habitants declares
            // (convention ZON-26b-b). Sans lui, la seule facon de retrouver un
            // PNJ du Fanal est son **nom affiche** — ce qui interdit de le
            // renommer, et fait dependre le code d'un texte de fiction.
            $pnj->setSlug($data['slug']);
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
            // 0 — Ysold, maîtresse d'armes (ONB-16)
            //
            // **Ce poste était Aldric le Forgeron, et c'était un doublon.** Le
            // Fanal comptait deux forgerons : celui-ci, et Gérard le Forgeron,
            // porteur de l'arc `intro` que les quêtes désignent par sa référence
            // de fixture (`pnj_0`). Gérard reste — on ne débranche pas un
            // donneur de quête —, et ce poste devient **le rôle qui manquait**.
            //
            // Car la chaîne de l'acte I commence chez le maître d'armes
            // (GAME_ONBOARDING § 5.2, étape 1) et il n'existait pas. Le
            // transformer plutôt que d'en ajouter un neuvième règle les deux
            // dettes d'un coup : le doublon disparaît, le manque est comblé, et
            // l'étal d'armes déjà écrit sert exactement le bon personnage.
            //
            // Au passage, « Aldric » cesse d'être porté par deux personnages :
            // **Aldric l'Ancien**, l'ermite de la Crête, est un donneur de quête
            // de l'acte 2. Deux Aldric à trois zones d'écart, dont un seul
            // compte pour une quête, est le genre de collision qu'on ne
            // découvre qu'en jouant.
            [
                'slug' => 'fanal-maitresse-armes-ysold',
                'name' => 'Ysold, maîtresse d\'armes',
                'coordinates' => '7.7',
                'classType' => 'warrior',
                'portrait' => '/styles/images/portraits/blacksmith.png',
                // Elle vend les armes de palier 1 **et les parchemins des arbres
                // qui apprennent à les tenir** (ONB-08/ONB-20b). C'est ce qui
                // fait de l'étape 1 un vrai choix : on repart avec une arme
                // **et** la voie qui l'autorise.
                'shopItems' => [
                    'short-sword', 'long-sword', 'iron-sword', 'wooden-shield',
                    'leather-armor', 'leather-boots', 'leather-hat', 'leather-helmet',
                    'soldier-domain-parchment', 'berserker-domain-parchment',
                    'archer-domain-parchment', 'assassin-domain-parchment',
                    'knight-domain-parchment', 'paladin-domain-parchment',
                ],
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
                        'text' => 'Tiens. Une main qui n\'a encore rien tenu. Ici on n\'apprend pas à se battre : on apprend à tenir une arme, et le reste vient tout seul. Que cherches-tu ?',
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Comment apprend-on à tenir une arme ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Au revoir',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'Par un parchemin, comme tout le reste. Il ne t\'apprendra rien — il t\'ouvre une voie, et c\'est toi qui la parcours. Prends l\'arme qui te va, prends la voie qui l\'autorise, et va voir les mannequins dans la cour. Ils ne mordent pas.',
                        'choices' => [
                            [
                                'text' => 'Merci, maîtresse d\'armes',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],
            // 1 — Iris l'Alchimiste (NE, près du bâtiment 33,4)
            [
                'slug' => 'fanal-alchimiste-iris',
                'name' => 'Iris l\'Alchimiste',
                'coordinates' => '33.8',
                'classType' => 'mage',
                'portrait' => '/styles/images/portraits/herbalist.png',
                'shopItems' => ['life-potion', 'healing-potion-small', 'healing-potion-medium', 'healing-potion-major', 'antidote', 'energy-potion-small', 'crafted-potion-base', 'alchimist-domain-parchment'],
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
                'slug' => 'fanal-marchand-marcellin',
                'name' => 'Marcellin le Marchand',
                'coordinates' => '7.23',
                'classType' => 'merchant',
                'portrait' => '/styles/images/portraits/merchant.png',
                // ECO-02 — plancher T1 : les huit outils de bronze, un par metier,
                // **sans limite de stock**. C'est ce qui fait de cette echoppe un
                // plancher : un stock fini se vide, et le nouveau venu se retrouve
                // face a un marche joueur qu'il ne peut pas encore alimenter.
                //
                // `pickaxe` et `fishing-rod` ont ete retires : ce sont des objets
                // `stuff` sans `toolType`, donc inoperants — ni la recolte ni
                // l'artisanat ne les reconnaissent. Les vendre revenait a vendre un
                // outil qui ne fonctionne pas.
                'shopItems' => [
                    // DOM-05 : la hache rejoint les quatre autres outils de recolte.
                    // Un outil qu'aucun etal ne vend est un outil que le nœud
                    // d'entree autorise sans que personne puisse l'obtenir.
                    'pickaxe-bronze', 'sickle-bronze', 'fishing-rod-bronze', 'skinning-knife-bronze', 'axe-bronze',
                    'hammer-bronze', 'tanning-kit-bronze', 'mortar-bronze', 'chisel-bronze',
                    'bread', 'grilled-meat', 'stew', 'mushroom', 'beer-pint', 'scroll-teleport',
                ],
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
                'slug' => 'fanal-quetes-oriane',
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
                'slug' => 'fanal-banquier-theodore',
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
                'slug' => 'fanal-garde-gareth',
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
                'slug' => 'fanal-guide-lyra',
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
            // 1 — L'arme (arc 1-2)
            [
                'text' => 'Bienvenue au Fanal, {{player_name}} ! Je suis Lyra. Allez voir Ysold, notre maîtresse d\'armes : elle vous fera choisir une arme, et la voie qui apprend à la tenir. Lisez le parchemin, prenez le nœud, portez l\'arme — c\'est la boucle de tout le jeu.',
                'choices' => [
                    ['text' => 'Un parchemin, une voie ?', 'action' => 'next', 'next' => 7],
                    ['text' => 'Compris, merci !', 'action' => 'close'],
                ],
            ],
            // 2 — La materia (arc 3-5)
            [
                'text' => 'Bravo, {{player_name}} ! Gareth a planté un mannequin sur la place — il ne rend pas les coups. Battez-le, on vous remettra une matéria de votre voie. Accordez-la, sertissez-la, puis lancez son sort sur le second mannequin.',
                'choices' => [
                    ['text' => 'Qu\'est-ce qu\'une matéria ?', 'action' => 'next', 'next' => 8],
                    ['text' => 'J\'y vais !', 'action' => 'close'],
                ],
            ],
            // 3 — Le metier (arc 6-8)
            [
                'text' => 'Vous savez vous battre, {{player_name}}. Il vous reste un choix, et c\'est le plus structurant de votre semaine : un métier de récolte. Cinq voies, aucune fermée. Récoltez, puis fabriquez ce que vous aurez ramassé.',
                'choices' => [
                    ['text' => 'Lequel choisir ?', 'action' => 'next', 'next' => 9],
                    ['text' => 'Compris !', 'action' => 'close'],
                ],
            ],
            // 4 — Le depart (arc 9)
            [
                'text' => 'Il est temps de partir, {{player_name}}. Trois chemins quittent le Fanal, et je ne vous en imposerai aucun. Sachez seulement qu\'un voyage prend du temps réel : c\'est la première attente que ce monde vous demandera.',
                'choices' => [
                    ['text' => 'Où aller en premier ?', 'action' => 'next', 'next' => 10],
                    ['text' => 'Je vais explorer', 'action' => 'close'],
                ],
            ],
            // 5 — L'expedition (arc 10)
            [
                'text' => 'Une dernière chose, {{player_name}}, et c\'est celle qu\'on oublie. Avant de fermer, envoyez-vous en expédition : votre personnage travaillera sans vous, et quelque chose vous attendra au retour.',
                'choices' => [
                    ['text' => 'Combien de temps ?', 'action' => 'next', 'next' => 11],
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
                'text' => 'Un parchemin ouvre un arbre — c\'est tout ce qu\'il fait, et il se vend à tout le monde au même prix. L\'arbre ouvert, vous y prenez le nœud qui autorise votre arme. Sans ce nœud, l\'arme reste dans le sac : ce n\'est pas une punition, c\'est le sens du geste.',
                'choices' => [
                    ['text' => 'Merci !', 'action' => 'close'],
                ],
            ],
            // 8 — Detail: Combat help
            [
                'text' => 'Une matéria est un geste appris qu\'on porte sur soi. Elle ne s\'équipe pas : elle s\'accorde — un nœud dans votre arbre — puis se sertit dans un emplacement de votre équipement. C\'est la seule façon de lancer un sort. Iris vend des potions de soin, au cas où.',
                'choices' => [
                    ['text' => 'Compris, merci !', 'action' => 'close'],
                ],
            ],
            // 9 — Detail: Inventory help
            [
                'text' => 'Celui que vous voudrez : les cinq se récoltent aux portes du Fanal, et aucun n\'en ferme un autre. Prenez celui dont le geste vous plaît — vous y passerez vos semaines. Théodore garde vos affaires si le sac déborde.',
                'choices' => [
                    ['text' => 'Bien noté !', 'action' => 'close'],
                ],
            ],
            // 10 — Detail: Quest help
            [
                'text' => 'Là où vous avez travaillé, on vous connaît déjà un peu. Suivez ça. Oriane, à l\'est, garde les demandes des environs si vous préférez qu\'on vous dise où aller.',
                'choices' => [
                    ['text' => 'J\'y vais, merci !', 'action' => 'close'],
                ],
            ],
            // 11 — Detail: Craft help
            [
                'text' => 'Une heure, quatre, ou douze — à vous. La plus courte suffit pour comprendre. Ce monde continue de tourner quand vous n\'êtes pas là, et c\'est voulu : personne ne devrait avoir à rester devant.',
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
            // ECO-02 : les zones doivent exister avant cette fixture, sinon
            // `WorldEntityZoneListener` ne trouve aucune zone a rattacher et
            // l'entite reste hors du graphe — invisible depuis l'ecran de zone.
            ZoneGraphFixtures::class,
        ];
    }
}
