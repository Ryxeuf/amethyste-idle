<?php

namespace App\DataFixtures;

use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Met à jour les dialogues des PNJ des Mines profondes pour l'Acte 2.
 *
 * Grimmur le Contremaître : lance la chaîne Fragment Mines (quêtes 1, 2, 3, 4).
 * Hilda l'Ingénieure : dialogue lié à la quête 2 (Minerai Ancien).
 */
class MinesActe2DialogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Quest $qTremblements */
        $qTremblements = $this->getReference('quest_acte2_mines_tremblements', Quest::class);
        /** @var Quest $qMinerai */
        $qMinerai = $this->getReference('quest_acte2_mines_minerai', Quest::class);
        /** @var Quest $qForge */
        $qForge = $this->getReference('quest_acte2_mines_forge', Quest::class);
        /** @var Quest $qFragment */
        $qFragment = $this->getReference('quest_acte2_mines_fragment', Quest::class);
        /** @var Quest $qCristal */
        $qCristal = $this->getReference('quest_acte1_cristal', Quest::class);

        $ids = [
            'cristal' => $qCristal->getId(),
            'tremblements' => $qTremblements->getId(),
            'minerai' => $qMinerai->getId(),
            'forge' => $qForge->getId(),
            'fragment' => $qFragment->getId(),
        ];

        /** @var Pnj $grimmur */
        $grimmur = $this->getReference('mines_pnj_0', Pnj::class);
        $grimmur->setDialog($this->createGrimmurDialog($ids));

        /** @var Pnj $hilda */
        $hilda = $this->getReference('mines_pnj_1', Pnj::class);
        $hilda->setDialog($this->createHildaDialog($ids));

        $manager->flush();
    }

    private function createGrimmurDialog(array $ids): array
    {
        return [
            // 0 — Accueil
            [
                'text' => "Bienvenue dans les Mines profondes. Autrefois, elles fournissaient tout le minerai du royaume. Aujourd'hui, seuls les plus courageux osent s'y aventurer.",
                'choices' => [
                    ['text' => "Qu'est-ce qui a changé ?", 'action' => 'next'],
                    ['text' => 'Je ne fais que passer.', 'action' => 'close'],
                ],
            ],
            // 1 — Aiguillage conditionnel
            [
                'conditional_next' => [
                    ['next' => 10, 'next_condition' => ['quest' => [$ids['fragment']]]],
                    ['next' => 8, 'next_condition' => ['quest_prerequisites_met' => [$ids['fragment']], 'quest_not' => [$ids['fragment']]]],
                    ['next' => 7, 'next_condition' => ['quest_active' => [$ids['forge']]]],
                    ['next' => 6, 'next_condition' => ['quest' => [$ids['minerai']], 'quest_not' => [$ids['forge']]]],
                    ['next' => 5, 'next_condition' => ['quest_active' => [$ids['minerai']]]],
                    ['next' => 4, 'next_condition' => ['quest' => [$ids['tremblements']], 'quest_not' => [$ids['minerai']]]],
                    ['next' => 2, 'next_condition' => ['quest' => [$ids['cristal']], 'quest_not' => [$ids['tremblements']]]],
                    ['next' => 11],
                ],
                'text' => '',
            ],
            // 2 — Proposer quête "Tremblements souterrains"
            [
                'text' => "Attendez... vous portez l'empreinte du Cristal d'Améthyste. Je la reconnais. Depuis quelques jours, les mines tremblent. Pas des éboulements normaux — c'est un rythme, comme un cœur qui bat dans les profondeurs. Quelque chose se réveille là-dessous.",
                'choices' => [
                    ['text' => 'Je vais enquêter.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['tremblements']]],
                    ['text' => 'Pas maintenant.', 'action' => 'close'],
                ],
            ],
            // 3 — (réservé)
            [
                'text' => '',
            ],
            // 4 — Proposer quête "Le Minerai Ancien"
            [
                'text' => "Vous avez senti les vibrations ? Elles émanent des filons profonds. L'énergie qui pulse ressemble à celle de votre Cristal. Récoltez du fer et de l'or dans les galeries — si le minerai réagit différemment, on saura d'où vient cette force.",
                'choices' => [
                    ['text' => 'Je vais récolter le minerai.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['minerai']]],
                    ['text' => 'Plus tard.', 'action' => 'close'],
                ],
            ],
            // 5 — Minerai en cours
            [
                'text' => "Les filons de fer se trouvent dans les galeries centrales, et l'or est enfoui plus profondément. Prenez une pioche solide et faites attention aux golems.",
            ],
            // 6 — Proposer quête "Le Seigneur de la Forge"
            [
                'text' => "Le minerai que vous avez récolté... il vibre d'une énergie que je n'ai jamais vue. La source est au fond des mines, dans la salle du Seigneur de la Forge. Ce gardien ancien protège quelque chose — quelque chose de la même nature que votre Cristal. Il faudra le vaincre.",
                'choices' => [
                    ['text' => "J'affronterai le Seigneur de la Forge.", 'action' => 'quest_offer', 'data' => ['quest' => $ids['forge']]],
                    ['text' => 'Je ne suis pas prêt.', 'action' => 'close'],
                ],
            ],
            // 7 — Forge en cours
            [
                'text' => "Le Seigneur de la Forge attend au fond des mines. C'est un adversaire redoutable — préparez-vous bien avant de l'affronter. Hilda peut vous vendre des potions si besoin.",
            ],
            // 8 — Proposer quête "Le Fragment de la Forge"
            [
                'text' => "Vous avez vaincu le Seigneur de la Forge ?! Incroyable ! Les tremblements ont cessé, mais... regardez, une fissure s'est ouverte dans le mur de sa salle. Une lueur orangée en émane. Allez voir ce qui se cache derrière.",
                'choices' => [
                    ['text' => "J'y vais immédiatement.", 'action' => 'quest_offer', 'data' => ['quest' => $ids['fragment']]],
                    ['text' => 'Je reviendrai plus tard.', 'action' => 'close'],
                ],
            ],
            // 9 — (réservé)
            [
                'text' => '',
            ],
            // 10 — Fragment récupéré (épilogue Acte 2 Mines)
            [
                'text' => "Le Fragment de la Forge... Un morceau du même cristal ancien que celui de la forêt. Ces fragments étaient dispersés dans tout le royaume, gardés par des créatures anciennes. Si vous en cherchez d'autres, explorez les marais et les montagnes. Bonne chance, {{player_name}}.",
            ],
            // 11 — Dialogue normal (pas encore Acte 2)
            [
                'text' => "Les golems se sont réveillés et les automates ont perdu leur contrôle. Plus vous descendez, plus les créatures sont dangereuses. Au fond se cache le Seigneur de la Forge, un ancien gardien devenu fou. Soyez prudent.",
                'choices' => [
                    ['text' => 'Merci pour les avertissements.', 'action' => 'close'],
                ],
            ],
        ];
    }

    private function createHildaDialog(array $ids): array
    {
        return [
            // 0 — Accueil
            [
                'text' => "Ah, un visiteur ! Je suis Hilda, la dernière ingénieure encore en poste ici. Je répare ce que je peux et je vends du matériel de survie. Ça vous intéresse ?",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Parlez-moi des mines', 'action' => 'next'],
                    ['text' => 'Non merci.', 'action' => 'close'],
                ],
            ],
            // 1 — Aiguillage conditionnel
            [
                'conditional_next' => [
                    ['next' => 4, 'next_condition' => ['quest' => [$ids['minerai']]]],
                    ['next' => 3, 'next_condition' => ['quest_active' => [$ids['minerai']]]],
                    ['next' => 2, 'next_condition' => ['quest' => [$ids['tremblements']], 'quest_not' => [$ids['minerai']]]],
                    ['next' => 5],
                ],
                'text' => '',
            ],
            // 2 — Après tremblements, indice sur le minerai
            [
                'text' => "Vous enquêtez sur les tremblements ? J'ai remarqué que certains filons brillent différemment depuis quelques jours. Si Grimmur vous demande de récolter du minerai, les galeries centrales ont du fer, et l'or se trouve dans les salles profondes.",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci du conseil !', 'action' => 'close'],
                ],
            ],
            // 3 — Minerai en cours
            [
                'text' => "Vous cherchez du fer et de l'or ? Le fer est abondant dans les galeries centrales. Pour l'or, descendez plus profondément, mais attention aux golems de cristal qui gardent les filons les plus riches.",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci !', 'action' => 'close'],
                ],
            ],
            // 4 — Minerai terminé
            [
                'text' => "Vous avez récolté tout ce minerai ? Impressionnant. Ce n'est pas du minerai ordinaire — je le sens vibrer d'ici. Grimmur saura quoi en faire. Bonne continuation !",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci Hilda !', 'action' => 'close'],
                ],
            ],
            // 5 — Dialogue normal (avant Acte 2)
            [
                'text' => "Les filons les plus riches se trouvent en profondeur : fer et cuivre près de l'entrée, argent et or plus loin, et si vous avez de la chance, des rubis au fond. Mais les golems gardent jalousement ces richesses.",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Je tenterai ma chance. Merci !', 'action' => 'close'],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            QuestFixtures::class,
            MinesPnjFixtures::class,
        ];
    }
}
