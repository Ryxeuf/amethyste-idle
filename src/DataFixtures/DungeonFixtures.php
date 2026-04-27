<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\Game\Dungeon;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DungeonFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $map = $this->getReference('map_dungeon_racines', Map::class);

        $dungeon = new Dungeon();
        $dungeon->setSlug('racines-de-la-foret');
        $dungeon->setName('Racines de la foret');
        $dungeon->setNameTranslations(['en' => 'Roots of the Forest']);
        $dungeon->setDescription('Un reseau de galeries souterraines envahi par des racines corrompues. Les creatures qui y rodent sont devenues hostiles, et une menace plus ancienne sommeille dans les profondeurs.');
        $dungeon->setDescriptionTranslations(['en' => 'A network of underground tunnels overrun by corrupted roots. The creatures that prowl within have grown hostile, and an older threat slumbers in the depths.']);
        $dungeon->setMap($map);
        $dungeon->setMinLevel(5);
        $dungeon->setMaxPlayers(1);
        $dungeon->setLootPreview(['Equipement tier 2', 'Materia rare', 'Potions avancees']);
        $dungeon->setCreatedAt(new \DateTime());
        $dungeon->setUpdatedAt(new \DateTime());

        $manager->persist($dungeon);
        $this->addReference('dungeon_racines', $dungeon);

        // Donjon final : Le Nexus de la Convergence (tache 94 — Acte 3)
        $mapConvergence = $this->getReference('map_dungeon_convergence', Map::class);

        $convergence = new Dungeon();
        $convergence->setSlug('nexus-de-la-convergence');
        $convergence->setName('Le Nexus de la Convergence');
        $convergence->setNameTranslations(['en' => 'The Nexus of Convergence']);
        $convergence->setDescription('Le coeur du cristal d\'Amethyste bat au plus profond de ce sanctuaire oublie. Les quatre fragments resonent, attirant leur porteur vers une verite ancienne. Seuls ceux qui ont rassemble les fragments peuvent penetrer ces lieux et affronter le Gardien de la Convergence.');
        $convergence->setDescriptionTranslations(['en' => 'The heart of the Amethyst crystal beats deep within this forgotten sanctuary. The four fragments resonate, drawing their bearer toward an ancient truth. Only those who have gathered all fragments may enter these halls and face the Guardian of Convergence.']);
        $convergence->setMap($mapConvergence);
        $convergence->setMinLevel(25);
        $convergence->setMaxPlayers(1);
        $convergence->setLootPreview(['Equipement Amethyste', 'Titre exclusif', 'Epilogue de la trame']);
        $convergence->setEntryRequirements([
            'items' => [
                ['slug' => 'quest-fragment-foret', 'name' => 'Fragment Sylvestre'],
                ['slug' => 'quest-fragment-mines', 'name' => 'Fragment de la Forge'],
                ['slug' => 'quest-fragment-marais', 'name' => 'Fragment des Brumes'],
                ['slug' => 'quest-fragment-montagne', 'name' => 'Fragment du Sommet'],
            ],
        ]);
        $convergence->setCreatedAt(new \DateTime());
        $convergence->setUpdatedAt(new \DateTime());

        $manager->persist($convergence);
        $this->addReference('dungeon_convergence', $convergence);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
        ];
    }
}
