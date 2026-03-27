<?php

namespace App\DataFixtures;

use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Met a jour les dialogues des PNJ du Marais Brumeux pour l'Acte 2.
 *
 * Morwenna la Voyante : lance la chaine Fragment Marais (quetes 1, 2, 3, 4).
 * Aldric le Pecheur : dialogue contextuel lie a la progression.
 */
class MaraisActe2DialogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Quest $qBrumes */
        $qBrumes = $this->getReference('quest_acte2_marais_brumes', Quest::class);
        /** @var Quest $qCreatures */
        $qCreatures = $this->getReference('quest_acte2_marais_creatures', Quest::class);
        /** @var Quest $qLivraison */
        $qLivraison = $this->getReference('quest_acte2_marais_livraison', Quest::class);
        /** @var Quest $qFragment */
        $qFragment = $this->getReference('quest_acte2_marais_fragment', Quest::class);
        /** @var Quest $qCristal */
        $qCristal = $this->getReference('quest_acte1_cristal', Quest::class);

        $ids = [
            'cristal' => $qCristal->getId(),
            'brumes' => $qBrumes->getId(),
            'creatures' => $qCreatures->getId(),
            'livraison' => $qLivraison->getId(),
            'fragment' => $qFragment->getId(),
        ];

        /** @var Pnj $morwenna */
        $morwenna = $this->getReference('marais_pnj_0', Pnj::class);
        $morwenna->setDialog($this->createMorwennaDialog($ids));

        /** @var Pnj $aldric */
        $aldric = $this->getReference('marais_pnj_1', Pnj::class);
        $aldric->setDialog($this->createAldricDialog($ids));

        $manager->flush();
    }

    private function createMorwennaDialog(array $ids): array
    {
        return [
            // 0 — Accueil
            [
                'text' => "Les brumes vous ont guide jusqu'a moi, etranger. Ce n'est pas un hasard. Le marais murmure des choses... des choses anciennes. Voulez-vous ecouter ?",
                'choices' => [
                    ['text' => 'Que disent les brumes ?', 'action' => 'next'],
                    ['text' => 'Non, je ne fais que passer.', 'action' => 'close'],
                ],
            ],
            // 1 — Aiguillage conditionnel
            [
                'conditional_next' => [
                    ['next' => 10, 'next_condition' => ['quest' => [$ids['fragment']]]],
                    ['next' => 8, 'next_condition' => ['quest_prerequisites_met' => [$ids['fragment']], 'quest_not' => [$ids['fragment']]]],
                    ['next' => 7, 'next_condition' => ['quest_active' => [$ids['livraison']]]],
                    ['next' => 6, 'next_condition' => ['quest' => [$ids['creatures']], 'quest_not' => [$ids['livraison']]]],
                    ['next' => 5, 'next_condition' => ['quest_active' => [$ids['creatures']]]],
                    ['next' => 4, 'next_condition' => ['quest' => [$ids['brumes']], 'quest_not' => [$ids['creatures']]]],
                    ['next' => 2, 'next_condition' => ['quest' => [$ids['cristal']], 'quest_not' => [$ids['brumes']]]],
                    ['next' => 11],
                ],
                'text' => '',
            ],
            // 2 — Proposer quete "Brumes inquietantes"
            [
                'text' => "Attendez... Vous portez l'empreinte du Cristal d'Amethyste. Les brumes s'agitent autour de vous. Elles sentent cette energie ancienne. Depuis quelques jours, le marais s'est assombri. Des creatures se rassemblent pres de l'ilot central, attirees par une force invisible. J'ai besoin de quelqu'un comme vous.",
                'choices' => [
                    ['text' => 'Dites-moi ce que je dois faire.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['brumes']]],
                    ['text' => 'Pas maintenant.', 'action' => 'close'],
                ],
            ],
            // 3 — (reserve)
            [
                'text' => '',
            ],
            // 4 — Proposer quete "Purger les brumes"
            [
                'text' => "Vous l'avez ressenti, n'est-ce pas ? Les brumes ne sont pas naturelles. Des banshees hurlent la nuit, des araignees geantes ont tisse leurs toiles sur les sentiers, et les ochus se multiplient sans controle. Il faut purger ces creatures pour affaiblir la brume. C'est la seule facon d'atteindre ce qui se cache au centre.",
                'choices' => [
                    ['text' => 'Je vais purger le marais.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['creatures']]],
                    ['text' => 'Je ne suis pas encore pret.', 'action' => 'close'],
                ],
            ],
            // 5 — Creatures en cours
            [
                'text' => "Les creatures rodent toujours dans le marais. Les banshees pres des saules morts, les araignees entre les racines, et les ochus au bord des etangs. Debarrassez-nous d'eux.",
            ],
            // 6 — Proposer quete "L'Offrande au marais"
            [
                'text' => "Les brumes faiblissent, je le sens. Mais pour localiser precisement la source de cette energie, j'ai besoin de realiser un rituel de divination. Il me faut de la sauge sacree et de la mandragore des profondeurs. Recoltez-les et rapportez-les-moi.",
                'choices' => [
                    ['text' => 'Je vais les trouver.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['livraison']]],
                    ['text' => 'Plus tard.', 'action' => 'close'],
                ],
            ],
            // 7 — Livraison en cours
            [
                'text' => "Avez-vous la sauge et la mandragore ? Sans elles, le rituel est impossible. La sauge pousse pres des claitieres humides, et la mandragore s'enfouit dans les sols riches.",
            ],
            // 8 — Proposer quete "Le Fragment des Brumes"
            [
                'text' => "Le rituel a fonctionne ! J'ai vu... un eclat de cristal bleu, enfoui sous l'ilot central du marais. Il pulse de la meme energie que votre Cristal d'Amethyste, mais teinte par les brumes. Allez le recuperer, {{player_name}}. Vite, avant que les brumes ne l'engloutissent a nouveau.",
                'choices' => [
                    ['text' => 'J\'y vais immediatement.', 'action' => 'quest_offer', 'data' => ['quest' => $ids['fragment']]],
                    ['text' => 'Je reviendrai plus tard.', 'action' => 'close'],
                ],
            ],
            // 9 — (reserve)
            [
                'text' => '',
            ],
            // 10 — Fragment recupere (epilogue Acte 2 Marais)
            [
                'text' => "Le Fragment des Brumes... Vous l'avez arrache aux tenebres du marais. Ce cristal est un morceau d'un tout bien plus grand. Les brumes me montrent d'autres fragments, disperses dans des terres lointaines. Les montagnes a l'est gardent peut-etre le prochain secret, {{player_name}}.",
            ],
            // 11 — Dialogue normal (pas encore Acte 2)
            [
                'text' => "Le marais cache bien des secrets sous ses eaux sombres. Des creatures rodent, certaines aussi vieilles que le monde. Et au coeur de cet endroit, quelque chose pulse... une energie que je n'avais pas ressentie depuis longtemps.",
                'choices' => [
                    ['text' => "Merci de l'avertissement.", 'action' => 'close'],
                ],
            ],
        ];
    }

    private function createAldricDialog(array $ids): array
    {
        return [
            // 0 — Accueil
            [
                'text' => "Chut... vous allez faire fuir les poissons. Enfin, ce qu'il en reste. Depuis que les brumes se sont epaissies, meme les carpes se font rares. Vous cherchez quelque chose ?",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Parlez-moi du marais', 'action' => 'next'],
                    ['text' => 'Non merci.', 'action' => 'close'],
                ],
            ],
            // 1 — Aiguillage conditionnel
            [
                'conditional_next' => [
                    ['next' => 4, 'next_condition' => ['quest' => [$ids['fragment']]]],
                    ['next' => 3, 'next_condition' => ['quest' => [$ids['creatures']]]],
                    ['next' => 2, 'next_condition' => ['quest' => [$ids['brumes']]]],
                    ['next' => 5],
                ],
                'text' => '',
            ],
            // 2 — Apres quete brumes
            [
                'text' => "Morwenna vous a parle ? Elle voit des choses, cette femme. Moi, je vois surtout que les poissons ont disparu et que les brumes sentent le soufre. Soyez prudent la-bas.",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci du conseil.', 'action' => 'close'],
                ],
            ],
            // 3 — Apres purge creatures
            [
                'text' => "Depuis que vous avez nettoye le marais, les poissons reviennent un peu. Les banshees ne hurlent plus la nuit non plus. C'est un progres. Morwenna dit que c'est pas fini, mais moi je retrouve enfin un peu de calme.",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Content que ca aille mieux.', 'action' => 'close'],
                ],
            ],
            // 4 — Fragment recupere
            [
                'text' => "Les brumes se dissipent ! Le marais reprend vie, les carpes sont revenues. Vous avez fait quelque chose la-bas, pas vrai ? Morwenna sourit pour la premiere fois depuis des semaines. Merci, l'ami.",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Bonne peche, Aldric !', 'action' => 'close'],
                ],
            ],
            // 5 — Dialogue normal (avant Acte 2)
            [
                'text' => "Ce marais etait paisible autrefois. Mais depuis quelques semaines, les brumes ne se dissipent plus, meme en plein jour. Et la nuit... des lueurs etranges apparaissent pres de l'ilot central. Morwenna dit que c'est un signe. Moi, je dis juste que c'est mauvais pour la peche.",
                'choices' => [
                    ['text' => 'Voir la boutique', 'action' => 'open_shop', 'datas' => []],
                    ['text' => 'Merci pour les infos.', 'action' => 'close'],
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
