<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\Mount;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MountFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTime();

        $horse = new Mount();
        $horse->setSlug('horse_brown');
        $horse->setName('Cheval brun');
        $horse->setDescription('Une monture commune, fidele et endurante. Premier compagnon de voyage de la plupart des aventuriers.');
        $horse->setSpriteSheet('mount/horse_brown.png');
        $horse->setIconPath('mount/icons/horse_brown.png');
        $horse->setSpeedBonus(50);
        $horse->setObtentionType(Mount::OBTENTION_PURCHASE);
        $horse->setGilCost(2500);
        $horse->setRequiredLevel(5);
        $horse->setEnabled(true);
        $horse->setCreatedAt($now);
        $horse->setUpdatedAt($now);
        $manager->persist($horse);
        $this->addReference('mount_horse_brown', $horse);

        $wolf = new Mount();
        $wolf->setSlug('wolf_dire');
        $wolf->setName('Loup sauvage');
        $wolf->setDescription('Un loup apprivoise apres une longue chaine de quetes. Plus rapide qu\'un cheval, il ne craint ni les forets ni les marais.');
        $wolf->setSpriteSheet('mount/wolf_dire.png');
        $wolf->setIconPath('mount/icons/wolf_dire.png');
        $wolf->setSpeedBonus(60);
        $wolf->setObtentionType(Mount::OBTENTION_QUEST);
        $wolf->setGilCost(null);
        $wolf->setRequiredLevel(15);
        $wolf->setEnabled(true);
        $wolf->setCreatedAt($now);
        $wolf->setUpdatedAt($now);
        $manager->persist($wolf);
        $this->addReference('mount_wolf_dire', $wolf);

        $chocobo = new Mount();
        $chocobo->setSlug('chocobo_yellow');
        $chocobo->setName('Chocobo jaune');
        $chocobo->setDescription('L\'oiseau geant legendaire. Rare et precieux, il est reserve aux aventuriers chevronnes qui en obtiennent un apres une quete epique.');
        $chocobo->setSpriteSheet('mount/chocobo_yellow.png');
        $chocobo->setIconPath('mount/icons/chocobo_yellow.png');
        $chocobo->setSpeedBonus(75);
        $chocobo->setObtentionType(Mount::OBTENTION_QUEST);
        $chocobo->setGilCost(null);
        $chocobo->setRequiredLevel(30);
        $chocobo->setEnabled(true);
        $chocobo->setCreatedAt($now);
        $chocobo->setUpdatedAt($now);
        $manager->persist($chocobo);
        $this->addReference('mount_chocobo_yellow', $chocobo);

        $direboar = new Mount();
        $direboar->setSlug('direboar');
        $direboar->setName('Sanglier colossal');
        $direboar->setDescription('Un sanglier massif obtenu comme drop rare sur certains boss de la montagne. Lent a galoper mais resistant aux coups ennemis.');
        $direboar->setSpriteSheet('mount/direboar.png');
        $direboar->setIconPath('mount/icons/direboar.png');
        $direboar->setSpeedBonus(40);
        $direboar->setObtentionType(Mount::OBTENTION_DROP);
        $direboar->setGilCost(null);
        $direboar->setRequiredLevel(20);
        $direboar->setEnabled(true);
        $direboar->setCreatedAt($now);
        $direboar->setUpdatedAt($now);
        $manager->persist($direboar);
        $this->addReference('mount_direboar', $direboar);

        $manager->flush();
    }
}
