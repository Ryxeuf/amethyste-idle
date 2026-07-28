<?php

namespace App\DataFixtures;

use App\GameEngine\Settlement\SettlementSeeder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Foyers du monde livre (FOY-01).
 *
 * Meme parti pris que `ZoneGraphFixtures` : la definition vit dans
 * `config/game/settlements.yaml`, cette fixture ne fait que la rejouer. Ajouter
 * un foyer = editer le YAML, pas ce fichier.
 */
class SettlementFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly SettlementSeeder $seeder,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->seeder->seed();
    }

    public function getDependencies(): array
    {
        return [
            ZoneGraphFixtures::class,
        ];
    }
}
