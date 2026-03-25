<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Region;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RegionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $map1 = $this->getReference('map_1', Map::class);
        $map2 = $this->getReference('map_2', Map::class);

        // Région 1 : Plaines de l'Éveil (carte principale, contestable)
        $plaines = new Region();
        $plaines->setName('Plaines de l\'Éveil');
        $plaines->setSlug('plaines-eveil');
        $plaines->setDescription('Vastes plaines verdoyantes où les aventuriers font leurs premiers pas. Territoire disputé pour ses ressources abondantes.');
        $plaines->setIcon('🌾');
        $plaines->setTaxRate('0.0500');
        $plaines->setIsContestable(true);
        $plaines->setCapitalMap($map2);
        $plaines->setCreatedAt(new \DateTime());
        $plaines->setUpdatedAt(new \DateTime());
        $manager->persist($plaines);
        $this->addReference('region_plaines', $plaines);

        // Associer les maps à la région
        $map1->setRegion($plaines);
        $map2->setRegion($plaines);

        // Région 2 : Sanctuaire de Lumière (zone safe, non contestable)
        $sanctuaire = new Region();
        $sanctuaire->setName('Sanctuaire de Lumière');
        $sanctuaire->setSlug('sanctuaire-lumiere');
        $sanctuaire->setDescription('Zone protégée par les anciens. Aucune guilde ne peut en revendiquer le contrôle.');
        $sanctuaire->setIcon('✨');
        $sanctuaire->setTaxRate('0.0000');
        $sanctuaire->setIsContestable(false);
        $sanctuaire->setCreatedAt(new \DateTime());
        $sanctuaire->setUpdatedAt(new \DateTime());
        $manager->persist($sanctuaire);
        $this->addReference('region_sanctuaire', $sanctuaire);

        // Région 3 : Terres Sauvages (future zone d'expansion, contestable)
        $terresSauvages = new Region();
        $terresSauvages->setName('Terres Sauvages');
        $terresSauvages->setSlug('terres-sauvages');
        $terresSauvages->setDescription('Contrées dangereuses au-delà des plaines. Riches en minerais rares mais peuplées de monstres redoutables.');
        $terresSauvages->setIcon('⚔️');
        $terresSauvages->setTaxRate('0.0800');
        $terresSauvages->setIsContestable(true);
        $terresSauvages->setCreatedAt(new \DateTime());
        $terresSauvages->setUpdatedAt(new \DateTime());
        $manager->persist($terresSauvages);
        $this->addReference('region_terres_sauvages', $terresSauvages);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
        ];
    }
}
