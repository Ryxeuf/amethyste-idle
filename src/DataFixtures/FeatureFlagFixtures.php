<?php

namespace App\DataFixtures;

use App\Entity\App\FeatureFlag;
use App\GameEngine\Zone\MapFreeze;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FeatureFlagFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Pivot PBBG (ZON-01) : gel de la carte navigable. Desactive par defaut —
        // a activer globalement (ou par testeur) une fois les actions de zone
        // livrees (Sprint 8), sinon la boucle de jeu n'a plus de point d'entree.
        $mapFrozen = new FeatureFlag();
        $mapFrozen->setSlug(MapFreeze::FLAG);
        $mapFrozen->setName('Gel de la carte (pivot PBBG)');
        $mapFrozen->setDescription('Redirige /game/map vers /game/zone, refuse move/teleport et suspend les topics Mercure map/move et map/respawn. Activable par utilisateur pour les testeurs.');
        $mapFrozen->setEnabled(false);
        $mapFrozen->setCreatedAt(new \DateTime());
        $mapFrozen->setUpdatedAt(new \DateTime());

        $manager->persist($mapFrozen);
        $manager->flush();
    }
}
