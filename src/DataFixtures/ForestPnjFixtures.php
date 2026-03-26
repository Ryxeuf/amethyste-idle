<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Pnj;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * PNJs de la Forêt des murmures — zone lvl 5-15.
 *
 * 3 PNJ : garde forestier, herboriste, ermite.
 */
class ForestPnjFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $forest = $this->getReference('map_3', Map::class);

        $pnjs = $this->getForestPnjs();

        foreach ($pnjs as $index => $data) {
            $pnj = new Pnj();
            $pnj->setName($data['name']);
            $pnj->setLife(10);
            $pnj->setMaxLife(10);
            $pnj->setMap($forest);
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
            $this->addReference('forest_pnj_' . $index, $pnj);
        }

        $manager->flush();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getForestPnjs(): array
    {
        return [
            // 0 — Sylvain le Garde forestier (clairière centrale)
            [
                'name' => 'Sylvain le Garde forestier',
                'coordinates' => '28.28',
                'classType' => 'guard',
                'portrait' => '/styles/images/portraits/guard.png',
                'dialog' => [
                    [
                        'text' => 'Halte, voyageur. Bienvenue dans la Forêt des murmures. Les bois sont dangereux pour les imprudents, mais recèlent bien des trésors pour qui sait chercher.',
                        'choices' => [
                            [
                                'text' => 'Que peut-on trouver ici ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Je suis de passage. Au revoir.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'La forêt abrite de nombreuses créatures : slimes, araignées, serpents venimeux... et des esprits nocturnes comme les feux follets. Plus au nord, un ermite vit reclus dans une clairière. Il connaît les secrets de ces bois mieux que quiconque.',
                        'choices' => [
                            [
                                'text' => 'Merci pour les informations.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],

            // 1 — Elara l'Herboriste (clairière centrale, marchande)
            [
                'name' => "Elara l'Herboriste",
                'coordinates' => '32.32',
                'classType' => 'healer',
                'portrait' => '/styles/images/portraits/healer.png',
                'shopItems' => ['life-potion', 'healing-potion-small', 'antidote', 'energy-potion-small'],
                'opensAt' => 6,
                'closesAt' => 21,
                'shopStock' => [
                    'life-potion' => ['stock' => 8, 'maxStock' => 8, 'restockInterval' => 3600],
                    'healing-potion-small' => ['stock' => 5, 'maxStock' => 5, 'restockInterval' => 3600],
                    'antidote' => ['stock' => 6, 'maxStock' => 6, 'restockInterval' => 3600],
                    'energy-potion-small' => ['stock' => 4, 'maxStock' => 4, 'restockInterval' => 7200],
                ],
                'dialog' => [
                    [
                        'text' => 'Les herbes de cette forêt possèdent des propriétés curatives remarquables. Je prépare des potions à partir de ce que je récolte ici. Puis-je vous aider ?',
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Parlez-moi de la forêt',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Non merci.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "La rivière qui traverse la forêt regorge de poissons. Et si vous savez reconnaître les plantes, vous trouverez de la menthe, de la sauge et même de la mandragore près de la clairière nord. Attention toutefois aux loups qui rôdent à l'est.",
                        'choices' => [
                            [
                                'text' => 'Merci du conseil !',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],

            // 2 — Thadeus l'Ermite (clairière nord)
            [
                'name' => "Thadeus l'Ermite",
                'coordinates' => '30.6',
                'classType' => 'mage',
                'portrait' => '/styles/images/portraits/mage.png',
                'dialog' => [
                    [
                        'text' => "Hmm... encore un visiteur. Les arbres m'ont prévenu de votre arrivée. Ils murmurent, vous savez. C'est pour cela que cette forêt porte ce nom.",
                        'choices' => [
                            [
                                'text' => 'Les arbres murmurent ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Je ne voulais pas vous déranger.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'Oh oui. Cette forêt est ancienne, bien plus ancienne que le Village de Lumière. Elle garde en mémoire les échos du passé. Parfois, la nuit, on peut apercevoir des esprits errants entre les arbres... Les feux follets ne sont pas de simples créatures, ce sont des fragments de souvenirs oubliés.',
                        'choices' => [
                            [
                                'text' => "C'est fascinant. Merci, vieil homme.",
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
