<?php

namespace App\Tests\Unit\Onboarding;

use App\Enum\CoachMark;
use App\Enum\CreationStep;
use App\Enum\TutorialStep;
use App\GameEngine\Progression\HomeSettlementResolver;
use PHPUnit\Framework\TestCase;

/**
 * Les invariants du plan d'onboarding (ONB-19).
 *
 * **Pourquoi un test de plus, alors que chaque jalon a deja le sien.** Les
 * tests de jalon repondent a « ce que j'ai ecrit marche-t-il ? ». Celui-ci
 * repond a une autre question : « le jalon **suivant** a-t-il defait ce que
 * celui-ci a etabli ? ». C'est la difference entre verifier une
 * implementation et tenir une doctrine, et rien ne la tient si personne ne
 * l'ecrit au meme endroit.
 *
 * Les invariants ci-dessous ont tous une propriete commune : **les perdre ne
 * casse rien**. Le jeu continue de fonctionner, les tests de jalon restent
 * verts, et l'on decouvre la regression en jouant — ou pas du tout. C'est
 * exactement le profil de defaut qui a produit les dettes D4, D5, D7, D8 et
 * D11 que ce plan a passe la journee a fermer.
 */
class OnboardingPlanContractTest extends TestCase
{
    /**
     * ONB-15 — aucune quete de l'arc `intro` ne depend d'une carte.
     *
     * Trois quetes visaient `map_id => 1`, c'est-a-dire la « carte de test » :
     * l'acte I etait bloque des sa premiere etape, sans erreur ni message. Le
     * pivot PBBG a supprime la carte navigable ; toute reference qui y revient
     * est morte a la naissance.
     */
    public function testNoIntroQuestDependsOnAMap(): void
    {
        $intro = $this->introArcSource();

        foreach (['map_id', 'coordinates'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $intro, sprintf(
                "L'arc `intro` reference « %s » : la carte navigable n'existe plus (ZON-21), et l'etape serait interminable.",
                $forbidden,
            ));
        }
    }

    /**
     * ONB-12 — la materia est garantie, accordee, puis lancee.
     *
     * Les trois, et par trois etapes distinctes. La recevoir sans l'accorder ne
     * se distingue pas d'un objet decoratif ; l'accorder sans la lancer
     * n'apprend pas qu'elle sert en combat. Et la chaine ne doit jamais nommer
     * la materia : elle derive de l'arbre choisi a l'etape 1.
     */
    public function testTheMateriaIsGrantedAttunedAndCast(): void
    {
        $intro = $this->introArcSource();

        foreach (["'act_one_materia' => true", "'gesture' => 'socket_materia'", "'gesture' => 'cast_spell'"] as $required) {
            self::assertStringContainsString($required, $intro, sprintf('L\'acte I ne contient plus « %s » : la materia redevient invisible.', $required));
        }

        self::assertSame(0, preg_match("/'genericItemSlug' => 'm1-/", $intro), 'L\'acte I nomme une materia : le choix de l\'etape 1 deviendrait decoratif.');
    }

    /**
     * ONB-08 — le parchemin est un cout, jamais un verrou.
     *
     * Les quatre conditions du § 6.3, et si l'une tombe le parchemin devient un
     * systeme de classes. Deux se verifient sur les donnees : **prix fixe pour
     * tout le monde** (aucun prerequis, aucune limite de nombre), et **aucun
     * parchemin payant sur le chemin critique** — les trois premiers sont
     * donnes en recompense de quete.
     */
    public function testTheScrollIsACostNeverALock(): void
    {
        $parchments = $this->source('src/DataFixtures/DomainParchmentFixtures.php');

        self::assertStringContainsString('PARCHMENT_PRICE', $parchments, 'Le prix unique des parchemins a disparu : un prix variable est un prerequis deguise.');

        foreach (['requirements', 'requiredSkill', 'race'] as $forbidden) {
            self::assertStringNotContainsString(sprintf("'%s' =>", $forbidden), $parchments, sprintf(
                'Un parchemin porte « %s » : il cesse d\'etre accessible a tout le monde (condition 1 du § 6.3).',
                $forbidden,
            ));
        }

        $intro = $this->introArcSource();
        foreach (['soldier-domain-parchment', 'herbalist-domain-parchment'] as $given) {
            self::assertStringContainsString($given, $intro, 'L\'acte I ne donne plus ses parchemins : un joueur sans gils serait bloque (condition 4).');
        }
    }

    /**
     * ONB-09 — le catalogue montre les arbres, jamais leurs nœuds.
     *
     * Un catalogue qui laisserait filtrer un nœud, un cout en points ou une
     * valeur ferait de la lecture une decision de build prise avant d'avoir
     * ouvert quoi que ce soit — exactement ce que le parchemin evite.
     */
    public function testTheCatalogueNeverExposesAClosedTree(): void
    {
        $card = $this->source('src/Dto/Domain/DomainCatalogCard.php');

        foreach (['requiredPoints', 'getSkills', 'canBeAcquired'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $card, sprintf(
                'La carte de catalogue porte « %s » : elle exposerait le contenu d\'un arbre ferme.',
                $forbidden,
            ));
        }
    }

