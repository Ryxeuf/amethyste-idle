<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Pnj;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * PNJs du Marais Brumeux — zone lvl 8-18.
 *
 * 3 PNJ : voyante des brumes, herboriste, chasseur.
 */
class MaraisPnjFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $swamp = $this->getReference('map_5', Map::class);

        $pnjs = $this->getMaraisPnjs();

        foreach ($pnjs as $index => $data) {
            $pnj = new Pnj();
            $pnj->setName($data['name']);
            $pnj->setLife(10);
            $pnj->setMaxLife(10);
            $pnj->setMap($swamp);
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
            // 0 — Morwen la Voyante (lisière du marais)
            [
                'name' => 'Morwen la Voyante',
                'coordinates' => '10.8',
                'classType' => 'mage',
                'portrait' => '/styles/images/portraits/mage.png',
                'dialog' => [
                    [
                        'text' => "Les brumes vous ont laissé passer... c'est qu'elles ont quelque chose à vous montrer. Je suis Morwen. Je lis dans les vapeurs du marais depuis bien longtemps.",
                        'choices' => [
                            [
                                'text' => 'Que voyez-vous dans les brumes ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Je ne fais que passer.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'Ce marais est ancien, bien plus que les villages alentour. Des esprits y errent, prisonniers de leur propre chagrin. Les banshees que vous croiserez ne sont pas de simples monstres — ce sont des âmes brisées. Et au cœur des eaux stagnantes dort quelque chose de plus ancien encore...',
                        'choices' => [
                            [
                                'text' => 'Merci pour ces mises en garde.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],

            // 1 — Fergus l'Herboriste des marais (clairière centrale, marchand)
            [
                'name' => "Fergus l'Herboriste",
                'coordinates' => '25.25',
                'classType' => 'healer',
                'portrait' => '/styles/images/portraits/healer.png',
                'shopItems' => ['antidote', 'life-potion', 'healing-potion-small', 'poisonous-mushroom'],
                'opensAt' => 5,
                'closesAt' => 22,
                'shopStock' => [
                    'antidote' => ['stock' => 10, 'maxStock' => 10, 'restockInterval' => 3600],
                    'life-potion' => ['stock' => 6, 'maxStock' => 6, 'restockInterval' => 3600],
                    'healing-potion-small' => ['stock' => 5, 'maxStock' => 5, 'restockInterval' => 3600],
                    'poisonous-mushroom' => ['stock' => 8, 'maxStock' => 8, 'restockInterval' => 7200],
                ],
                'dialog' => [
                    [
                        'text' => 'Les plantes de ce marais sont redoutables pour les novices, mais entre de bonnes mains, elles guérissent presque tout. Je suis Fergus, herboriste depuis trois générations. Besoin de quelque chose ?',
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
                        'text' => "Le Marais Brumeux est riche en champignons vénéneux et en racines noueuses. Les araignées tissent leurs toiles entre les arbres morts, et les ochus se terrent dans les eaux profondes. Si vous cherchez des ingrédients rares, c'est l'endroit idéal — mais ne vous éloignez pas trop du sentier.",
                        'choices' => [
                            [
                                'text' => 'Merci du conseil !',
                                'action' => 'close',
                            ],
                        ],
                    ],
                ],
            ],

            // 2 — Bran le Chasseur (poste avancé sud)
            [
                'name' => 'Bran le Chasseur',
                'coordinates' => '40.38',
                'classType' => 'guard',
                'portrait' => '/styles/images/portraits/guard.png',
                'dialog' => [
                    [
                        'text' => "Vous êtes courageux de venir jusqu'ici. Je suis Bran, chasseur de prime. Le marais regorge de créatures dangereuses — zombies errants, araignées géantes, et ces maudites banshees dont les cris glacent le sang.",
                        'choices' => [
                            [
                                'text' => 'Des conseils pour les affronter ?',
                                'action' => 'next',
                            ],
                            [
                                'text' => 'Bonne chasse.',
                                'action' => 'close',
                            ],
                        ],
                    ],
                    [
                        'text' => 'Les zombies sont lents mais résistants. Les araignées empoisonnent — ayez toujours des antidotes. Quant aux banshees, elles attaquent à distance avec des ondes de choc. Et les ochus... ne les sous-estimez jamais, ils se régénèrent. Les profondeurs du marais cachent bien pire encore, croyez-moi.',
                        'choices' => [
                            [
                                'text' => 'Merci pour les conseils.',
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
