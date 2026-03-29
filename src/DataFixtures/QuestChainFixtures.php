<?php

namespace App\DataFixtures;

use App\Entity\App\Map;
use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Sets up quest prerequisite chains and fixes PNJ/map IDs in quest requirements.
 * Extracted from QuestFixtures to break circular dependency with PnjFixtures.
 */
class QuestChainFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Chaîne Acte 1 : L'Éveil (5 quêtes séquentielles)
        /** @var Quest $acte1Reveil */
        $acte1Reveil = $this->getReference('quest_acte1_reveil', Quest::class);
        /** @var Quest $acte1PremiersPas */
        $acte1PremiersPas = $this->getReference('quest_acte1_premiers_pas', Quest::class);
        /** @var Quest $acte1Bapteme */
        $acte1Bapteme = $this->getReference('quest_acte1_bapteme_du_feu', Quest::class);
        /** @var Quest $acte1Recolte */
        $acte1Recolte = $this->getReference('quest_acte1_recolte', Quest::class);
        /** @var Quest $acte1Cristal */
        $acte1Cristal = $this->getReference('quest_acte1_cristal', Quest::class);

        $acte1PremiersPas->setPrerequisiteQuests([$acte1Reveil->getId()]);
        $acte1Bapteme->setPrerequisiteQuests([$acte1PremiersPas->getId()]);
        $acte1Recolte->setPrerequisiteQuests([$acte1Bapteme->getId()]);
        $acte1Cristal->setPrerequisiteQuests([$acte1Recolte->getId()]);

        // Chaîne Acte 2 : Fragment Forêt (4 quêtes séquentielles, après Acte 1)
        /** @var Quest $acte2ForetMurmures */
        $acte2ForetMurmures = $this->getReference('quest_acte2_foret_murmures', Quest::class);
        /** @var Quest $acte2ForetPurification */
        $acte2ForetPurification = $this->getReference('quest_acte2_foret_purification', Quest::class);
        /** @var Quest $acte2ForetRemede */
        $acte2ForetRemede = $this->getReference('quest_acte2_foret_remede', Quest::class);
        /** @var Quest $acte2ForetFragment */
        $acte2ForetFragment = $this->getReference('quest_acte2_foret_fragment', Quest::class);

        $acte2ForetMurmures->setPrerequisiteQuests([$acte1Cristal->getId()]);
        $acte2ForetPurification->setPrerequisiteQuests([$acte2ForetMurmures->getId()]);
        $acte2ForetRemede->setPrerequisiteQuests([$acte2ForetPurification->getId()]);
        $acte2ForetFragment->setPrerequisiteQuests([$acte2ForetRemede->getId()]);

        // Chaîne Acte 2 : Fragment Mines (4 quêtes séquentielles, après Acte 1)
        /** @var Quest $acte2MinesTremblements */
        $acte2MinesTremblements = $this->getReference('quest_acte2_mines_tremblements', Quest::class);
        /** @var Quest $acte2MinesMinerai */
        $acte2MinesMinerai = $this->getReference('quest_acte2_mines_minerai', Quest::class);
        /** @var Quest $acte2MinesForge */
        $acte2MinesForge = $this->getReference('quest_acte2_mines_forge', Quest::class);
        /** @var Quest $acte2MinesFragment */
        $acte2MinesFragment = $this->getReference('quest_acte2_mines_fragment', Quest::class);

        $acte2MinesTremblements->setPrerequisiteQuests([$acte1Cristal->getId()]);
        $acte2MinesMinerai->setPrerequisiteQuests([$acte2MinesTremblements->getId()]);
        $acte2MinesForge->setPrerequisiteQuests([$acte2MinesMinerai->getId()]);
        $acte2MinesFragment->setPrerequisiteQuests([$acte2MinesForge->getId()]);

        // Fix PNJ ID in talk_to requirements (needs PnjFixtures loaded first)
        /** @var Pnj $thadeus */
        $thadeus = $this->getReference('forest_pnj_2', Pnj::class);
        $requirements = $acte2ForetMurmures->getRequirements();
        $requirements['talk_to'][0]['pnj_id'] = $thadeus->getId();
        $acte2ForetMurmures->setRequirements($requirements);

        /** @var Pnj $grimmur */
        $grimmur = $this->getReference('mines_pnj_0', Pnj::class);
        $requirementsMines = $acte2MinesTremblements->getRequirements();
        $requirementsMines['talk_to'][0]['pnj_id'] = $grimmur->getId();
        $acte2MinesTremblements->setRequirements($requirementsMines);

        // Chaîne Acte 2 : Fragment Marais (4 quêtes séquentielles, après Acte 1)
        /** @var Quest $acte2MaraisBrumes */
        $acte2MaraisBrumes = $this->getReference('quest_acte2_marais_brumes', Quest::class);
        /** @var Quest $acte2MaraisIngredients */
        $acte2MaraisIngredients = $this->getReference('quest_acte2_marais_ingredients', Quest::class);
        /** @var Quest $acte2MaraisGardiens */
        $acte2MaraisGardiens = $this->getReference('quest_acte2_marais_gardiens', Quest::class);
        /** @var Quest $acte2MaraisFragment */
        $acte2MaraisFragment = $this->getReference('quest_acte2_marais_fragment', Quest::class);

        $acte2MaraisBrumes->setPrerequisiteQuests([$acte1Cristal->getId()]);
        $acte2MaraisIngredients->setPrerequisiteQuests([$acte2MaraisBrumes->getId()]);
        $acte2MaraisGardiens->setPrerequisiteQuests([$acte2MaraisIngredients->getId()]);
        $acte2MaraisFragment->setPrerequisiteQuests([$acte2MaraisGardiens->getId()]);

        // Fix PNJ ID for Morwen la Voyante (marais)
        /** @var Pnj $morwen */
        $morwen = $this->getReference('marais_pnj_0', Pnj::class);
        $requirementsMarais = $acte2MaraisBrumes->getRequirements();
        $requirementsMarais['talk_to'][0]['pnj_id'] = $morwen->getId();
        $acte2MaraisBrumes->setRequirements($requirementsMarais);

        // Chaîne Acte 2 : Fragment Montagne (3 quêtes séquentielles, après Acte 1)
        /** @var Quest $acte2MontagneEchos */
        $acte2MontagneEchos = $this->getReference('quest_acte2_montagne_echos', Quest::class);
        /** @var Quest $acte2MontagneGardien */
        $acte2MontagneGardien = $this->getReference('quest_acte2_montagne_gardien', Quest::class);
        /** @var Quest $acte2MontagneFragment */
        $acte2MontagneFragment = $this->getReference('quest_acte2_montagne_fragment', Quest::class);

        $acte2MontagneEchos->setPrerequisiteQuests([$acte1Cristal->getId()]);
        $acte2MontagneGardien->setPrerequisiteQuests([$acte2MontagneEchos->getId()]);
        $acte2MontagneFragment->setPrerequisiteQuests([$acte2MontagneGardien->getId()]);

        // Fix PNJ ID for Aldric l'Ancien (montagne)
        /** @var Pnj $aldric */
        $aldric = $this->getReference('montagne_pnj_0', Pnj::class);
        $requirementsMontagne = $acte2MontagneEchos->getRequirements();
        $requirementsMontagne['talk_to'][0]['pnj_id'] = $aldric->getId();
        $acte2MontagneEchos->setRequirements($requirementsMontagne);

        // Chaîne Acte 3 : La Convergence (3 quêtes séquentielles, après les 4 fragments)
        /** @var Quest $acte3Appel */
        $acte3Appel = $this->getReference('quest_acte3_appel', Quest::class);
        /** @var Quest $acte3Gardien */
        $acte3Gardien = $this->getReference('quest_acte3_gardien', Quest::class);
        /** @var Quest $acte3Epilogue */
        $acte3Epilogue = $this->getReference('quest_acte3_epilogue', Quest::class);

        // L'Appel requiert les 4 quêtes de fragment terminées
        $acte3Appel->setPrerequisiteQuests([
            $acte2ForetFragment->getId(),
            $acte2MinesFragment->getId(),
            $acte2MaraisFragment->getId(),
            $acte2MontagneFragment->getId(),
        ]);
        $acte3Gardien->setPrerequisiteQuests([$acte3Appel->getId()]);
        $acte3Epilogue->setPrerequisiteQuests([$acte3Gardien->getId()]);

        // Fix PNJ ID for Claire la Sage (Acte 3)
        /** @var Pnj $claire */
        $claire = $this->getReference('pnj_15', Pnj::class);
        $requirementsAppel = $acte3Appel->getRequirements();
        $requirementsAppel['talk_to'][0]['pnj_id'] = $claire->getId();
        $acte3Appel->setRequirements($requirementsAppel);

        // Fix map_id for the explore quest (Nexus dungeon map)
        /** @var Map $convergenceMap */
        $convergenceMap = $this->getReference('map_dungeon_convergence', Map::class);
        $requirementsEpilogue = $acte3Epilogue->getRequirements();
        $requirementsEpilogue['explore'][0]['map_id'] = $convergenceMap->getId();
        $acte3Epilogue->setRequirements($requirementsEpilogue);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            QuestFixtures::class,
            PnjFixtures::class,
            ForestPnjFixtures::class,
            MinesPnjFixtures::class,
            MaraisPnjFixtures::class,
            MontagnePnjFixtures::class,
            MapFixtures::class,
        ];
    }
}
