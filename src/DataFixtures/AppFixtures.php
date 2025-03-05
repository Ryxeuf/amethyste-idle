<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Les fixtures sont chargées via les classes spécifiques
        
        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            WorldFixtures::class,
            MapFixtures::class,
            AreaFixtures::class,
            PlayerFixtures::class,
            SlotFixtures::class,
            InventoryFixtures::class,
            DomainFixtures::class,
            DomainExperienceFixtures::class,
            SkillFixtures::class,
            SpellFixtures::class,
            MonsterFixtures::class,
            MobFixtures::class,
            ItemFixtures::class,
            PlayerItemFixtures::class,
            MonsterItemFixtures::class,
            ObjectLayerFixtures::class,
            PnjFixtures::class,
            QuestFixtures::class,
            PlayerQuestFixtures::class,
        ];
    }
} 
