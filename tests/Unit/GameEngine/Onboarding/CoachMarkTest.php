<?php

namespace App\Tests\Unit\GameEngine\Onboarding;

use App\Entity\App\Player;
use App\Enum\CoachMark;
use App\GameEngine\Onboarding\CoachMarkResolver;
use App\GameEngine\Tutorial\TutorialManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Le coach par ecran (ONB-17).
 *
 * **Ferme la dette D10.** Le jeu n'expliquait ses ecrans nulle part : le
 * tutoriel disait quoi faire, jamais ce qu'un ecran contient ni ce qu'un geste
 * coute. On ouvrait l'inventaire sans savoir que les emplacements de materia s'y
 * trouvaient.
 *
 * Les trois contraintes du cadrage sont ce que ce fichier verifie, et elles ne
 * protegent pas la meme chose :
 *
 * - **C1** — jamais un systeme inutilisable : expliquer une porte fermee
 *   enseigne une frustration, et le joueur retient la porte.
 * - **C2** — toujours le cout : sinon l'ecran apprend a decouvrir le prix en le
 *   payant.
 * - **C3** — a l'arrivee, jamais au temps ecoule : un encart differe se lirait
 *   comme une relance.
 */
class CoachMarkTest extends TestCase
{
    private TutorialManager&MockObject $tutorial;
    private CoachMarkResolver $resolver;

    protected function setUp(): void
    {
        $this->tutorial = $this->createMock(TutorialManager::class);
        $this->resolver = new CoachMarkResolver(
            $this->createMock(EntityManagerInterface::class),
            $this->tutorial,
        );
    }

    /**
     * Un encart s'affiche une fois, et ne revient jamais seul.
     */
    public function testAMarkIsShownOnceAndNeverReturns(): void
    {
        $player = new Player();

        self::assertSame(CoachMark::Zone, $this->resolver->forScreen($player, CoachMark::Zone));

        $player->markCoachSeen(CoachMark::Zone);

        self::assertNull($this->resolver->forScreen($player, CoachMark::Zone));
    }

    /**
     * La fermeture est idempotente.
     *
     * Un double-clic, un rechargement en cours de route : rien ne doit produire
     * deux entrees ni une seconde ecriture.
     */
    public function testDismissingTwiceChangesNothing(): void
    {
        $player = new Player();

        self::assertTrue($player->markCoachSeen(CoachMark::Zone));
        self::assertFalse($player->markCoachSeen(CoachMark::Zone));
    }

    /**
     * Chaque encart est independant des autres.
     */
    public function testMarksDoNotShadowEachOther(): void
    {
        $player = new Player();
        $player->markCoachSeen(CoachMark::Zone);

        self::assertNull($this->resolver->forScreen($player, CoachMark::Zone));
        self::assertSame(CoachMark::Inventory, $this->resolver->forScreen($player, CoachMark::Inventory));
    }

    /**
     * C1 — le hub et la guilde attendent la fin de l'acte I.
     *
     * Avant, le hub n'a rien a raconter : ni semaine, ni reprise, ni attente. Un
     * encart qui presenterait un tableau de bord vide serait la meilleure facon
     * d'apprendre au joueur a ne pas y revenir.
     */
    public function testTheHubStaysSilentUntilActOneIsDone(): void
    {
        $player = new Player();

        $this->tutorial->method('isCompleted')->willReturnOnConsecutiveCalls(false, true);

        self::assertNull($this->resolver->forScreen($player, CoachMark::Hub));
        self::assertSame(CoachMark::Hub, $this->resolver->forScreen($player, CoachMark::Hub));
    }

    /**
     * C1 — un encart a condition ne s'affiche pas sans elle.
     *
     * Le marche attend un objet vendable **et** un e-mail verifie ; le combat
     * attend le premier mannequin. Aucune des deux ne se deduit du joueur seul.
     */
    public function testAConditionalMarkWaitsForItsCondition(): void
    {
        $player = new Player();

        self::assertNull($this->resolver->forScreen($player, CoachMark::Market, false));
        self::assertSame(CoachMark::Market, $this->resolver->forScreen($player, CoachMark::Market, true));

        self::assertNull($this->resolver->forScreen($player, CoachMark::Combat, false));
        self::assertSame(CoachMark::Combat, $this->resolver->forScreen($player, CoachMark::Combat, true));
    }

