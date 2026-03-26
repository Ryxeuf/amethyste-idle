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
 * 3 PNJ : contremaître mineur, ingénieure mécanicienne, marchand souterrain.
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
        ];
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
        ];
    }
}
