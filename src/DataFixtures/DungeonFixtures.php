<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Zone;
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

        $this->loadGroupDungeons($manager, $map);

        $manager->flush();
    }

    /**
     * Donjons de **groupe**, rattaches a une zone du graphe : ce sont les seuls
     * que l'ecran de zone propose (`maxPlayers > 1`). Les deux donjons solo
     * ci-dessus restent hors graphe, ouverts depuis `/game/dungeon`.
     *
     * Ils forment le reservoir de contenu gratuit du modele PBBG : l'entree ne
     * coute pas d'energie et la repetition est reglee par la decroissance de
     * recompense (`zone.dungeon.lockout.*`), pas par un blocage sec.
     */
    private function loadGroupDungeons(ObjectManager $manager, Map $fallbackMap): void
    {
        $definitions = [
            [
                'slug' => 'galeries-envahies',
                'zone' => 'foret-des-murmures',
                'name' => 'Les Galeries envahies',
                'nameEn' => 'The Overrun Galleries',
                'description' => 'Sous les racines de la foret, un boyau s\'est effondre et libere ce qui dormait dessous. Trop vaste pour un seul aventurier : il faut y descendre a plusieurs.',
                'descriptionEn' => 'Beneath the forest roots, a tunnel has collapsed and freed what slept below. Far too vast for a lone adventurer: you must descend as a group.',
                'minLevel' => 3,
                'maxPlayers' => 4,
                'lootPreview' => ['Equipement tier 2', 'Materia commune', 'Composants d\'artisanat'],
            ],
            [
                'slug' => 'forges-noyees',
                'zone' => 'mines-profondes',
                'name' => 'Les Forges noyees',
                'nameEn' => 'The Drowned Forges',
                'description' => 'Les anciennes forges des mines ont ete englouties par une nappe souterraine. Les constructs qui les gardaient tournent encore, indifferents a l\'eau qui monte.',
                'descriptionEn' => 'The old forges of the mines were swallowed by an underground flood. The constructs that guarded them still turn, indifferent to the rising water.',
                'minLevel' => 8,
                'maxPlayers' => 5,
                'lootPreview' => ['Equipement tier 3', 'Materia rare', 'Lingots de cobalt'],
            ],
        ];

        $zoneRepository = $manager->getRepository(Zone::class);

        foreach ($definitions as $definition) {
            $zone = $zoneRepository->findOneBy(['slug' => $definition['zone']]);
            if (null === $zone) {
                // Le graphe de zones est importe depuis le YAML : si la zone
                // visee disparait de la definition, on saute le donjon plutot
                // que de casser tout le chargement des fixtures.
                continue;
            }

            $dungeon = new Dungeon();
            $dungeon->setSlug($definition['slug']);
            $dungeon->setName($definition['name']);
            $dungeon->setNameTranslations(['en' => $definition['nameEn']]);
            $dungeon->setDescription($definition['description']);
            $dungeon->setDescriptionTranslations(['en' => $definition['descriptionEn']]);
            // `map` reste obligatoire mais n'est plus qu'un support de donnees
            // depuis la suppression du rendu carte (ZON-21) : c'est `zone` qui
            // rattache desormais le donjon au monde.
            $dungeon->setMap($zone->getSourceMap() ?? $fallbackMap);
            $dungeon->setZone($zone);
            $dungeon->setMinLevel($definition['minLevel']);
            $dungeon->setMaxPlayers($definition['maxPlayers']);
            $dungeon->setLootPreview($definition['lootPreview']);
            $dungeon->setCreatedAt(new \DateTime());
            $dungeon->setUpdatedAt(new \DateTime());

            $manager->persist($dungeon);
            $this->addReference('dungeon_' . $definition['slug'], $dungeon);
        }
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
            // Les donjons de groupe se rattachent aux zones du graphe, importees
            // depuis config/game/zones/world_1.yaml par cette fixture.
            ZoneGraphFixtures::class,
        ];
    }
}
