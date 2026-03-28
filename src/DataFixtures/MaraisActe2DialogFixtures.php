<?php

namespace App\DataFixtures;

use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Met à jour les dialogues des PNJ du Marais Brumeux pour l'Acte 2.
 *
 * Morwen la Voyante : lance la chaîne Fragment Marais (quêtes 1, 2, 3, 4).
 * Fergus l'Herboriste : dialogue lié à la quête 2 (Remèdes des Profondeurs).
 */
class MaraisActe2DialogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Quest $qBrumes */
        $qBrumes = $this->getReference('quest_acte2_marais_brumes', Quest::class);
        /** @var Quest $qIngredients */
        $qIngredients = $this->getReference('quest_acte2_marais_ingredients', Quest::class);
        /** @var Quest $qGardiens */
        $qGardiens = $this->getReference('quest_acte2_marais_gardiens', Quest::class);
        /** @var Quest $qFragment */
        $qFragment = $this->getReference('quest_acte2_marais_fragment', Quest::class);
        /** @var Quest $qCristal */
        $qCristal = $this->getReference('quest_acte1_cristal', Quest::class);

        $acte2Ids = [
            'cristal' => $qCristal->getId(),
            'brumes' => $qBrumes->getId(),
            'ingredients' => $qIngredients->getId(),
            'gardiens' => $qGardiens->getId(),
            'fragment' => $qFragment->getId(),
        ];

        /** @var Pnj $morwen */
        $morwen = $this->getReference('marais_pnj_0', Pnj::class);
        $morwen->setDialog($this->createMorwenDialog($acte2Ids));

        /** @var Pnj $fergus */
        $fergus = $this->getReference('marais_pnj_1', Pnj::class);
        $fergus->setDialog($this->createFergusDialog($acte2Ids));

        $manager->flush();
    }

    private function createMorwenDialog(array $ids): array
    {
        return [
            // 0 — Accueil
            [
                'text' => "Les brumes vous ont laissé passer... c'est qu'elles ont quelque chose à vous montrer. Je suis Morwen. Je lis dans les vapeurs du marais depuis bien longtemps.",
                'choices' => [
                    ['text' => 'Que voyez-vous dans les brumes ?', 'action' => 'next'],
                    ['text' => 'Je ne fais que passer.', 'action' => 'close'],
                ],
            ],
            // 1 — Aiguillage conditionnel
            [
                'conditional_next' => [
                    ['next' => 10, 'next_condition' => ['quest' => [$ids['fragment']]]],
                    ['next' => 8, 'next_condition' => ['quest_prerequisites_met' => [$ids['fragment']], 'quest_not' => [$ids['fragment']]]],
                    ['next' => 7, 'next_condition' => ['quest_active' => [$ids['gardiens']]]],
                    ['next' => 6, 'next_condition' => ['quest' => [$ids['ingredients']], 'quest_not' => [$ids['gardiens']]]],
                    ['next' => 5, 'next_condition' => ['quest_active' => [$ids['ingredients']]]],
                    ['next' => 4, 'next_condition' => ['quest' => [$ids['brumes']], 'quest_not' => [$ids['ingredients']]]],
                    ['next' => 2, 'next_condition' => ['quest' => [$ids['cristal']], 'quest_not' => [$ids['brumes']]]],
                    ['next' => 11],
                ],
                'text' => '',
            ],
            // 2 — Proposer quête "Les Brumes s'épaississent"
            [
                'text' => "Attendez... vous portez la marque du Cristal d'Améthyste. Les brumes me l'avaient annoncé. Depuis quelques jours, elles sont plus denses, plus froides. Quelque chose s'éveille au cœur du marais, quelque chose d'ancien et de puissant. J'ai besoin de votre aide.",
                'choices' => [
                    ['text' => 'Que puis-je faire ?', 'action' => 'quest_offer', 'data' => ['quest' => $ids['brumes']]],
                    ['text' => 'Pas maintenant.', 'action' => 'close'],
                ],
            ],
            // 3 — (reserved)
            [
                'text' => '',
            ],
            // 4 — Proposer quête "Remèdes des Profondeurs"
            [
                'text' => "Les brumes enchantées protègent le cœur du marais. Pour les dissiper, j'ai besoin d'un onguent ancestral. Il me faut des champignons vénéneux et des racines de marais — Fergus l'Herboriste pourra vous indiquer où en trouver. Rapportez-les-moi.",
                'choices' => [
                    ['text' => 'Je vais récolter les ingrédients.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['ingredients']]],
                    ['text' => 'Plus tard.', 'action' => 'close'],
                ],
            ],
            // 5 — Ingrédients en cours
            [
                'text' => "Avez-vous trouvé les champignons vénéneux et les racines ? Fergus l'Herboriste, dans la clairière, pourra vous aider à les localiser. Dépêchez-vous, les brumes s'épaississent.",
            ],
            // 6 — Proposer quête "Les Gardiens des Eaux Mortes"
            [
                'text' => "L'onguent est prêt et une partie de la brume s'est dissipée. Mais le chemin vers le cœur du marais est gardé par des créatures anciennes — des banshees et des ochus, corrompus par l'énergie qui émane des profondeurs. Il faut les vaincre pour ouvrir la voie.",
                'choices' => [
                    ['text' => 'Je vais les affronter.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['gardiens']]],
                    ['text' => 'C\'est trop dangereux.', 'action' => 'close'],
                ],
            ],
            // 7 — Gardiens en cours
            [
                'text' => 'Les gardiens rôdent encore dans les profondeurs du marais. Éliminez les banshees et les ochus pour dégager le passage. Soyez prudent, leurs cris sont mortels.',
            ],
            // 8 — Proposer quête "Le Fragment des Brumes"
            [
                'text' => "Le passage est libre ! Au fond du marais, il y a un bassin ancien — le Bassin des Brumes éternelles. J'y vois un éclat de cristal bleu-gris, enveloppé de vapeur glaciale. C'est le même type d'énergie que votre Cristal d'Améthyste. Allez le récupérer.",
                'choices' => [
                    ['text' => 'J\'y vais immédiatement.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['fragment']]],
                    ['text' => 'Je reviendrai plus tard.', 'action' => 'close'],
                ],
            ],
            // 9 — (reserved)
            [
                'text' => '',
            ],
            // 10 — Fragment récupéré (épilogue Acte 2 Marais)
            [
                'text' => "Le Fragment des Brumes... Les vapeurs du marais se sont apaisées depuis que vous l'avez retiré du bassin. Ce cristal résonne avec les autres fragments que vous portez. Les brumes me murmurent qu'il en reste encore, cachés dans des terres que vous n'avez pas encore foulées, {{player_name}}.",
            ],
            // 11 — Dialogue normal (avant Acte 2)
            [
                'text' => 'Ce marais est ancien, bien plus que les villages alentour. Des esprits y errent, prisonniers de leur propre chagrin. Les banshees que vous croiserez ne sont pas de simples monstres — ce sont des âmes brisées. Et au cœur des eaux stagnantes dort quelque chose de plus ancien encore...',
                'choices' => [
                    ['text' => 'Merci pour ces mises en garde.', 'action' => 'close'],
                ],
            ],
        ];
    }

    private function createFergusDialog(array $ids): array
    {
        return [
            // 0 — Accueil
            [
                'text' => 'Les plantes de ce marais sont redoutables pour les novices, mais entre de bonnes mains, elles guérissent presque tout. Je suis Fergus, herboriste depuis trois générations. Besoin de quelque chose ?',
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Parlez-moi du marais', 'action' => 'next'],
                    ['text' => 'Non merci.', 'action' => 'close'],
                ],
            ],
            // 1 — Aiguillage conditionnel
            [
                'conditional_next' => [
                    ['next' => 4, 'next_condition' => ['quest' => [$ids['ingredients']]]],
                    ['next' => 3, 'next_condition' => ['quest_active' => [$ids['ingredients']]]],
                    ['next' => 2, 'next_condition' => ['quest' => [$ids['brumes']], 'quest_not' => [$ids['ingredients']]]],
                    ['next' => 5],
                ],
                'text' => '',
            ],
            // 2 — Après brumes, indice sur les ingrédients
            [
                'text' => "Morwen vous a parlé de l'onguent ? Les champignons vénéneux poussent près des souches mortes, au sud du marais. Quant aux racines, elles s'enfouissent dans les berges boueuses à l'est. Faites attention aux araignées en chemin.",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci du conseil !', 'action' => 'close'],
                ],
            ],
            // 3 — Ingrédients en cours
            [
                'text' => 'Vous cherchez encore les champignons et les racines ? Les champignons vénéneux sont reconnaissables à leur chapeau violet, près des eaux stagnantes. Les racines de marais sont noueuses et brunâtres, enfoncées dans la vase. Bonne cueillette !',
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci !', 'action' => 'close'],
                ],
            ],
            // 4 — Ingrédients terminés
            [
                'text' => "Vous avez trouvé tout ce qu'il fallait pour l'onguent de Morwen ? Formidable ! Ces plantes du marais sont parmi les plus puissantes que je connaisse. Morwen saura en tirer le meilleur parti.",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci Fergus !', 'action' => 'close'],
                ],
            ],
            // 5 — Dialogue normal (avant Acte 2)
            [
                'text' => "Le Marais Brumeux est riche en champignons vénéneux et en racines noueuses. Les araignées tissent leurs toiles entre les arbres morts, et les ochus se terrent dans les eaux profondes. Si vous cherchez des ingrédients rares, c'est l'endroit idéal — mais ne vous éloignez pas trop du sentier.",
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
            MaraisPnjFixtures::class,
        ];
    }
}
