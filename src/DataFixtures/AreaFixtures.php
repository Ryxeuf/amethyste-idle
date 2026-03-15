<?php

namespace App\DataFixtures;

use App\Entity\App\Area;
use App\Entity\App\Map;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AreaFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Charger les données des areas depuis le fichier JSON
        $jsonFile = dirname(__DIR__, 2) . '/fixtures/area_data.json';

        if (!file_exists($jsonFile)) {
            throw new \Exception("Le fichier $jsonFile n'existe pas. Veuillez exécuter le script scripts/concat_area_fixtures.php pour le générer.");
        }

        $areasData = json_decode(file_get_contents($jsonFile), true);

        if (empty($areasData)) {
            throw new \Exception("Aucune donnée d'area trouvée dans $jsonFile.");
        }

        // Création des areas pour chaque map
        foreach ($areasData as $mapRef => $areas) {
            foreach ($areas as $areaData) {
                $area = new Area();
                $area->setName($areaData['name']);
                $area->setSlug($areaData['slug']);
                $area->setCoordinates($areaData['coordinates']);
                $area->setFullData(json_encode($areaData['data']));
                $area->setMap($this->getReference($mapRef, Map::class));
                $area->setCreatedAt(new \DateTime());
                $area->setUpdatedAt(new \DateTime());

                $manager->persist($area);

                // Créer une référence unique pour chaque area
                $this->addReference('area_' . $mapRef . '_' . $areaData['coordinates'], $area);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
        ];
    }
}