    /**
     * La condition de l'appelant est ignoree quand l'encart n'en declare pas.
     *
     * C'est l'encart qui decide s'il attend quelque chose, pas l'appelant qui
     * decide de se faire confiance — sans quoi un `false` egare ferait taire un
     * encart inconditionnel sans que personne ne comprenne pourquoi.
     */
    public function testAnUnconditionalMarkIgnoresTheCaller(): void
    {
        self::assertSame(CoachMark::Zone, $this->resolver->forScreen(new Player(), CoachMark::Zone, false));
    }

    /**
     * Sans joueur, aucun encart.
     */
    public function testNoPlayerNoMark(): void
    {
        self::assertNull($this->resolver->forScreen(null, CoachMark::Zone));
    }

    /**
     * C2 — chaque encart dit ce que le geste coute.
     *
     * Le verifier sur les **cles** : un encart livre sans sa phrase de cout
     * s'afficherait avec la cle brute, ce que personne ne remarque en relecture
     * de code et que tout le monde voit a l'ecran.
     */
    public function testEveryMarkAnnouncesItsCost(): void
    {
        $catalog = (string) file_get_contents(\dirname(__DIR__, 4) . '/translations/messages.fr.json');

        foreach ([CoachMark::Zone, CoachMark::Combat, CoachMark::Inventory] as $mark) {
            self::assertStringContainsString('"cost"', $catalog);
            self::assertStringContainsString(sprintf('"%s"', $mark->value), $catalog, sprintf('L\'encart « %s » n\'a aucun texte livre.', $mark->value));
        }
    }

    /**
     * C3 — l'encart se decide au rendu, jamais au temps ecoule.
     *
     * Le controle porte sur la source : un `setTimeout` dans le controleur
     * Stimulus, ou un delai cote serveur, transformerait l'encart en relance —
     * *« vous n'avez pas encore compris ? »*.
     */
    public function testNothingIsTimeDelayed(): void
    {
        $controller = (string) file_get_contents(\dirname(__DIR__, 4) . '/assets/controllers/coach_mark_controller.js');
        $resolver = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/GameEngine/Onboarding/CoachMarkResolver.php');

        foreach (['setTimeout', 'setInterval'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $controller, 'L\'encart est differe : il se lirait comme une relance.');
        }

        self::assertStringNotContainsString('DateTime', $resolver, 'Le resolveur regarde l\'heure : l\'encart doit se decider a l\'arrivee.');
    }

    /**
     * Le coach est **par personnage**, pas par compte.
     *
     * Deux personnages du meme joueur decouvrent le jeu chacun a son rythme, et
     * le second a souvent une raison d'etre — essayer autre chose.
     */
    public function testTheCoachIsPerCharacter(): void
    {
        $first = new Player();
        $second = new Player();

        $first->markCoachSeen(CoachMark::Zone);

        self::assertTrue($first->hasSeenCoachMark(CoachMark::Zone));
        self::assertFalse($second->hasSeenCoachMark(CoachMark::Zone));
    }

    /**
     * Les dix encarts sont branches, et chacun a ses trois textes (ONB-17b).
     *
     * Un encart declare mais jamais inclus dans un gabarit ne se plaint pas : il
     * ne s'affiche simplement jamais, et rien ne le distingue d'un encart deja
     * lu. C'est le defaut que cette loi ferme — et elle vaut aussi pour le
     * texte, un encart sans sa phrase de cout s'affichant avec la cle brute.
     */
    public function testEveryMarkIsWiredAndWorded(): void
    {
        $root = \dirname(__DIR__, 4);

        $templates = '';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/templates', \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'twig') {
                $templates .= (string) file_get_contents($file->getPathname());
            }
        }

        /** @var array<string, mixed> $catalog */
        $catalog = json_decode((string) file_get_contents($root . '/translations/messages.fr.json'), true);
        $coach = $catalog['game']['coach'] ?? [];

        $unwired = [];
        $unworded = [];
        foreach (CoachMark::cases() as $mark) {
            if (!str_contains($templates, sprintf("coach_mark('%s'", $mark->value))) {
                $unwired[] = $mark->value;
            }
            foreach (['title', 'body', 'cost'] as $part) {
                if (!isset($coach[$mark->value][$part])) {
                    $unworded[] = $mark->value . '.' . $part;
                }
            }
        }

        self::assertSame([], $unwired, sprintf('Ces encarts ne sont inclus dans aucun gabarit : %s.', implode(', ', $unwired)));
        self::assertSame([], $unworded, sprintf('Ces textes d\'encart manquent : %s.', implode(', ', $unworded)));
    }
}
