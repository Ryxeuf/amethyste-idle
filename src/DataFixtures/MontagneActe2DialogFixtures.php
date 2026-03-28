<?php

namespace App\DataFixtures;

use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Met a jour les dialogues des PNJ de la Crete de Ventombre pour l'Acte 2.
 *
 * Aldric l'Ancien : lance la chaine Fragment Montagne (quetes 1, 2, 3).
 */
class MontagneActe2DialogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Quest $qCristal */
        $qCristal = $this->getReference('quest_acte1_cristal', Quest::class);
        /** @var Quest $qEchos */
        $qEchos = $this->getReference('quest_acte2_montagne_echos', Quest::class);
        /** @var Quest $qGardien */
        $qGardien = $this->getReference('quest_acte2_montagne_gardien', Quest::class);
        /** @var Quest $qFragment */
        $qFragment = $this->getReference('quest_acte2_montagne_fragment', Quest::class);

        $acte2Ids = [
            'cristal' => $qCristal->getId(),
            'echos' => $qEchos->getId(),
            'gardien' => $qGardien->getId(),
            'fragment' => $qFragment->getId(),
        ];

        /** @var Pnj $aldric */
        $aldric = $this->getReference('montagne_pnj_0', Pnj::class);
        $aldric->setDialog($this->createAldricDialog($acte2Ids));

        $manager->flush();
    }

    private function createAldricDialog(array $ids): array
    {
        return [
            // 0 — Accueil
            [
                'text' => "Le vent porte vos pas jusqu'ici... Vous n'êtes pas un randonneur ordinaire. Je suis Aldric. Je vis sur cette crête depuis plus longtemps que les pierres s'en souviennent.",
                'choices' => [
                    ['text' => 'Que savez-vous de cette montagne ?', 'action' => 'next'],
                    ['text' => 'Je ne fais que passer.', 'action' => 'close'],
                ],
            ],
            // 1 — Aiguillage conditionnel
            [
                'conditional_next' => [
                    // Fragment recupere → epilogue
                    ['next' => 8, 'next_condition' => ['quest' => [$ids['fragment']]]],
                    // Fragment proposable (gardien vaincu)
                    ['next' => 6, 'next_condition' => ['quest_prerequisites_met' => [$ids['fragment']], 'quest_not' => [$ids['fragment']]]],
                    // Gardien en cours
                    ['next' => 5, 'next_condition' => ['quest_active' => [$ids['gardien']]]],
                    // Echos termines → proposer gardien
                    ['next' => 4, 'next_condition' => ['quest' => [$ids['echos']], 'quest_not' => [$ids['gardien']]]],
                    // Acte 1 termine, pas encore commence Acte 2 montagne → proposer echos
                    ['next' => 2, 'next_condition' => ['quest' => [$ids['cristal']], 'quest_not' => [$ids['echos']]]],
                    // Par defaut → dialogue normal
                    ['next' => 9],
                ],
                'text' => '',
            ],
            // 2 — Proposer quete "Les Echos du Sommet"
            [
                'text' => "Attendez... cette aura que vous portez... le Cristal d'Améthyste. Je le reconnais. Les vents me parlent de vous depuis des jours. Quelque chose s'éveille au sommet de la Crête — un écho ancien, un appel. Venez me voir quand vous serez prêt, je vous expliquerai.",
                'choices' => [
                    ['text' => 'Je suis prêt, dites-moi tout.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['echos']]],
                    ['text' => 'Pas maintenant.', 'action' => 'close'],
                ],
            ],
            // 3 — (reserved)
            [
                'text' => '',
            ],
            // 4 — Proposer quete "Le Gardien des Cimes"
            [
                'text' => "Vous avez senti les échos, n'est-ce pas ? Ils viennent du pic sacré, tout au sommet. Mais le chemin est gardé par le Dragon ancestral — une créature millénaire qui protège le sommet depuis la nuit des temps. Personne ne l'a jamais vaincu. Vous devrez l'affronter seul pour prouver votre valeur.",
                'choices' => [
                    ['text' => 'Je vaincrai le Dragon.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['gardien']]],
                    ['text' => 'Je ne suis pas encore prêt.', 'action' => 'close'],
                ],
            ],
            // 5 — Gardien en cours
            [
                'text' => "Le Dragon ancestral vous attend au sommet. C'est une créature de feu et de fureur — préparez-vous bien. L'eau est sa faiblesse. Et surtout, ne sous-estimez pas sa rage quand il est blessé.",
            ],
            // 6 — Proposer quete "Le Fragment du Sommet"
            [
                'text' => "Vous l'avez fait... Le Dragon ancestral est vaincu. Le chemin vers le pic sacré est enfin libre. Là-haut, au sommet battu par les vents éternels, un éclat de cristal blanc brille depuis l'aube des temps. C'est l'un des fragments. Allez le récupérer, {{player_name}}.",
                'choices' => [
                    ['text' => 'J\'y monte immédiatement.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['fragment']]],
                    ['text' => 'Je reviendrai plus tard.', 'action' => 'close'],
                ],
            ],
            // 7 — (reserved)
            [
                'text' => '',
            ],
            // 8 — Fragment recupere (epilogue Acte 2 Montagne)
            [
                'text' => "Le Fragment du Sommet... Le dernier éclat. Quatre fragments, quatre terres, quatre épreuves. Vous les avez tous réunis, {{player_name}}. Les vents murmurent que votre quête touche à son terme — ou peut-être ne fait-elle que commencer. Le Cristal d'Améthyste attend.",
            ],
            // 9 — Dialogue normal (pas encore Acte 2)
            [
                'text' => 'La Crête de Ventombre est le toit du monde. Les vents qui la balaient portent les échos de l\'ancien temps. Un dragon sommeille près du sommet — ne le sous-estimez pas. Et au-delà de sa tanière, on dit qu\'un cristal brille depuis l\'aube des temps...',
                'choices' => [
                    ['text' => 'Merci pour ces mises en garde.', 'action' => 'close'],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            QuestFixtures::class,
            MontagnePnjFixtures::class,
        ];
    }
}
