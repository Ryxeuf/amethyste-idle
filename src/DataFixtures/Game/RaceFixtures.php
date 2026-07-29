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
        $human->setNameTranslations(['en' => 'Human']);
        $human->setDescription('Les humains passent leur vie a échanger. Un objet posé devant eux, ils savent déjà à quoi il sert et qui l\'achète — les usages leur viennent avant les mots.');
        $human->setDescriptionTranslations(['en' => 'Humans spend their lives trading. Put an object in front of them and they already know what it is for and who buys it — uses come to them before words.']);
        $human->setSpriteSheet('human_v00');
        $human->setAvailableAtCreation(true);
        $human->setCreatedAt($now);
        $human->setUpdatedAt($now);
        $manager->persist($human);
        $this->addReference('race_human', $human);

        $elf = new Race();
        $elf->setSlug('elf');
        $elf->setName('Elfe');
        $elf->setNameTranslations(['en' => 'Elf']);
        $elf->setDescription('Les elfes lisent les lisières. Là où un autre rentrerait bredouille, ils rapportent au moins un endroit à retenir : rien n\'est jamais tout à fait vide.');
        $elf->setDescriptionTranslations(['en' => 'Elves read the margins. Where anyone else would come back empty-handed, they bring back at least a place worth remembering: nothing is ever quite empty.']);
        $elf->setSpriteSheet('human_v01');
        $elf->setAvailableAtCreation(true);
        $elf->setCreatedAt($now);
        $elf->setUpdatedAt($now);
        $manager->persist($elf);
        $this->addReference('race_elf', $elf);

        $dwarf = new Race();
        $dwarf->setSlug('dwarf');
        $dwarf->setName('Nain');
        $dwarf->setNameTranslations(['en' => 'Dwarf']);
        $dwarf->setDescription('Les nains lisent la pierre. La pureté d\'un filon leur saute aux yeux avant le premier coup de pioche — ce que les autres n\'apprennent qu\'en récoltant.');
        $dwarf->setDescriptionTranslations(['en' => 'Dwarves read stone. A vein\'s purity is plain to them before the first swing of the pick — what others only learn by harvesting.']);
        $dwarf->setSpriteSheet('human_v02');
        $dwarf->setAvailableAtCreation(true);
        $dwarf->setCreatedAt($now);
        $dwarf->setUpdatedAt($now);
        $manager->persist($dwarf);
        $this->addReference('race_dwarf', $dwarf);

        $orc = new Race();
        $orc->setSlug('orc');
        $orc->setName('Orc');
        $orc->setNameTranslations(['en' => 'Orc']);
        $orc->setDescription('Les orcs ont le flair. Un monstre croisé une seule fois leur a déjà livré son élément et sa faiblesse, quand il en faut cent aux autres.');
        $orc->setDescriptionTranslations(['en' => 'Orcs have the scent. A monster met once has already given up its element and its weakness, where others need a hundred encounters.']);
        $orc->setSpriteSheet('human_v03');
        $orc->setAvailableAtCreation(true);
        $orc->setCreatedAt($now);
        $orc->setUpdatedAt($now);
        $manager->persist($orc);
        $this->addReference('race_orc', $orc);

        $manager->flush();
    }
}
