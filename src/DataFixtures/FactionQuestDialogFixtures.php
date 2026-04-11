<?php

namespace App\DataFixtures;

use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Attache les quêtes de faction (avec récompense de réputation) à des PNJ
 * thématiquement liés à chacune des 4 factions du jeu.
 *
 * Mages : Antoine le Mage (pnj_18) — Cercle des Mages
 * Chevaliers : Sébastien le Chevalier (pnj_24) — Ordre des Chevaliers
 * Ombres : Aurélie l'Archère (pnj_17) — Confrérie des Ombres
 * Marchands : Chloé l'Exploratrice (pnj_26) — Guilde des Marchands
 */
class FactionQuestDialogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $pnjQuests = [
            ['ref' => 'pnj_18', 'quest' => 'quest_faction_mages_intro', 'dialog' => [
                'greeting' => 'Je suis Antoine, émissaire du Cercle des Mages. Nous étudions les manifestations arcaniques qui perturbent la région.',
                'ask' => 'Le Cercle a-t-il besoin d\'aide ?',
                'leave' => 'Je ne vous dérange pas plus longtemps.',
                'intro' => "Justement. Les élémentaires de feu et les feux follets multiplient les incidents dans les zones reculées. Le Cercle souhaite comprendre ce phénomène — et pour cela, nous avons besoin d'échantillons.",
                'volunteer' => 'Je peux m\'en charger.',
                'decline' => 'Ce n\'est pas pour moi.',
                'offer' => "Éliminez deux élémentaires de feu et deux feux follets. Rapportez les traces de leur essence — le Cercle saura la reconnaître. En retour, votre nom sera inscrit auprès de nos archivistes, et votre réputation auprès du Cercle grandira.",
                'accept' => 'Pour la connaissance.',
                'later' => 'Je réfléchirai.',
                'progress' => 'Soyez prudent : les élémentaires résistent au feu, mais cèdent à l\'eau et à la glace. Les feux follets fuient la lumière du jour.',
                'done' => 'Ces essences sont précieuses. Le Cercle saura s\'en souvenir — votre réputation auprès des Mages est assurée. Revenez nous voir, nous aurons d\'autres missions.',
                'bye' => 'Que les arcanes vous guident.',
            ]],
            ['ref' => 'pnj_24', 'quest' => 'quest_faction_chevaliers_intro', 'dialog' => [
                'greeting' => 'Halte, voyageur. Je suis Sébastien, chevalier de l\'Ordre. Nous défendons les faibles contre les ténèbres — c\'est notre serment.',
                'ask' => 'L\'Ordre recrute-t-il ?',
                'leave' => 'Bonne route, aventurier.',
                'intro' => "L'Ordre ne recrute pas à la légère. Mais si vous souhaitez prouver votre valeur, les morts-vivants qui souillent nos terres offrent une épreuve à votre mesure. Purgez-les, et vous serez reconnu comme allié de l'Ordre.",
                'volunteer' => 'Je relève le défi.',
                'decline' => 'Pas aujourd\'hui.',
                'offer' => "Terrassez trois squelettes et deux zombies. Je jugerai de votre courage à votre retour. En récompense, un bouclier frappé aux armes de l'Ordre, et votre nom gravé dans nos registres.",
                'accept' => 'Pour l\'honneur.',
                'later' => 'Je dois me préparer.',
                'progress' => 'Les morts-vivants craignent la lumière bénie et les armes contondantes. Ne flanchez pas — l\'Ordre ne tolère pas la faiblesse.',
                'done' => "Bien combattu. Votre valeur est reconnue par l'Ordre des Chevaliers — votre réputation parmi nous vient de croître. Revenez, d'autres épreuves vous attendent.",
                'bye' => 'Que votre lame reste droite.',
            ]],
            ['ref' => 'pnj_17', 'quest' => 'quest_faction_ombres_intro', 'dialog' => [
                'greeting' => 'Chut... Pas si fort. Je suis Aurélie. Disons que je travaille pour... des gens qui préfèrent rester dans l\'ombre.',
                'ask' => 'Vos amis ont-ils besoin d\'aide ?',
                'leave' => 'Je n\'ai rien entendu.',
                'intro' => "Peut-être. Un campement de gobelins espionne les routes marchandes pour le compte de brigands. Mes... associés de la Confrérie des Ombres souhaitent les faire disparaître avant qu'ils ne deviennent gênants.",
                'volunteer' => 'Je peux être discret.',
                'decline' => 'Je préfère ne pas me mêler de ça.',
                'offer' => "Éliminez quatre éclaireurs gobelins. Nulle trace, nulle gloire — juste le travail bien fait. La Confrérie récompense ses amis silencieux, et votre nom circulera dans les bons cercles.",
                'accept' => 'Considérez-les oubliés.',
                'later' => 'Peut-être plus tard.',
                'progress' => 'Rappelez-vous : ni témoins, ni bruit. Les gobelins se regroupent aux abords des routes — frappez vite, disparaissez.',
                'done' => 'Parfait. La Confrérie saura reconnaître votre discrétion — votre réputation auprès des Ombres s\'étoffe. D\'autres contrats suivront, pour qui sait tenir sa langue.',
                'bye' => 'On ne s\'est jamais parlé.',
            ]],
            ['ref' => 'pnj_26', 'quest' => 'quest_faction_marchands_intro', 'dialog' => [
                'greeting' => 'Bienvenue, voyageur ! Je suis Chloé, éclaireuse pour la Guilde des Marchands. Je cartographie les routes pour sécuriser nos caravanes.',
                'ask' => 'La Guilde a-t-elle besoin de bras ?',
                'leave' => 'Bonne continuation !',
                'intro' => "Toujours ! Les araignées et les rats géants pillent nos convois sur les routes de l'est. Nos caravaniers n'osent plus passer seuls — la Guilde offre une belle récompense à qui saura dégager les sentiers.",
                'volunteer' => 'Je m\'en occupe.',
                'decline' => 'Une autre fois.',
                'offer' => "Éliminez trois araignées et trois rats géants aux abords des routes. La Guilde des Marchands vous récompensera en or, en objets précieux, et votre nom sera inscrit sur les registres de la Guilde — la réputation ouvre bien des portes ici.",
                'accept' => 'Affaire conclue.',
                'later' => 'Je repasserai.',
                'progress' => 'Les araignées aiment les zones boisées, les rats pullulent près des granges. N\'oubliez pas : chaque caravane sauvée compte pour la Guilde.',
                'done' => 'Les routes sont plus sûres ! La Guilde des Marchands vous en est reconnaissante — votre réputation auprès de nous vient de monter. Revenez, nous aurons toujours des missions pour les bons éléments.',
                'bye' => 'Que le commerce vous soit favorable !',
            ]],
        ];

        foreach ($pnjQuests as $config) {
            /** @var Quest $quest */
            $quest = $this->getReference($config['quest'], Quest::class);
            /** @var Pnj $pnj */
            $pnj = $this->getReference($config['ref'], Pnj::class);
            $pnj->setDialog($this->buildQuestDialog($quest->getId(), $config['dialog']));
        }

        $manager->flush();
    }

    /**
     * Construit un dialogue PNJ à 6 nœuds pour une quête de faction.
     *
     * 0: Accueil → 1: Aiguillage → 2: Intro → 3: Offre → 4: En cours → 5: Terminée
     *
     * @param array{greeting: string, ask: string, leave: string, intro: string, volunteer: string, decline: string, offer: string, accept: string, later: string, progress: string, done: string, bye: string} $t
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildQuestDialog(int $questId, array $t): array
    {
        return [
            ['text' => $t['greeting'], 'choices' => [
                ['text' => $t['ask'], 'action' => 'next'],
                ['text' => $t['leave'], 'action' => 'close'],
            ]],
            ['conditional_next' => [
                ['next' => 5, 'next_condition' => ['quest' => [$questId]]],
                ['next' => 4, 'next_condition' => ['quest_active' => [$questId]]],
                ['next' => 2],
            ]],
            ['text' => $t['intro'], 'choices' => [
                ['text' => $t['volunteer'], 'action' => 'next'],
                ['text' => $t['decline'], 'action' => 'close'],
            ]],
            ['text' => $t['offer'], 'choices' => [
                ['text' => $t['accept'], 'action' => 'quest_offer', 'data' => ['quest' => $questId]],
                ['text' => $t['later'], 'action' => 'close'],
            ]],
            ['text' => $t['progress'], 'choices' => [
                ['text' => $t['bye'], 'action' => 'close'],
            ]],
            ['text' => $t['done'], 'choices' => [
                ['text' => $t['bye'], 'action' => 'close'],
            ]],
        ];
    }

    public function getDependencies(): array
    {
        return [
            QuestFixtures::class,
            PnjFixtures::class,
        ];
    }
}
