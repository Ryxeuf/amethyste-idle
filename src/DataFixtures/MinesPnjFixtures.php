<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Pnj;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * PNJs des Mines profondes — zone lvl 10-25.
 *
 * 5 PNJ : contremaître mineur, ingénieure mécanicienne, marchand souterrain, guérisseuse de galerie, vieux prospecteur.
 */
class MinesPnjFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $mines = $this->getReference('map_4', Map::class);

        $pnjs = $this->getMinesPnjs();

        foreach ($pnjs as $index => $data) {
            $pnj = new Pnj();
            $pnj->setName($data['name']);
            $pnj->setLife(10);
            $pnj->setMaxLife(10);
            $pnj->setMap($mines);
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
            $this->addReference('mines_pnj_' . $index, $pnj);
        }

        $manager->flush();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMinesPnjs(): array
    {
        return [
            // 0 — Grimmur le Contremaître (entrée des mines)
            [
                'name' => 'Grimmur le Contremaître',
                'coordinates' => '6.26',
                'classType' => 'guard',
                'portrait' => '/styles/images/portraits/guard.png',
                'dialog' => [
                    [
                        'text' => "Bienvenue dans les Mines profondes. Autrefois, elles fournissaient tout le minerai du royaume. Aujourd'hui, seuls les plus courageux osent s'y aventurer.",
                        'choices' => [
                            [
                                'text' => "Qu'est-ce qui a changé ?",
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Je ne fais que passer.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'Les golems se sont réveillés et les automates ont perdu leur contrôle. Plus vous descendez, plus les créatures sont dangereuses. Au fond se cache le Seigneur de la Forge, un ancien gardien devenu fou. Soyez prudent.',
                        'choices' => [
                            [
                                'text' => 'Merci pour les avertissements.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],

            // 1 — Hilda l'Ingénieure (tunnel central, marchande d'équipement)
            [
                'name' => "Hilda l'Ingénieure",
                'coordinates' => '25.16',
                'classType' => 'blacksmith',
                'portrait' => '/styles/images/portraits/blacksmith.png',
                'shopItems' => ['life-potion', 'healing-potion-small', 'energy-potion-small', 'pickaxe-iron'],
                'opensAt' => 0,
                'closesAt' => 24,
                'shopStock' => [
                    'life-potion' => ['stock' => 6, 'maxStock' => 6, 'restockInterval' => 3600],
                    'healing-potion-small' => ['stock' => 4, 'maxStock' => 4, 'restockInterval' => 3600],
                    'energy-potion-small' => ['stock' => 3, 'maxStock' => 3, 'restockInterval' => 7200],
                    'pickaxe-iron' => ['stock' => 2, 'maxStock' => 2, 'restockInterval' => 14400],
                ],
                'dialog' => [
                    [
                        'text' => 'Ah, un visiteur ! Je suis Hilda, la dernière ingénieure encore en poste ici. Je répare ce que je peux et je vends du matériel de survie. Ça vous intéresse ?',
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Parlez-moi des mines',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Non merci.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Les filons les plus riches se trouvent en profondeur : fer et cuivre près de l'entrée, argent et or plus loin, et si vous avez de la chance, des rubis au fond. Mais les golems gardent jalousement ces richesses.",
                        'choices' => [
                            [
                                'text' => 'Je tenterai ma chance. Merci !',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],

            // 2 — Noric le Marchand souterrain (salle profonde)
            [
                'name' => 'Noric le Marchand souterrain',
                'coordinates' => '48.8',
                'classType' => 'merchant',
                'portrait' => '/styles/images/portraits/merchant.png',
                'shopItems' => ['healing-potion-small', 'antidote', 'ore-iron', 'ore-copper'],
                'opensAt' => 0,
                'closesAt' => 24,
                'shopStock' => [
                    'healing-potion-small' => ['stock' => 5, 'maxStock' => 5, 'restockInterval' => 3600],
                    'antidote' => ['stock' => 4, 'maxStock' => 4, 'restockInterval' => 3600],
                    'ore-iron' => ['stock' => 10, 'maxStock' => 10, 'restockInterval' => 7200],
                    'ore-copper' => ['stock' => 10, 'maxStock' => 10, 'restockInterval' => 7200],
                ],
                'dialog' => [
                    [
                        'text' => "Psst... un client ! Pas beaucoup de passage par ici, mais j'ai tout ce qu'il faut pour un mineur avisé. Potions, minerais bruts, tout y est.",
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Comment survivez-vous ici ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Pas intéressé.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Les automates ne m'embêtent pas, je connais leurs patrouilles par cœur. Et le Seigneur de la Forge est au fond, il ne sort jamais de sa salle. Tant qu'on reste discret, on est tranquille... plus ou moins.",
                        'choices' => [
                            [
                                'text' => 'Bonne continuation.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],

            // 3 — Agna la Guérisseuse de galerie (poste de soins, marchande potions)
            [
                'name' => 'Agna la Guérisseuse',
                'coordinates' => '15.20',
                'classType' => 'healer',
                'portrait' => '/styles/images/portraits/healer.png',
                'shopItems' => ['life-potion', 'healing-potion-small', 'healing-potion-medium', 'antidote'],
                'opensAt' => 0,
                'closesAt' => 24,
                'shopStock' => [
                    'life-potion' => ['stock' => 6, 'maxStock' => 6, 'restockInterval' => 3600],
                    'healing-potion-small' => ['stock' => 8, 'maxStock' => 8, 'restockInterval' => 3600],
                    'healing-potion-medium' => ['stock' => 3, 'maxStock' => 3, 'restockInterval' => 7200],
                    'antidote' => ['stock' => 6, 'maxStock' => 6, 'restockInterval' => 3600],
                ],
                'dialog' => [
                    [
                        'text' => "Ah, encore un blessé... Approchez, je suis Agna. Avant, j'étais médecin au village. Maintenant je soigne les mineurs et les aventuriers assez fous pour descendre ici. Les golems frappent fort, mais c'est le gaz des galeries profondes qui est le plus sournois.",
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Le gaz des galeries ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Je vais bien, merci.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Un gaz verdâtre qui suinte des parois profondes. Il empoisonne lentement — vous ne le sentez pas tout de suite, puis vos forces vous quittent d'un coup. Gardez toujours des antidotes sur vous. Et si vous voyez une lueur verte au plafond, faites demi-tour.",
                        'conditional_next' => [
                            [
                                'next' => 2,
                                'next_condition' => [
                                    'domain_xp_min' => ['3' => 50],
                                ],
                            ],
                        ],
                        'choices' => [
                            [
                                'text' => 'Compris, merci Agna.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Tiens, vous avez l'œil d'un mineur expérimenté ! Vous connaissez déjà les galeries, n'est-ce pas ? Dans ce cas, un conseil : les filons de mithril se trouvent près des nids de golems de cristal. Le risque en vaut la chandelle, croyez-moi.",
                        'choices' => [
                            [
                                'text' => "J'irai jeter un œil. Merci !",
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],

            // 4 — Durgan le Vieux Prospecteur (galerie reculée, lore & conseils)
            [
                'name' => 'Durgan le Vieux Prospecteur',
                'coordinates' => '35.5',
                'classType' => 'mage',
                'portrait' => '/styles/images/portraits/sage.png',
                'dialog' => [
                    [
                        'text' => "Ha ! Un visiteur dans mes galeries... Je suis Durgan. Ça fait quarante ans que je creuse ici. J'ai vu des choses que personne ne croirait — des cristaux qui chantent, des automates qui pleurent, et le Seigneur de la Forge lui-même...",
                        'choices' => [
                            [
                                'text' => 'Le Seigneur de la Forge ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Des cristaux qui chantent ?',
                                'action' => 'next',
                                'datas' => ['next' => 2],
                            ],
                            [
                                'text' => 'Bonne continuation, vieux fou.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Autrefois, c'était le gardien des mines — un automate géant créé par les anciens pour protéger les trésors enfouis. Mais quelque chose l'a corrompu. Maintenant, il détruit tout ce qui entre dans sa salle. Seuls les guerriers portant des armes de métal enchanté ont une chance. La synergie Métal et Feu, voilà ce qu'il faut.",
                        'choices' => [
                            [
                                'text' => 'Et les cristaux ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Merci pour ces informations.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Les cristaux résonnants... on les trouve dans les cavités les plus profondes. Ils émettent un son pur quand on les touche. Les anciens s'en servaient pour alimenter les automates. Si vous en trouvez un intact, gardez-le précieusement — les joailliers du village sauraient en faire quelque chose d'extraordinaire.",
                        'choices' => [
                            [
                                'text' => "Je garderai l'oreille tendue.",
                                'action' => 'close',
                            ],
                        ],
                    ],
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
