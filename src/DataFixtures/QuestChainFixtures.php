<?php

namespace App\DataFixtures;

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

        // Arc « intro » (NAR-03) : deux étapes finales chaînées après le Cristal —
        // craft T1 (première potion) puis découverte des guildes.
        /** @var Quest $acte1PremierePotion */
        $acte1PremierePotion = $this->getReference('quest_acte1_premiere_potion', Quest::class);
        /** @var Quest $acte1Guilde */
        $acte1Guilde = $this->getReference('quest_acte1_guilde', Quest::class);
        $acte1PremierePotion->setPrerequisiteQuests([$acte1Cristal->getId()]);
        $acte1Guilde->setPrerequisiteQuests([$acte1PremierePotion->getId()]);

        // Back-patch du PNJ mentor (Claire la Sage, pnj_15) pour l'étape « guilde ».
        /** @var Pnj $claireMentor */
        $claireMentor = $this->getReference('pnj_15', Pnj::class);
        $guildeReq = $acte1Guilde->getRequirements();
        $guildeReq['talk_to'][0]['pnj_id'] = $claireMentor->getId();
        $acte1Guilde->setRequirements($guildeReq);

        // ONB-15 : les trois premieres etapes de l'arc `intro` visaient
        // `map_id => 1` — c'est-a-dire la « Carte de test », que `MapFixtures`
        // cree en premier. Elles ne pointaient donc pas vers un mecanisme mort,
        // mais vers une zone qui n'existe pas : l'acte I etait bloque des sa
        // premiere etape. Elles parlent maintenant a quelqu'un, comme le reste
        // de l'arc, et leur `pnj_id` se recale ici. Jamais le donneur de la
        // quete : on ne demande pas de retourner voir celui qu'on vient de
        // quitter — les trois sont donnees par Claire la Sage.
        $this->pointTalkToAt($acte1Reveil, 'pnj_7');       // Marie la Herboriste
        $this->pointTalkToAt($acte1PremiersPas, 'pnj_0');  // Gérard le Forgeron
        $this->pointTalkToAt($acte1Cristal, 'pnj_18');     // Antoine le Mage

        // Chaine de fond « Foret des Murmures » (NAR-13) : rumeurs → meute → cœur.
        /** @var Quest $bgForetRumeurs */
        $bgForetRumeurs = $this->getReference('quest_bg_foret_rumeurs', Quest::class);
        /** @var Quest $bgForetMeute */
        $bgForetMeute = $this->getReference('quest_bg_foret_meute', Quest::class);
        /** @var Quest $bgForetCoeur */
        $bgForetCoeur = $this->getReference('quest_bg_foret_coeur', Quest::class);
        $bgForetMeute->setPrerequisiteQuests([$bgForetRumeurs->getId()]);
        $bgForetCoeur->setPrerequisiteQuests([$bgForetMeute->getId()]);

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

        // ONB-15 : l'epilogue visait « le coeur du Nexus » par `map_id`, recale
        // ici sur la carte du donjon de la Convergence. Or un donjon n'est pas
        // une zone : cette carte n'a aucune `Zone` qui la prenne pour origine,
        // donc `updateExplored()` ne pouvait jamais la reconnaitre. L'arc se
        // terminait sur une etape impossible. Il se termine desormais chez celle
        // qui attend la reponse depuis la premiere quete.
        $this->pointTalkToAt($acte3Epilogue, 'pnj_15'); // Claire la Sage

        $manager->flush();
    }

    /**
     * Recale le `pnj_id` d'un objectif « parler a » sur un PNJ reellement seede.
     *
     * Les fixtures de quete sont ecrites avant que les PNJ existent : elles
     * posent un `pnj_id` a 0, corrige ici. Ecrire l'identifiant en dur dans la
     * quete marcherait le jour ou on le tape, et casserait au premier PNJ
     * insere avant lui.
     */
    private function pointTalkToAt(Quest $quest, string $pnjReference): void
    {
        /** @var Pnj $pnj */
        $pnj = $this->getReference($pnjReference, Pnj::class);

        $requirements = $quest->getRequirements();
        $requirements['talk_to'][0]['pnj_id'] = $pnj->getId();
        $quest->setRequirements($requirements);
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
