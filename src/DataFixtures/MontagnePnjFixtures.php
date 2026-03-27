<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Pnj;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * PNJs de la Crête de Ventombre — zone montagneuse lvl 15-25.
 *
 * 2 PNJ : ermite ancien, guide de montagne.
 */
class MontagnePnjFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $mountain = $this->getReference('map_6', Map::class);

        $pnjs = $this->getMontagnePnjs();

        foreach ($pnjs as $index => $data) {
            $pnj = new Pnj();
            $pnj->setName($data['name']);
            $pnj->setLife(10);
            $pnj->setMaxLife(10);
            $pnj->setMap($mountain);
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
            $this->addReference('montagne_pnj_' . $index, $pnj);
        }

        $manager->flush();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMontagnePnjs(): array
    {
        return [
            // 0 — Aldric l'Ancien (ermite du sommet)
            [
                'name' => 'Aldric l\'Ancien',
                'coordinates' => '25.40',
                'classType' => 'mage',
                'portrait' => '/styles/images/portraits/sage.png',
                'dialog' => [
                    [
                        'text' => "Le vent porte vos pas jusqu'ici... Vous n'êtes pas un randonneur ordinaire. Je suis Aldric. Je vis sur cette crête depuis plus longtemps que les pierres s'en souviennent.",
                        'choices' => [
                            [
                                'text' => 'Que savez-vous de cette montagne ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Je ne fais que passer.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'La Crête de Ventombre est le toit du monde. Les vents qui la balaient portent les échos de l\'ancien temps. Un dragon sommeille près du sommet — ne le sous-estimez pas. Et au-delà de sa tanière, on dit qu\'un cristal brille depuis l\'aube des temps...',
                        'choices' => [
                            [
                                'text' => 'Merci pour ces mises en garde.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],

            // 1 — Seren la Guide (base de la montagne, marchande)
            [
                'name' => 'Seren la Guide',
                'coordinates' => '25.45',
                'classType' => 'hunter',
                'portrait' => '/styles/images/portraits/guard.png',
                'shopItems' => ['life-potion', 'healing-potion-small', 'antidote'],
                'opensAt' => 6,
                'closesAt' => 20,
                'shopStock' => [
                    'life-potion' => ['stock' => 8, 'maxStock' => 8, 'restockInterval' => 3600],
                    'healing-potion-small' => ['stock' => 5, 'maxStock' => 5, 'restockInterval' => 3600],
                    'antidote' => ['stock' => 5, 'maxStock' => 5, 'restockInterval' => 3600],
                ],
                'dialog' => [
                    [
                        'text' => 'Bienvenue sur la Crête de Ventombre ! Je suis Seren, guide de montagne. Les sentiers sont traîtres ici — griffons dans les airs, gargouilles sur les falaises, et ne parlons pas du dragon au sommet. Besoin d\'équipement ?',
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Parlez-moi de la montagne',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Non merci.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'La Crête de Ventombre culmine au-dessus des nuages. Plus vous montez, plus les créatures sont redoutables. Les griffons chassent en altitude, les gargouilles nichent dans les falaises, et les élémentaires de feu jaillissent des fissures volcaniques. Tout en haut, le Dragon ancestral règne en maître. Peu de gens en reviennent.',
                        'choices' => [
                            [
                                'text' => 'Merci du conseil !',
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
