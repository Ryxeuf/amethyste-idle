<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Pnj;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * PNJs du Marais Brumeux — zone lvl 12-20.
 *
 * 2 PNJ : voyante des marais, pecheur solitaire.
 */
class MaraisPnjFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $marais = $this->getReference('map_6', Map::class);

        $pnjs = $this->getMaraisPnjs();

        foreach ($pnjs as $index => $data) {
            $pnj = new Pnj();
            $pnj->setName($data['name']);
            $pnj->setLife(10);
            $pnj->setMaxLife(10);
            $pnj->setMap($marais);
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
            $this->addReference('marais_pnj_' . $index, $pnj);
        }

        $manager->flush();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMaraisPnjs(): array
    {
        return [
            // 0 — Morwenna la Voyante (entree du marais)
            [
                'name' => 'Morwenna la Voyante',
                'coordinates' => '10.8',
                'classType' => 'mage',
                'portrait' => '/styles/images/portraits/mage.png',
                'dialog' => [
                    [
                        'text' => "Les brumes vous ont guide jusqu'a moi, etranger. Ce n'est pas un hasard. Le marais murmure des choses... des choses anciennes. Voulez-vous ecouter ?",
                        'choices' => [
                            [
                                'text' => 'Que disent les brumes ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Non, je ne fais que passer.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Le marais cache bien des secrets sous ses eaux sombres. Des creatures rodent, certaines aussi vieilles que le monde. Et au coeur de cet endroit, quelque chose pulse... une energie que je n'avais pas ressentie depuis longtemps.",
                        'choices' => [
                            [
                                'text' => "C'est inquietant. Merci de l'avertissement.",
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],

            // 1 — Aldric le Pecheur solitaire (bord d'etang, marchand)
            [
                'name' => 'Aldric le Pecheur solitaire',
                'coordinates' => '30.35',
                'classType' => 'merchant',
                'portrait' => '/styles/images/portraits/merchant.png',
                'shopItems' => ['life-potion', 'antidote', 'healing-potion-small', 'fishing-rod-iron'],
                'opensAt' => 0,
                'closesAt' => 24,
                'shopStock' => [
                    'life-potion' => ['stock' => 6, 'maxStock' => 6, 'restockInterval' => 3600],
                    'antidote' => ['stock' => 8, 'maxStock' => 8, 'restockInterval' => 3600],
                    'healing-potion-small' => ['stock' => 4, 'maxStock' => 4, 'restockInterval' => 3600],
                    'fishing-rod-iron' => ['stock' => 1, 'maxStock' => 1, 'restockInterval' => 14400],
                ],
                'dialog' => [
                    [
                        'text' => "Chut... vous allez faire fuir les poissons. Enfin, ce qu'il en reste. Depuis que les brumes se sont epaissies, meme les carpes se font rares. Vous cherchez quelque chose ?",
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Parlez-moi du marais',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Non merci.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Ce marais etait paisible autrefois. Mais depuis quelques semaines, les brumes ne se dissipent plus, meme en plein jour. Et la nuit... des lueurs etranges apparaissent pres de l'ilot central. Morwenna dit que c'est un signe. Moi, je dis juste que c'est mauvais pour la peche.",
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Merci pour les infos.',
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
