<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Pnj;
use App\Entity\App\PnjSchedule;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Routines quotidiennes des PNJ du Village de Lumière.
 *
 * Chaque PNJ a 3-4 positions dans la journée (travail, pause, maison, taverne).
 * L'heure est en temps in-game (0-23).
 */
class PnjScheduleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $village = $this->getReference('map_2', Map::class);

        $routines = $this->getRoutines();

        foreach ($routines as $routine) {
            $pnj = $this->getReference($routine['pnjRef'], Pnj::class);

            foreach ($routine['schedules'] as $schedule) {
                $entry = new PnjSchedule();
                $entry->setPnj($pnj);
                $entry->setHour($schedule['hour']);
                $entry->setCoordinates($schedule['coordinates']);
                $entry->setMap($village);
                $entry->setLabel($schedule['label'] ?? null);

                $manager->persist($entry);
            }
        }

        $manager->flush();
    }

    /**
     * @return array<int, array{pnjRef: string, schedules: array<int, array{hour: int, coordinates: string, label?: string}>}>
     */
    private function getRoutines(): array
    {
        return [
            // Aldric le Forgeron : forge (7h-19h), taverne (20h-23h), maison (0h-6h)
            [
                'pnjRef' => 'village_pnj_0',
                'schedules' => [
                    ['hour' => 0, 'coordinates' => '4.4', 'label' => 'Dort chez lui'],
                    ['hour' => 7, 'coordinates' => '7.7', 'label' => 'Travaille à la forge'],
                    ['hour' => 20, 'coordinates' => '20.15', 'label' => 'Se détend à la taverne'],
                ],
            ],
            // Iris l'Alchimiste : laboratoire (6h-21h), promenade (12h-13h), maison (22h-5h)
            [
                'pnjRef' => 'village_pnj_1',
                'schedules' => [
                    ['hour' => 0, 'coordinates' => '36.4', 'label' => 'Dort chez elle'],
                    ['hour' => 6, 'coordinates' => '33.8', 'label' => 'Travaille au laboratoire'],
                    ['hour' => 12, 'coordinates' => '20.12', 'label' => 'Promenade sur la place'],
                    ['hour' => 13, 'coordinates' => '33.8', 'label' => 'Retour au laboratoire'],
                    ['hour' => 22, 'coordinates' => '36.4', 'label' => 'Rentre chez elle'],
                ],
            ],
            // Marcellin le Marchand : étal (8h-20h), taverne (21h-23h), maison (0h-7h)
            [
                'pnjRef' => 'village_pnj_2',
                'schedules' => [
                    ['hour' => 0, 'coordinates' => '4.20', 'label' => 'Dort chez lui'],
                    ['hour' => 8, 'coordinates' => '7.23', 'label' => 'Tient son étal'],
                    ['hour' => 21, 'coordinates' => '20.15', 'label' => 'Boit à la taverne'],
                ],
            ],
            // Gareth le Garde : patrouille jour (6h-17h) entrée sud, ronde soir (18h-23h) place, repos (0h-5h)
            [
                'pnjRef' => 'village_pnj_5',
                'schedules' => [
                    ['hour' => 0, 'coordinates' => '25.4', 'label' => 'Repos au poste de garde'],
                    ['hour' => 6, 'coordinates' => '20.35', 'label' => 'Garde l\'entrée sud'],
                    ['hour' => 18, 'coordinates' => '20.20', 'label' => 'Ronde sur la place centrale'],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            VillageHubPnjFixtures::class,
        ];
    }
}
