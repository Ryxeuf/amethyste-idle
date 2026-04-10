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
 * 5 PNJ : ermite ancien, guide de montagne, forgeron itinérant, guérisseuse mystique, éclaireur.
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

            // 2 — Tormund le Forgeron itinérant (camp de base, marchand armes/armures)
            [
                'name' => 'Tormund le Forgeron itinérant',
                'coordinates' => '30.42',
                'classType' => 'blacksmith',
                'portrait' => '/styles/images/portraits/blacksmith.png',
                'shopItems' => ['iron-sword', 'leather-armor', 'leather-helmet', 'wooden-shield', 'healing-potion-medium'],
                'opensAt' => 5,
                'closesAt' => 22,
                'shopStock' => [
                    'iron-sword' => ['stock' => 2, 'maxStock' => 2, 'restockInterval' => 14400],
                    'leather-armor' => ['stock' => 2, 'maxStock' => 2, 'restockInterval' => 14400],
                    'leather-helmet' => ['stock' => 3, 'maxStock' => 3, 'restockInterval' => 7200],
                    'wooden-shield' => ['stock' => 2, 'maxStock' => 2, 'restockInterval' => 14400],
                    'healing-potion-medium' => ['stock' => 3, 'maxStock' => 3, 'restockInterval' => 7200],
                ],
                'dialog' => [
                    [
                        'text' => "Hé là, {{player_name}} ! Je suis Tormund, forgeron de métier. J'ai quitté le village pour forger ici, à même la roche volcanique. Le feu du dragon chauffe ma forge mieux que n'importe quel soufflet !",
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Vous forgez avec le feu du dragon ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Peut-être plus tard.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Pas directement, bien sûr — je ne suis pas fou ! Mais les fissures volcaniques crachent une chaleur constante. Parfaite pour tremper l'acier. Les armes forgées ici résistent aux griffes de griffon comme aucune autre. Si vous comptez monter plus haut, équipez-vous correctement.",
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Merci du conseil.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],

            // 3 — Ysolde la Guérisseuse mystique (grotte abritée, soigneuse)
            [
                'name' => 'Ysolde la Guérisseuse',
                'coordinates' => '18.38',
                'classType' => 'healer',
                'portrait' => '/styles/images/portraits/healer.png',
                'shopItems' => ['healing-potion-small', 'healing-potion-medium', 'antidote', 'energy-potion-small'],
                'opensAt' => 0,
                'closesAt' => 24,
                'shopStock' => [
                    'healing-potion-small' => ['stock' => 8, 'maxStock' => 8, 'restockInterval' => 3600],
                    'healing-potion-medium' => ['stock' => 4, 'maxStock' => 4, 'restockInterval' => 7200],
                    'antidote' => ['stock' => 6, 'maxStock' => 6, 'restockInterval' => 3600],
                    'energy-potion-small' => ['stock' => 4, 'maxStock' => 4, 'restockInterval' => 7200],
                ],
                'dialog' => [
                    [
                        'text' => 'Entrez, entrez... la grotte vous protégera du vent. Je suis Ysolde. Les blessures de la montagne sont traîtresses — le froid engourdit la douleur, et quand on la sent enfin, il est souvent trop tard.',
                        'choices' => [
                            [
                                'text' => 'Voir la boutique',
                                'action' => 'open_shop',
                                'datas' => [],
                            ],
                            [
                                'text' => 'Comment survivez-vous ici seule ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Merci, je vais bien.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Seule ? Les esprits de la montagne me tiennent compagnie. Et puis, Aldric passe de temps en temps. Les herbes qui poussent près des sources chaudes ont des vertus qu'on ne trouve nulle part ailleurs. Je prépare mes remèdes avec ce que la montagne m'offre.",
                        'conditional_next' => [
                            [
                                'next' => 2,
                                'next_condition' => [
                                    'domain_xp_min' => ['5' => 100],
                                ],
                            ],
                        ],
                        'choices' => [
                            [
                                'text' => 'Fascinant. Merci Ysolde.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Ah, je vois que vous avez de l'expérience en herboristerie ! Les sources chaudes en altitude regorgent de plantes rares. Si vous en trouvez, apportez-les-moi — je pourrais vous préparer des remèdes bien plus puissants que ce que j'ai en stock.",
                        'choices' => [
                            [
                                'text' => 'Je garderai l\'œil ouvert.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],

            // 4 — Kaelen l'Éclaireur (sentier avancé, lore & conseils combat)
            [
                'name' => "Kaelen l'Éclaireur",
                'coordinates' => '20.30',
                'classType' => 'guard',
                'portrait' => '/styles/images/portraits/knight.png',
                'dialog' => [
                    [
                        'text' => 'Halte ! Je suis Kaelen, éclaireur de la garde de Ventombre. Au-delà de ce point, seuls les combattants aguerris survivent. Les griffons patrouillent en meute, et les gargouilles sont quasi invulnérables aux armes ordinaires.',
                        'choices' => [
                            [
                                'text' => 'Comment vaincre les gargouilles ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Parlez-moi du Dragon ancestral.',
                                'action' => 'next',
                                'datas' => ['next' => 2],
                            ],
                            [
                                'text' => 'Je suis prêt.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Les gargouilles résistent aux coups physiques — leur peau est de pierre. Il faut utiliser la magie élémentaire : le feu fissure leur carapace, l'eau les ralentit. Une materia de feu ou de métal fera des merveilles. Et ne les affrontez jamais à plus de deux à la fois.",
                        'choices' => [
                            [
                                'text' => 'Et le Dragon ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Merci pour ces conseils.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => "Le Dragon ancestral... personne ne l'a vaincu. Il maîtrise le feu et la terre, et son souffle peut balayer une escouade entière. On dit que seul un guerrier portant l'Éclat de Ventombre pourrait l'affronter. Un cristal ancien, quelque part dans les grottes supérieures... mais je n'y suis jamais allé.",
                        'choices' => [
                            [
                                'text' => 'Je trouverai cet éclat.',
                                'action' => 'close',
                            ],
                            [
                                'text' => 'Merci Kaelen.',
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
