<?php

namespace App\DataFixtures;

use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Met à jour les dialogues des PNJ de la Forêt des murmures pour l'Acte 2.
 *
 * Thadeus l'Ermite : lance la chaîne Fragment Forêt (quêtes 1, 2, 4).
 * Elara l'Herboriste : dialogue lié à la quête 3 (Remède Ancestral).
 */
class ForestActe2DialogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer les IDs des quêtes Acte 2
        /** @var Quest $qMurmures */
        $qMurmures = $this->getReference('quest_acte2_foret_murmures', Quest::class);
        /** @var Quest $qPurification */
        $qPurification = $this->getReference('quest_acte2_foret_purification', Quest::class);
        /** @var Quest $qRemede */
        $qRemede = $this->getReference('quest_acte2_foret_remede', Quest::class);
        /** @var Quest $qFragment */
        $qFragment = $this->getReference('quest_acte2_foret_fragment', Quest::class);
        /** @var Quest $qCristal */
        $qCristal = $this->getReference('quest_acte1_cristal', Quest::class);

        $acte2Ids = [
            'cristal' => $qCristal->getId(),
            'murmures' => $qMurmures->getId(),
            'purification' => $qPurification->getId(),
            'remede' => $qRemede->getId(),
            'fragment' => $qFragment->getId(),
        ];

        // Mettre à jour Thadeus l'Ermite (forest_pnj_2)
        /** @var Pnj $thadeus */
        $thadeus = $this->getReference('forest_pnj_2', Pnj::class);
        $thadeus->setDialog($this->createThadeusDialog($acte2Ids));

        // Mettre à jour Elara l'Herboriste (forest_pnj_1)
        /** @var Pnj $elara */
        $elara = $this->getReference('forest_pnj_1', Pnj::class);
        $elara->setDialog($this->createElaraDialog($acte2Ids));

        $manager->flush();
    }

    private function createThadeusDialog(array $ids): array
    {
        return [
            // 0 — Accueil
            [
                'text' => "Hmm... encore un visiteur. Les arbres m'ont prévenu de votre arrivée. Ils murmurent, vous savez. C'est pour cela que cette forêt porte ce nom.",
                'choices' => [
                    ['text' => 'Les arbres murmurent ?', 'action' => 'next'],
                    ['text' => 'Je ne voulais pas vous déranger.', 'action' => 'close'],
                ],
            ],
            // 1 — Aiguillage conditionnel
            [
                'conditional_next' => [
                    // Fragment récupéré → épilogue
                    ['next' => 10, 'next_condition' => ['quest' => [$ids['fragment']]]],
                    // Fragment proposable (remède terminé)
                    ['next' => 8, 'next_condition' => ['quest_prerequisites_met' => [$ids['fragment']], 'quest_not' => [$ids['fragment']]]],
                    // Remède en cours
                    ['next' => 7, 'next_condition' => ['quest_active' => [$ids['remede']]]],
                    // Purification terminée → proposer remède
                    ['next' => 6, 'next_condition' => ['quest' => [$ids['purification']], 'quest_not' => [$ids['remede']]]],
                    // Purification en cours
                    ['next' => 5, 'next_condition' => ['quest_active' => [$ids['purification']]]],
                    // Murmures terminé → proposer purification
                    ['next' => 4, 'next_condition' => ['quest' => [$ids['murmures']], 'quest_not' => [$ids['purification']]]],
                    // Acte 1 terminé, pas encore commencé Acte 2 → proposer murmures
                    ['next' => 2, 'next_condition' => ['quest' => [$ids['cristal']], 'quest_not' => [$ids['murmures']]]],
                    // Par défaut → dialogue normal
                    ['next' => 11],
                ],
                'text' => '',
            ],
            // 2 — Proposer quête "Les Murmures s'intensifient"
            [
                'text' => "Attendez... vous portez l'empreinte du Cristal d'Améthyste. Je le sens. Depuis quelques jours, les murmures de la forêt ont changé. Ils sont plus intenses, plus urgents. Quelque chose se réveille dans les profondeurs des bois. Revenez me parler quand vous serez prêt — j'ai besoin de quelqu'un comme vous.",
                'choices' => [
                    ['text' => 'Je suis prêt, dites-moi tout.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['murmures']]],
                    ['text' => 'Pas maintenant.', 'action' => 'close'],
                ],
            ],
            // 3 — (unused, reserved)
            [
                'text' => '',
            ],
            // 4 — Proposer quête "Purifier la Corruption"
            [
                'text' => "Vous l'avez senti aussi, n'est-ce pas ? Une corruption ancienne s'est éveillée près de l'Arbre-Mère, au cœur de la forêt. Des créatures corrompues y rôdent maintenant — ondines déformées, ochus enragés, et même les feux follets sont devenus agressifs. Il faut les éliminer avant que la corruption ne se propage.",
                'choices' => [
                    ['text' => 'Je vais purifier la forêt.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['purification']]],
                    ['text' => 'C\'est trop dangereux pour moi.', 'action' => 'close'],
                ],
            ],
            // 5 — Purification en cours
            [
                'text' => "Les créatures corrompues rôdent toujours autour de l'Arbre-Mère. Éliminez les ondines, les ochus et le feu follet. La forêt compte sur vous.",
            ],
            // 6 — Proposer quête "Le Remède Ancestral"
            [
                'text' => "Bien joué ! La corruption faiblit, mais l'Arbre-Mère est toujours malade. Il existe un remède ancien... J'ai besoin de sauge et de mandragore. Elara l'Herboriste, dans la clairière centrale, saura préparer la décoction. Récoltez les plantes et portez-les-lui.",
                'choices' => [
                    ['text' => 'Je vais récolter les ingrédients.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['remede']]],
                    ['text' => 'Plus tard.', 'action' => 'close'],
                ],
            ],
            // 7 — Remède en cours
            [
                'text' => "Avez-vous trouvé la sauge et la mandragore ? Elara saura les préparer. Dépêchez-vous, l'Arbre-Mère s'affaiblit.",
            ],
            // 8 — Proposer quête "Le Fragment Sylvestre"
            [
                'text' => "Le remède a fonctionné ! L'Arbre-Mère reprend vie... Et regardez ! Ses racines ont révélé quelque chose d'enfoui — un éclat de cristal vert, ancien, pulsant de la même énergie que votre Cristal d'Améthyste. Allez le récupérer, près de ses racines au nord de la clairière.",
                'choices' => [
                    ['text' => 'J\'y vais immédiatement.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['fragment']]],
                    ['text' => 'Je reviendrai plus tard.', 'action' => 'close'],
                ],
            ],
            // 9 — (unused, reserved)
            [
                'text' => '',
            ],
            // 10 — Fragment récupéré (épilogue Acte 2 Forêt)
            [
                'text' => "Le Fragment Sylvestre... Vous l'avez. Ce cristal est un morceau de quelque chose de bien plus grand. Les murmures de la forêt me disent qu'il en existe d'autres, dispersés dans les terres lointaines. Votre quête ne fait que commencer, {{player_name}}.",
            ],
            // 11 — Dialogue normal (pas encore Acte 2)
            [
                'text' => 'Oh oui. Cette forêt est ancienne, bien plus ancienne que le Village de Lumière. Elle garde en mémoire les échos du passé. Parfois, la nuit, on peut apercevoir des esprits errants entre les arbres... Les feux follets ne sont pas de simples créatures, ce sont des fragments de souvenirs oubliés.',
                'choices' => [
                    ['text' => "C'est fascinant. Merci, vieil homme.", 'action' => 'close'],
                ],
            ],
        ];
    }

    private function createElaraDialog(array $ids): array
    {
        return [
            // 0 — Accueil
            [
                'text' => 'Les herbes de cette forêt possèdent des propriétés curatives remarquables. Je prépare des potions à partir de ce que je récolte ici. Puis-je vous aider ?',
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Parlez-moi de la forêt', 'action' => 'next'],
                    ['text' => 'Non merci.', 'action' => 'close'],
                ],
            ],
            // 1 — Aiguillage conditionnel
            [
                'conditional_next' => [
                    // Remède terminé → remerciement
                    ['next' => 4, 'next_condition' => ['quest' => [$ids['remede']]]],
                    // Remède en cours → encouragement
                    ['next' => 3, 'next_condition' => ['quest_active' => [$ids['remede']]]],
                    // Purification terminée mais remède pas encore accepté → indice
                    ['next' => 2, 'next_condition' => ['quest' => [$ids['purification']], 'quest_not' => [$ids['remede']]]],
                    // Par défaut → dialogue normal
                    ['next' => 5],
                ],
                'text' => '',
            ],
            // 2 — Après purification, indice sur le remède
            [
                'text' => "Vous avez affronté les créatures corrompues ? Courageux. J'ai entendu dire que Thadeus cherchait des plantes pour un remède ancestral. Si vous récoltez de la sauge et de la mandragore, je pourrai vous aider à les préparer.",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci du conseil !', 'action' => 'close'],
                ],
            ],
            // 3 — Remède en cours
            [
                'text' => 'Vous cherchez de la sauge et de la mandragore ? La sauge pousse près de la clairière sud, et la mandragore se trouve dans le sous-bois au nord. Bonne cueillette !',
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci !', 'action' => 'close'],
                ],
            ],
            // 4 — Remède terminé
            [
                'text' => "La décoction est prête et l'Arbre-Mère va mieux grâce à vous. C'est remarquable. Vous avez sauvé le cœur de cette forêt.",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci Elara !', 'action' => 'close'],
                ],
            ],
            // 5 — Dialogue normal (avant Acte 2)
            [
                'text' => "La rivière qui traverse la forêt regorge de poissons. Et si vous savez reconnaître les plantes, vous trouverez de la menthe, de la sauge et même de la mandragore près de la clairière nord. Attention toutefois aux loups qui rôdent à l'est.",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci du conseil !', 'action' => 'close'],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            QuestFixtures::class,
            ForestPnjFixtures::class,
        ];
    }
}
