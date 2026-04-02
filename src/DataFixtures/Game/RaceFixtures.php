<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\Race;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RaceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTime();

        $human = new Race();
        $human->setSlug('human');
        $human->setName('Humain');
        $human->setDescription('Les humains sont la race la plus répandue. Polyvalents et adaptables, ils ne possèdent aucun bonus ni malus particulier.');
        $human->setStatModifiers(['life' => 0, 'energy' => 0, 'speed' => 0, 'hit' => 0]);
        $human->setAvailableAtCreation(true);
        $human->setCreatedAt($now);
        $human->setUpdatedAt($now);
        $manager->persist($human);
        $this->addReference('race_human', $human);

        $elf = new Race();
        $elf->setSlug('elf');
        $elf->setName('Elfe');
        $elf->setDescription('Les elfes sont agiles et précis. Leur vitesse naturelle et leur adresse en font d\'excellents éclaireurs et archers.');
        $elf->setStatModifiers(['life' => 0, 'energy' => 0, 'speed' => 2, 'hit' => 3]);
        $elf->setAvailableAtCreation(true);
        $elf->setCreatedAt($now);
        $elf->setUpdatedAt($now);
        $manager->persist($elf);
        $this->addReference('race_elf', $elf);

        $dwarf = new Race();
        $dwarf->setSlug('dwarf');
        $dwarf->setName('Nain');
        $dwarf->setDescription('Les nains sont robustes et endurants. Leur constitution exceptionnelle leur confère une résistance accrue aux coups.');
        $dwarf->setStatModifiers(['life' => 5, 'energy' => 5, 'speed' => -1, 'hit' => 0]);
        $dwarf->setAvailableAtCreation(true);
        $dwarf->setCreatedAt($now);
        $dwarf->setUpdatedAt($now);
        $manager->persist($dwarf);
        $this->addReference('race_dwarf', $dwarf);

        $orc = new Race();
        $orc->setSlug('orc');
        $orc->setName('Orc');
        $orc->setDescription('Les orcs sont des guerriers nés. Leur force brute compense leur manque de finesse, les rendant redoutables au corps à corps.');
        $orc->setStatModifiers(['life' => 8, 'energy' => 0, 'speed' => 0, 'hit' => -3]);
        $orc->setAvailableAtCreation(true);
        $orc->setCreatedAt($now);
        $orc->setUpdatedAt($now);
        $manager->persist($orc);
        $this->addReference('race_orc', $orc);

        $manager->flush();
    }
}
