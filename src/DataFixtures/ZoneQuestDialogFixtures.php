<?php

namespace App\DataFixtures;

use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Met à jour les dialogues de 6 PNJ pour proposer les quêtes de zone secondaires.
 *
 * Forêt : Diane la Chasseuse (meute), Sylvain le Garde (venin)
 * Mines : Durgan le Vieux Prospecteur (automates)
 * Marais : Bran le Chasseur (prime), Oswald le Pêcheur (appât)
 * Montagne : Kaelen l'Éclaireur (menace aérienne)
 */
class ZoneQuestDialogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $pnjQuests = [
            ['ref' => 'forest_pnj_4', 'quest' => 'quest_zone_foret_meute', 'dialog' => [
                'greeting' => 'Chut ! Vous allez faire fuir le gibier. Je suis Diane, chasseuse. Je traque les créatures de cette forêt pour protéger les voyageurs... et pour gagner ma vie, bien sûr.',
                'ask' => 'Vous avez besoin d\'aide ?',
                'leave' => 'Pardon. Je m\'en vais.',
                'intro' => "Justement, oui. Les loups sont de plus en plus agressifs ces derniers temps. Ils s'approchent des sentiers et attaquent les voyageurs. Le pire, c'est leur chef — un loup alpha, énorme et féroce.",
                'volunteer' => 'Je peux m\'en occuper.',
                'decline' => 'Pas maintenant.',
                'offer' => "Éliminez trois loups et le loup alpha qui les mène. En échange, je vous donnerai un arc — ça vous sera plus utile qu'à moi, j'en ai trois de rechange.",
                'accept' => 'J\'accepte la chasse.',
                'later' => 'Je réfléchirai.',
                'progress' => 'La meute rôde toujours ? Restez sur vos gardes — les loups attaquent en groupe. Cherchez-les au nord-est de la forêt, près des clairières.',
                'done' => 'La meute est dispersée ? Excellent travail ! Les sentiers seront plus sûrs maintenant. Tenez, cet arc a bien servi sa précédente propriétaire.',
                'bye' => 'Merci, Diane. Bonne chasse !',
            ]],
            ['ref' => 'forest_pnj_0', 'quest' => 'quest_zone_foret_venin', 'dialog' => [
                'greeting' => 'Halte, voyageur. Bienvenue dans la Forêt des murmures. Les bois sont dangereux pour les imprudents, mais recèlent bien des trésors pour qui sait chercher.',
                'ask' => 'Des problèmes en ce moment ?',
                'leave' => 'Je suis de passage. Au revoir.',
                'intro' => "Des serpents venimeux et des scorpions se sont installés près de l'entrée de la forêt. Les voyageurs se font mordre avant même d'atteindre la clairière. Je ne peux pas quitter mon poste...",
                'volunteer' => 'Je m\'en charge.',
                'decline' => 'Pas pour l\'instant.',
                'offer' => 'Tuez trois serpents venimeux et deux scorpions près des sentiers. Je vous donnerai des antidotes en récompense — vous en aurez besoin si vous allez plus loin dans la forêt.',
                'accept' => 'Considérez-les déjà morts.',
                'later' => 'Peut-être plus tard.',
                'progress' => 'Les créatures venimeuses traînent surtout aux abords des sentiers. Soyez prudent — leur venin agit vite.',
                'done' => 'Les sentiers sont dégagés ? Parfait ! Les voyageurs pourront passer en sécurité. Tenez, ces antidotes sont pour vous.',
                'bye' => 'Merci, Sylvain.',
            ]],
            ['ref' => 'mines_pnj_4', 'quest' => 'quest_zone_mines_automates', 'dialog' => [
                'greeting' => "Ha ! Un visiteur dans mes galeries... Je suis Durgan. Ça fait quarante ans que je creuse ici. J'ai vu des choses que personne ne croirait.",
                'ask' => 'Quelque chose vous tracasse ?',
                'leave' => 'Bonne continuation, Durgan.',
                'intro' => "Les automates rouillés des galeries profondes... ils se sont déréglés. Avant, ils gardaient les couloirs sans broncher. Maintenant, ils attaquent tout ce qui bouge. Impossible d'accéder aux filons les plus riches.",
                'volunteer' => 'Je peux dégager le passage.',
                'decline' => 'C\'est trop dangereux pour moi.',
                'offer' => "Détruisez trois automates rouillés et deux golems de pierre dans les galeries profondes. En échange, je partagerai du minerai d'argent que j'ai mis de côté.",
                'accept' => 'Marché conclu.',
                'later' => 'Je verrai plus tard.',
                'progress' => 'Les automates patrouillent les galeries centrales. Méfiez-vous des golems de pierre — la magie de feu fait des merveilles contre eux.',
                'done' => "Les galeries sont accessibles ? Formidable ! Voilà quarante ans que je n'avais pas pu creuser aussi profond. Tenez, votre part d'argent !",
                'bye' => 'Merci Durgan. Bonne prospection !',
            ]],
            ['ref' => 'marais_pnj_2', 'quest' => 'quest_zone_marais_prime', 'dialog' => [
                'greeting' => "Vous êtes courageux de venir jusqu'ici. Je suis Bran, chasseur de prime. Le marais regorge de créatures dangereuses — zombies errants, araignées géantes, et ces maudites banshees.",
                'ask' => 'Vous avez du travail pour moi ?',
                'leave' => 'Bonne chasse.',
                'intro' => "Du travail ? J'en ai toujours. Les zombies se multiplient dans les sentiers du marais, et des golems champignon bloquent les passages. J'ai une prime pour quiconque les élimine.",
                'volunteer' => 'Je suis volontaire.',
                'decline' => 'Trop risqué pour moi.',
                'offer' => "Éliminez quatre zombies et deux golems champignon dans les marécages. La prime est de 55 pièces d'or, plus des antidotes — le poison du marais est vicieux.",
                'accept' => 'J\'accepte la prime.',
                'later' => 'Je reviendrai.',
                'progress' => 'Les zombies traînent dans les zones humides au sud. Les golems champignon préfèrent les souches pourries. Gardez des antidotes sur vous.',
                'done' => 'Vous les avez eus ? Bien joué ! Voilà votre prime. Revenez quand vous voulez — il y a toujours des créatures à chasser dans ce fichu marais.',
                'bye' => 'Merci, Bran.',
            ]],
            ['ref' => 'marais_pnj_3', 'quest' => 'quest_zone_marais_appat', 'dialog' => [
                'greeting' => 'Hé, doucement ! Vous allez effrayer les poissons. Je suis Oswald. Oui, je pêche dans le marais — ça vous étonne ?',
                'ask' => 'Je peux vous aider ?',
                'leave' => 'Je vous laisse à votre pêche.',
                'intro' => "M'aider ? En fait... oui ! Je prépare un appât spécial pour attirer les gros poissons des profondeurs. Il me faut des champignons vénéneux — ceux avec des taches violettes.",
                'volunteer' => 'Je peux en récolter.',
                'decline' => 'Désolé, pas le temps.',
                'offer' => "Il m'en faudrait cinq. Le poison attire les anguilles géantes comme rien d'autre. En échange, je vous donnerai des potions de soin — les eaux du marais sont traîtresses.",
                'accept' => 'Cinq champignons, c\'est noté.',
                'later' => 'Peut-être une autre fois.',
                'progress' => 'Les champignons vénéneux poussent près des souches pourries et des racines immergées. Attention — les vénéneux ont des reflets violets.',
                'done' => "Cinq champignons ! Parfait, c'est exactement ce qu'il me faut. Avec ça, je vais préparer le meilleur appât du marais. Tenez, vos potions !",
                'bye' => 'Bonne pêche, Oswald !',
            ]],
            ['ref' => 'montagne_pnj_4', 'quest' => 'quest_zone_montagne_aerienne', 'dialog' => [
                'greeting' => 'Halte ! Je suis Kaelen, éclaireur de la garde de Ventombre. Au-delà de ce point, seuls les combattants aguerris survivent.',
                'ask' => 'Vous avez une mission pour moi ?',
                'leave' => 'Je suis prêt, laissez-moi passer.',
                'intro' => "Justement. Les griffons et les gargouilles ont envahi les sentiers d'altitude. Mes éclaireurs ne peuvent plus patrouiller — c'est trop dangereux.",
                'volunteer' => 'Je suis votre homme.',
                'decline' => 'C\'est au-dessus de mes forces.',
                'offer' => "Éliminez trois griffons et deux gargouilles sur les sentiers d'altitude. Les gargouilles résistent aux coups physiques — utilisez la magie. En récompense : une amulette d'argent de Ventombre.",
                'accept' => 'Mission acceptée.',
                'later' => 'Je reviendrai mieux équipé.',
                'progress' => "Les griffons patrouillent en meute au nord. Les gargouilles se perchent sur les parois rocheuses. N'oubliez pas : le feu fissure la pierre des gargouilles.",
                'done' => "Les sentiers sont dégagés ? Remarquable. Mes éclaireurs vont reprendre leurs rondes. Tenez, cette amulette d'argent — elle porte l'emblème de Ventombre.",
                'bye' => 'Merci, Kaelen. Pour Ventombre.',
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
     * Construit un dialogue PNJ à 6 nœuds pour une quête de zone.
     *
     * 0: Accueil → 1: Aiguillage → 2: Intro → 3: Offre → 4: En cours → 5: Terminée
     *
     * @param array{greeting: string, ask: string, leave: string, intro: string, volunteer: string, decline: string, offer: string, accept: string, later: string, progress: string, done: string, bye: string} $t
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
            ForestPnjFixtures::class,
            MinesPnjFixtures::class,
            MaraisPnjFixtures::class,
            MontagnePnjFixtures::class,
        ];
    }
}
