<?php

namespace App\DataFixtures;

use App\Entity\App\TimeTrial;
use App\Entity\App\Zone;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Parcours chronometres du World 1 (tache 133).
 *
 * Les deux parcours sont trace sur le graphe existant, et choisis pour que la
 * **route** compte : le detour le plus court n'est pas toujours celui qui
 * traverse le hub. Un joueur qui rentre au village entre chaque etape perd,
 * face a celui qui emprunte les liaisons laterales.
 */
class TimeTrialFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [ZoneGraphFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $zones = $manager->getRepository(Zone::class);
        $hub = $zones->findOneBy(['slug' => 'village-de-lumiere']);

        if (!$hub instanceof Zone) {
            return;
        }

        // Initiation : quatre liaisons courtes, dont un aller-retour par le
        // quartier residentiel. Sert surtout a faire comprendre la mecanique.
        $initiation = new TimeTrial();
        $initiation->setSlug('sentiers-d-apprentissage');
        $initiation->setName('Les sentiers d\'apprentissage');
        $initiation->setNameTranslations(['en' => 'The Learning Paths']);
        $initiation->setDescription('Un parcours court pour prendre la mesure du chrono : le quartier, le village, puis la lisiere de la foret.');
        $initiation->setDescriptionTranslations(['en' => 'A short course to get a feel for the clock: the district, the village, then the forest edge.']);
        $initiation->setStartZone($hub);
        $initiation->setCheckpoints(['quartier-des-jardins', 'village-de-lumiere', 'foret-des-murmures']);
        $initiation->setEnergyCost(3);
        $initiation->setTimeLimitSeconds(7_200);
        $manager->persist($initiation);

        // Tour complet : la route optimale passe par les liaisons laterales
        // (foret-mines, mines-crete) et non par le hub. Repasser par le village
        // entre chaque etape coute plus de vingt minutes.
        $tour = new TimeTrial();
        $tour->setSlug('tour-des-quatre-vents');
        $tour->setName('Le tour des quatre vents');
        $tour->setNameTranslations(['en' => 'The Four Winds Circuit']);
        $tour->setDescription('La grande boucle : la foret, les mines, la crete, puis retour au village. Les liaisons laterales font toute la difference.');
        $tour->setDescriptionTranslations(['en' => 'The grand loop: the forest, the mines, the ridge, then back to the village. The lateral links make all the difference.']);
        $tour->setStartZone($hub);
        $tour->setCheckpoints(['foret-des-murmures', 'mines-profondes', 'crete-de-ventombre', 'village-de-lumiere']);
        $tour->setEnergyCost(8);
        $tour->setTimeLimitSeconds(86_400);
        $manager->persist($tour);

        $manager->flush();
    }
}
