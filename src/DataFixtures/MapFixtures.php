<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\World;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MapFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Carte de test : l'id Doctrine sera en général 1 sur base vide.
        // Les fichiers data/map/tag_{id}_* et map_{id}_* doivent exister pour ce même id
        // (ex. tag_1_20250306151305 copié depuis la carte monde équivalente).
        $map = new Map();
        $map->setName('Carte de test');
        $map->setWorld($this->getReference('world_1', World::class));
        $map->setAreaWidth(60);
        $map->setAreaHeight(60);
        $map->setCreatedAt(new \DateTime());
        $map->setUpdatedAt(new \DateTime());

        $manager->persist($map);
        $this->addReference('map_1', $map);

        // Village central — hub principal entre les zones (zone safe, aucun monstre)
        $village = new Map();
        $village->setName('Village de Lumière');
        $village->setWorld($this->getReference('world_1', World::class));
        $village->setAreaWidth(40);
        $village->setAreaHeight(40);
        $village->setCreatedAt(new \DateTime());
        $village->setUpdatedAt(new \DateTime());

        $manager->persist($village);
        $this->addReference('map_2', $village);

        // Forêt des murmures — zone lvl 5-15, arbres, clairières, rivière
        $forest = new Map();
        $forest->setName('Forêt des murmures');
        $forest->setWorld($this->getReference('world_1', World::class));
        $forest->setAreaWidth(60);
        $forest->setAreaHeight(60);
        $forest->setCreatedAt(new \DateTime());
        $forest->setUpdatedAt(new \DateTime());

        $manager->persist($forest);
        $this->addReference('map_3', $forest);

        // Mines profondes — zone lvl 10-25, tunnels, salles, filons, boss
        $mines = new Map();
        $mines->setName('Mines profondes');
        $mines->setWorld($this->getReference('world_1', World::class));
        $mines->setAreaWidth(60);
        $mines->setAreaHeight(30);
        $mines->setCreatedAt(new \DateTime());
        $mines->setUpdatedAt(new \DateTime());

        $manager->persist($mines);
        $this->addReference('map_4', $mines);

        // Marais Brumeux — zone lvl 8-18, brume, eaux stagnantes, créatures corrompues
        $swamp = new Map();
        $swamp->setName('Marais Brumeux');
        $swamp->setWorld($this->getReference('world_1', World::class));
        $swamp->setAreaWidth(50);
        $swamp->setAreaHeight(50);
        $swamp->setCreatedAt(new \DateTime());
        $swamp->setUpdatedAt(new \DateTime());

        $manager->persist($swamp);
        $this->addReference('map_5', $swamp);

        // Crête de Ventombre — zone montagneuse lvl 15-25, pics, grottes, vents violents
        $mountain = new Map();
        $mountain->setName('Crête de Ventombre');
        $mountain->setWorld($this->getReference('world_1', World::class));
        $mountain->setAreaWidth(50);
        $mountain->setAreaHeight(50);
        $mountain->setCreatedAt(new \DateTime());
        $mountain->setUpdatedAt(new \DateTime());

        $manager->persist($mountain);
        $this->addReference('map_6', $mountain);

        // Donjon : Racines de la foret — carte instanciee pour le donjon
        $dungeonRoots = new Map();
        $dungeonRoots->setName('Racines de la foret (donjon)');
        $dungeonRoots->setWorld($this->getReference('world_1', World::class));
        $dungeonRoots->setAreaWidth(20);
        $dungeonRoots->setAreaHeight(20);
        $dungeonRoots->setCreatedAt(new \DateTime());
        $dungeonRoots->setUpdatedAt(new \DateTime());

        $manager->persist($dungeonRoots);
        $this->addReference('map_dungeon_racines', $dungeonRoots);

        // Donjon : Le Nexus de la Convergence — donjon final Acte 3 (tache 94)
        $dungeonConvergence = new Map();
        $dungeonConvergence->setName('Nexus de la Convergence (donjon)');
        $dungeonConvergence->setWorld($this->getReference('world_1', World::class));
        $dungeonConvergence->setAreaWidth(30);
        $dungeonConvergence->setAreaHeight(30);
        $dungeonConvergence->setCreatedAt(new \DateTime());
        $dungeonConvergence->setUpdatedAt(new \DateTime());

        $manager->persist($dungeonConvergence);
        $this->addReference('map_dungeon_convergence', $dungeonConvergence);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            WorldFixtures::class,
        ];
    }
}
