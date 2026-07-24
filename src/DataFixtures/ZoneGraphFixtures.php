<?php

namespace App\DataFixtures;

use App\GameEngine\Zone\ZoneDefinitionLoader;
use App\GameEngine\Zone\ZoneImporter;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Graphe de zones du World 1 (pivot PBBG, ZON-02 ; declaratif depuis ZON-11).
 *
 * A ne pas confondre avec ZoneFixtures (sous-zones Tiled biome/meteo sur Area,
 * heritees de l'editeur de cartes). Ici, chaque Zone est un noeud du graphe de
 * monde PBBG et reprend une carte TMX existante (sourceMap) pour permettre la
 * migration des positions et spawns (ZON-03 / ZON-04). Topologie en etoile
 * autour du Village de Lumiere + liaisons laterales foret-marais et mines-crete.
 *
 * ZON-11 : la definition vit desormais dans `config/game/zones/world_1.yaml`
 * (source de verite unique, partagee avec la commande `app:zone:import`). Cette
 * fixture ne fait que rejouer l'import declaratif — ajouter du contenu = editer
 * le YAML, pas ce fichier.
 */
class ZoneGraphFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly ZoneDefinitionLoader $loader,
        private readonly ZoneImporter $importer,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $definition = $this->loader->loadFile($this->loader->defaultFile());
        $this->importer->import($definition);
    }

    public function getDependencies(): array
    {
        return [
            MapFixtures::class,
        ];
    }
}
