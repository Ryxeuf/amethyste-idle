<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Pnj;
use App\Entity\App\ShopStock;
use App\Entity\Game\Item;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MerchantFixtures extends Fixture implements DependentFixtureInterface
{
    private const MERCHANTS = [
        [
            'name' => 'Roland le Marchand d\'armes',
            'classType' => 'merchant',
            'coordinates' => '5.3',
            'shopItems' => ['short-sword', 'long-sword'],
            'dialog' => [
                ['text' => 'Bienvenue dans mon echoppe ! Je vends les meilleures armes de la region.', 'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'data' => []],
                    ['text' => 'Non merci', 'action' => 'close'],
                ]],
            ],
        ],
        [
            'name' => 'Marguerite l\'Armuriere',
            'classType' => 'merchant',
            'coordinates' => '7.3',
            'shopItems' => ['leather-armor', 'leather-boots', 'leather-hat'],
            'dialog' => [
                ['text' => 'Vous cherchez une bonne protection ? Mes armures sont renforcees a la main.', 'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'data' => []],
                    ['text' => 'Non merci', 'action' => 'close'],
                ]],
            ],
        ],
        [
            'name' => 'Augustin l\'Apothicaire',
            'classType' => 'merchant',
            'coordinates' => '3.5',
            'shopItems' => ['life-potion'],
            'dialog' => [
                ['text' => 'Mes potions guerissent tous les maux ! Que puis-je pour vous ?', 'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'data' => []],
                    ['text' => 'Non merci', 'action' => 'close'],
                ]],
            ],
            'limitedStock' => [
                'life-potion' => ['max' => 10, 'restock' => 30],
            ],
        ],
        [
            'name' => 'Genevieve la Marchande generale',
            'classType' => 'merchant',
            'coordinates' => '8.6',
            'shopItems' => ['life-potion', 'pickaxe-bronze', 'sickle-bronze', 'fishing-rod-bronze', 'skinning-knife-bronze'],
            'dialog' => [
                ['text' => 'Bonjour ! J\'ai de tout pour les aventuriers en herbe. Jetez un oeil !', 'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'data' => []],
                    ['text' => 'Non merci', 'action' => 'close'],
                ]],
            ],
        ],
        [
            'name' => 'Marcel le Forgeron-Marchand',
            'classType' => 'blacksmith',
            'coordinates' => '12.4',
            'shopItems' => ['pickaxe-bronze', 'pickaxe-iron', 'pickaxe-steel'],
            'dialog' => [
                ['text' => 'Hola ! Besoin d\'outils solides ? Mes pioches sont les meilleures du pays !', 'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'data' => []],
                    ['text' => 'Non merci', 'action' => 'close'],
                ]],
            ],
            'limitedStock' => [
                'pickaxe-steel' => ['max' => 3, 'restock' => 60],
            ],
        ],
        [
            'name' => 'Colette l\'Herboriste-Marchande',
            'classType' => 'merchant',
            'coordinates' => '15.7',
            'shopItems' => ['sickle-bronze', 'sickle-iron', 'sickle-steel'],
            'dialog' => [
                ['text' => 'Les plantes ne se cueillent pas a la main, jeune aventurier ! Prenez une faucille.', 'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'data' => []],
                    ['text' => 'Non merci', 'action' => 'close'],
                ]],
            ],
        ],
        [
            'name' => 'Gaston le Pecheur-Marchand',
            'classType' => 'merchant',
            'coordinates' => '18.2',
            'shopItems' => ['fishing-rod-bronze', 'fishing-rod-iron'],
            'dialog' => [
                ['text' => 'Ah, un amateur de peche ! J\'ai ce qu\'il vous faut.', 'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'data' => []],
                    ['text' => 'Non merci', 'action' => 'close'],
                ]],
            ],
        ],
        [
            'name' => 'Fernand le Marchand de raretés',
            'classType' => 'noble',
            'coordinates' => '10.10',
            'shopItems' => ['pickaxe-mithril', 'sickle-mithril', 'fishing-rod-mithril', 'skinning-knife-mithril'],
            'dialog' => [
                ['text' => 'Seuls les plus riches peuvent se permettre mes articles... Voyons si vous etes digne.', 'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'data' => []],
                    ['text' => 'Peut-etre plus tard', 'action' => 'close'],
                ]],
            ],
            'limitedStock' => [
                'pickaxe-mithril' => ['max' => 1, 'restock' => 120],
                'sickle-mithril' => ['max' => 1, 'restock' => 120],
                'fishing-rod-mithril' => ['max' => 1, 'restock' => 120],
                'skinning-knife-mithril' => ['max' => 1, 'restock' => 120],
            ],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $map = $this->getReference('map_1', Map::class);
        $itemRepo = $manager->getRepository(Item::class);

        foreach (self::MERCHANTS as $i => $merchantData) {
            $pnj = new Pnj();
            $pnj->setName($merchantData['name']);
            $pnj->setClassType($merchantData['classType']);
            $pnj->setLife(10);
            $pnj->setMaxLife(10);
            $pnj->setMap($map);
            $pnj->setCoordinates($merchantData['coordinates']);
            $pnj->setShopItems($merchantData['shopItems']);
            $pnj->setCreatedAt(new \DateTime());
            $pnj->setUpdatedAt(new \DateTime());

            // Set dialog with pnj_id reference for open_shop action
            $dialog = $merchantData['dialog'];

            $pnj->setDialog($dialog);

            $manager->persist($pnj);
            $manager->flush(); // Flush to get the PNJ ID

            // Update dialog with actual pnj_id
            foreach ($dialog as &$step) {
                if (isset($step['choices'])) {
                    foreach ($step['choices'] as &$choice) {
                        if (($choice['action'] ?? '') === 'open_shop') {
                            $choice['data'] = ['pnj_id' => $pnj->getId()];
                        }
                    }
                }
            }
            $pnj->setDialog($dialog);

            // Create limited stock entries
            if (isset($merchantData['limitedStock'])) {
                foreach ($merchantData['limitedStock'] as $slug => $stockConfig) {
                    $item = $itemRepo->findOneBy(['slug' => $slug]);
                    if ($item) {
                        $stock = new ShopStock();
                        $stock->setPnj($pnj);
                        $stock->setItem($item);
                        $stock->setMaxStock($stockConfig['max']);
                        $stock->setCurrentStock($stockConfig['max']);
                        $stock->setRestockIntervalMinutes($stockConfig['restock']);
                        $stock->setLastRestockAt(new \DateTime());
                        $stock->setCreatedAt(new \DateTime());
                        $stock->setUpdatedAt(new \DateTime());
                        $manager->persist($stock);
                    }
                }
            }

            $this->addReference('merchant_' . $i, $pnj);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
            \App\DataFixtures\Game\ItemFixtures::class,
        ];
    }
}
