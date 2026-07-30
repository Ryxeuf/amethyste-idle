<?php

namespace App\Tests\Unit\GameEngine\Onboarding;

use App\Enum\CreationStep;
use App\GameEngine\Onboarding\CharacterDraft;
use PHPUnit\Framework\TestCase;

/**
 * Le tunnel d'entree en quatre pas (ONB-05).
 *
 * Ce qu'il remplace : deux formulaires administratifs d'affilee. Le second
 * demandait le nom, le peuple et l'apparence **sur le meme ecran** — trois
 * decisions de nature differente presentees comme une seule corvee, ou la seule
 * qui porte quelque chose (le peuple) se noyait entre un champ texte et une
 * palette de couleurs de cheveux.
 *
 * La loi la plus importante ici n'est pas la mecanique du tunnel : c'est
 * **qu'aucun pas ne demande une decision de build**. Un tunnel qui ferait
 * choisir un metier ou un element remettrait la classe a l'entree, sous une
 * forme plus jolie.
 */
class CharacterTunnelTest extends TestCase
{
    /**
     * Compte → nom → peuple → visage, dans cet ordre et sans trou.
     */
    public function testTheFourSteps(): void
    {
        self::assertSame(4, CreationStep::total());
        self::assertSame(
            [1, 2, 3, 4],
            array_map(static fn (CreationStep $s): int => $s->position(), CreationStep::cases()),
        );

        $step = CreationStep::Account;
        $walked = [$step];
        while (null !== ($step = $step->next())) {
            $walked[] = $step;
        }

        self::assertSame(CreationStep::cases(), $walked, 'La chaine des pas a un trou : un joueur s\'arreterait au milieu du tunnel.');
    }

    /**
     * Le retour arriere existe partout sauf au premier pas.
     *
     * Le compte precede la session de jeu : y « revenir » n'aurait aucun sens,
     * et proposer le lien ferait croire qu'on peut defaire une inscription.
     */
    public function testEveryStepButTheFirstCanGoBack(): void
    {
        self::assertNull(CreationStep::Account->previous());
        self::assertNull(CreationStep::Name->previous(), 'Le nom est le premier pas servi par le tunnel.');
        self::assertSame(CreationStep::Name, CreationStep::People->previous());
        self::assertSame(CreationStep::People, CreationStep::Face->previous());
    }

    /**
     * Un tunnel interrompu reprend ou il s'etait arrete.
     *
     * Sans cela, un onglet ferme couterait tout — et le tunnel serait pire que
     * le formulaire unique qu'il remplace.
     */
    public function testAnInterruptedTunnelResumes(): void
    {
        self::assertSame(CreationStep::Name, (new CharacterDraft())->firstIncompleteStep());
        self::assertSame(CreationStep::People, (new CharacterDraft('Elara'))->firstIncompleteStep());
        self::assertNull((new CharacterDraft('Elara', 'elfe'))->firstIncompleteStep());
    }

    /**
     * Le visage n'est jamais obligatoire.
     *
     * Un personnage sans apparence choisie reste un personnage, et bloquer la
     * creation sur une couleur de cheveux serait absurde.
     */
    public function testTheFaceIsNeverRequired(): void
    {
        self::assertTrue((new CharacterDraft('Elara', 'elfe'))->isReady());
    }

    /**
     * Aucune saisie n'est perdue par un aller-retour.
     *
     * Le brouillon se serialise en tableau — une session ne doit pas contenir
     * d'objet dont la classe pourrait changer sous elle — et doit revenir
     * identique.
     */
    public function testNothingIsLostInTheRoundTrip(): void
    {
        $draft = new CharacterDraft('Elara', 'elfe', 'body-2', 'hair-5', '#b4441f');

        self::assertEquals($draft, CharacterDraft::fromArray($draft->toArray()));
    }

    /**
     * Une session corrompue ne fait pas tomber le tunnel.
     *
     * Elle se lit comme un brouillon vide : le joueur recommence, ce qui est
     * desagreable, mais il ne rencontre pas une erreur qu'il ne peut pas
     * comprendre.
     */
    public function testACorruptedDraftReadsAsEmpty(): void
    {
        $draft = CharacterDraft::fromArray(['name' => 42, 'raceSlug' => ['nain'], 'body' => '   ']);

        self::assertNull($draft->name);
        self::assertNull($draft->raceSlug);
        self::assertNull($draft->body);
    }

    /**
     * **Aucun pas ne demande une decision de build** (A8).
     *
     * Ni metier, ni element, ni arme, ni destination. Le peuple porte une
     * capacite qui touche ce qu'on **sait**, jamais ce qu'on **produit** ; tout
     * le reste s'apprend en jouant, et le premier vrai choix est l'arme remise
     * a l'etape 1 de l'acte I.
     *
     * Le controle porte sur les **champs du brouillon** : c'est par la qu'une
     * decision de build entrerait, et une capture de gabarit passerait a cote
     * d'un champ ajoute sans ecran.
     */
    public function testNoStepAsksForABuildDecision(): void
    {
        $carried = array_keys((new CharacterDraft())->toArray());

        self::assertSame(
            ['name', 'raceSlug', 'body', 'hair', 'hairColor'],
            $carried,
            implode("\n", [
                'Le tunnel transporte autre chose que le nom, le peuple et le visage.',
                'Ni metier, ni element, ni arme, ni destination : ce serait une classe',
                'a l\'entree, sous une forme plus jolie (GAME_ONBOARDING, decision A8).',
            ]),
        );
    }

    /**
     * L'eveil mene a la zone, jamais au hub.
     *
     * Le premier ecran d'un joueur doit etre un lieu ou il peut agir, pas un
     * tableau de bord qui n'a encore rien a lui raconter (decision A4).
     */
    public function testTheAwakeningLeadsToTheZone(): void
    {
        $awakening = (string) file_get_contents(\dirname(__DIR__, 4) . '/templates/game/character/tunnel/awakening.html.twig');

        self::assertStringContainsString("path('app_game_zone')", $awakening);
        self::assertStringNotContainsString("path('app_game')", $awakening, 'L\'eveil mene au hub : il n\'a encore rien a raconter.');
        self::assertSame(1, substr_count($awakening, '<a '), 'L\'eveil propose plus d\'un chemin : un paragraphe, un bouton.');
    }
}