    /**
     * ONB-13 et ONB-07 — aucun contenu gate par la race ni par le foyer.
     *
     * Les deux dispositifs enregistrent une orientation ; ils ne l'orientent
     * pas. Un foyer ou un peuple qui ouvrirait du contenu reintroduirait la
     * classe par la fenetre, et personne ne s'en apercevrait avant d'avoir un
     * joueur bloque.
     *
     * Le controle porte sur les **lecteurs** : chercher les mots-cles d'un `if`
     * serait contournable par accident, enumerer qui a le droit de lire ne
     * l'est pas.
     */
    public function testNeitherRaceNorHomeGatesAnyContent(): void
    {
        $homeReaders = [];
        $capabilityWriters = [];

        foreach ($this->sourceTree() as $relative => $source) {
            if (str_contains($source, 'getHomeZone(') || str_contains($source, 'claimHomeZone(')) {
                $homeReaders[] = $relative;
            }
            // Une capacite de peuple change ce qu'on **sait**. Un fichier qui la
            // lit et ecrit une quantite dans la foulee change ce qu'on produit.
            if (str_contains($source, 'RaceCapability::') && preg_match('/set(Life|Damage|Price|Quantity)\(/', $source)) {
                $capabilityWriters[] = $relative;
            }
        }

        sort($homeReaders);

        self::assertSame([
            'src/Entity/App/Player.php',
            'src/GameEngine/Progression/HomeSettlementResolver.php',
        ], $homeReaders, 'Le foyer d\'attache est lu ailleurs que la ou il est constate : il deviendrait une autorisation.');

        self::assertSame([], $capabilityWriters, 'Une capacite de peuple touche une quantite : elle doit changer ce qu\'on sait, jamais ce qu\'on produit.');

        self::assertSame('village-de-lumiere', HomeSettlementResolver::DEFAULT_HOME_SLUG, 'Le foyer par defaut n\'est plus le Fanal : un joueur sans activite distinctive se verrait attribuer un travail qu\'il n\'a pas fait.');
    }

    /**
     * ONB-14 — un seul etat d'onboarding.
     *
     * Deux etats ne peuvent plus diverger quand il n'y en a qu'un. Le second
     * compteur serait d'accord avec l'arc le premier jour, et ne divergerait
     * qu'apres — c'est precisement ainsi que D7 s'est installee.
     */
    public function testThereIsOnlyOneOnboardingState(): void
    {
        $writers = [];
        foreach ($this->sourceTree() as $relative => $source) {
            if (str_contains($source, 'setTutorialStep(')) {
                $writers[] = $relative;
            }
        }

        self::assertSame([], $writers, 'Un second etat d\'onboarding est ecrit quelque part : il finira par contredire l\'arc.');
        self::assertSame(10, TutorialStep::ARC_STEPS, 'Le tutoriel ne se projette plus sur les dix etapes de l\'arc.');
    }

    /**
     * ONB-05 — le tunnel ne demande aucune decision de build.
     *
     * Ni metier, ni element, ni arme, ni destination. Le controle porte sur ce
     * que le tunnel **transporte** : c'est par la qu'une telle decision
     * entrerait, et une capture de gabarit passerait a cote d'un champ ajoute
     * sans ecran.
     */
    public function testTheTunnelAsksForNoBuildDecision(): void
    {
        $draft = $this->source('src/GameEngine/Onboarding/CharacterDraft.php');

        foreach (['domain', 'element', 'weapon', 'trade', 'destination'] as $forbidden) {
            self::assertStringNotContainsString(sprintf('public ?string $%s', $forbidden), $draft, sprintf(
                'Le tunnel transporte « %s » : ce serait une classe a l\'entree, sous une forme plus jolie.',
                $forbidden,
            ));
        }

        self::assertSame(4, CreationStep::total(), 'Le tunnel n\'a plus ses quatre pas.');
    }

    /**
     * ONB-17 — le coach n'explique jamais un systeme inutilisable (C1).
     *
     * Le hub et la guilde attendent la fin de l'acte I ; le marche et le combat
     * attendent une condition que seul l'ecran connaît. Un encart qui
     * expliquerait une porte fermee enseigne une frustration, et le joueur
     * retient la porte, pas l'explication.
     */
    public function testTheCoachNeverExplainsAClosedSystem(): void
    {
        self::assertTrue(CoachMark::Hub->waitsForActOne());
        self::assertTrue(CoachMark::Guild->waitsForActOne());
        self::assertTrue(CoachMark::Market->needsCallerCondition());
        self::assertTrue(CoachMark::Combat->needsCallerCondition(), 'Le coach de combat s\'afficherait sur une vraie rencontre, ou lire coute des points de vie.');
    }

    /**
     * Le bloc de l'arc `intro` dans les fixtures de quete.
     */
    private function introArcSource(): string
    {
        $quests = $this->source('src/DataFixtures/QuestFixtures.php');

        $start = strpos($quests, "'quest_acte1_reveil' => [");
        $end = strpos($quests, "// --- Quetes d'evenement de Saison 1");
        self::assertNotFalse($start, 'L\'arc `intro` a disparu des fixtures.');
        self::assertNotFalse($end, 'La borne de fin de l\'arc `intro` a bouge : la loi ne verifierait plus le bon bloc.');

        return substr($quests, $start, $end - $start);
    }

    private function source(string $relative): string
    {
        $path = \dirname(__DIR__, 3) . '/' . $relative;
        self::assertFileExists($path, sprintf('« %s » a disparu : le plan d\'onboarding s\'y appuie.', $relative));

        return (string) file_get_contents($path);
    }

    /**
     * @return array<string, string> chemin relatif => source
     */
    private function sourceTree(): array
    {
        $root = \dirname(__DIR__, 3);
        $sources = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/src', \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $sources[substr($file->getPathname(), \strlen($root) + 1)] = (string) file_get_contents($file->getPathname());
            }
        }

        return $sources;
    }
}
