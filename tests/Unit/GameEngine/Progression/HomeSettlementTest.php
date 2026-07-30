<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\App\PlayerZoneActivity;
use App\Entity\App\Zone;
use App\EventListener\ActOneClosureListener;
use App\GameEngine\Progression\HomeSettlementResolver;
use PHPUnit\Framework\TestCase;

/**
 * Le foyer d'attache se gagne (ONB-13).
 *
 * GAME_ONBOARDING § 4.4 amende GAME_WORLD § 13.1. Le canon derivait le foyer de
 * la **race** : kit, destination et chaine de quetes decides avant toute
 * experience de jeu, et l'Elfe qui voulait miner pousse ailleurs. C'est une
 * classe deguisee — exactement ce que le projet a renonce a faire.
 *
 * La loi la plus importante de ce fichier n'est pas que le foyer soit bien
 * calcule : c'est **qu'il ne serve a rien d'autre qu'a dire bonjour**. Un foyer
 * qui ouvrirait du contenu reintroduirait la classe par la fenetre, et personne
 * ne s'en apercevrait avant d'avoir un joueur bloque.
 */
class HomeSettlementTest extends TestCase
{
    /**
     * Le foyer ne se lit jamais comme une autorisation.
     *
     * La liste est courte et doit le rester. Un fichier qui s'y ajoute doit
     * pouvoir repondre a une question : *est-ce que je decide de ce que le
     * joueur peut faire ?* Si oui, il n'a rien a faire ici.
     *
     * Le controle porte sur les **lecteurs**, pas sur les mots-cles d'un `if` :
     * chercher « home && can » serait contournable par accident. Enumerer qui a
     * le droit de lire ne l'est pas.
     */
    public function testTheHomeIsNeverReadAsAGate(): void
    {
        // `ActOneClosureListener` n'y figure pas, et c'est le point : il
        // **declenche** le constat sans jamais lire le resultat. Un declencheur
        // qui relirait le foyer serait deja en train d'en faire quelque chose.
        $allowed = [
            'src/Entity/App/Player.php',                              // il le porte
            'src/GameEngine/Progression/HomeSettlementResolver.php',  // il le constate
        ];

        $readers = [];
        foreach ($this->phpSources() as $relative => $source) {
            if (str_contains($source, 'getHomeZone(') || str_contains($source, 'claimHomeZone(')) {
                $readers[] = $relative;
            }
        }

        sort($readers);
        sort($allowed);

        self::assertSame($allowed, $readers, implode("\n", [
            'Le foyer d\'attache est lu ailleurs que la ou il est constate.',
            'Il n\'ouvre et ne ferme rien : le lire pour autoriser un acces reintroduirait',
            'la classe deguisee que GAME_ONBOARDING § 4.4 a precisement retiree.',
            'Si la lecture est un affichage, ajoutez le fichier a la liste avec sa raison.',
        ]));
    }

    /**
     * Le foyer se derive des gestes, jamais de la race.
     *
     * Le verifier par la **forme du code** plutot que par un scenario : c'est la
     * regression qu'on introduirait sans y penser, en « completant » le
     * resolveur avec le seul champ qui semble parler d'origine.
     */
    public function testTheHomeIsNeverDerivedFromTheRace(): void
    {
        $resolver = $this->source('src/GameEngine/Progression/HomeSettlementResolver.php');

        self::assertStringNotContainsString('getRace(', $resolver, 'Le foyer redevient une consequence de la race : c\'est l\'amendement § 4.4 annule.');
        self::assertStringContainsString('findBusiestFor(', $resolver, 'Le foyer ne se derive plus du travail accompli.');
    }

    /**
     * Sans activite distinctive, le Fanal.
     *
     * Le canon le reservait a l'Humain ; le cas humain devient le cas par
     * defaut de tout le monde. Un joueur qui n'a rien fait de distinctif n'a
     * rien decide, et le lui faire decider retroactivement serait pire que de
     * ne rien dire.
     */
    public function testTheDefaultHomeIsTheBeacon(): void
    {
        self::assertSame('village-de-lumiere', HomeSettlementResolver::DEFAULT_HOME_SLUG);
    }

    /**
     * Voyager n'est pas travailler.
     *
     * Sans cette distinction, le foyer de tout le monde serait la zone ou
     * l'acte I se termine — c'est-a-dire la meme pour tous, ce qui vide le
     * mecanisme de son objet.
     */
    public function testTravellingIsNotWorking(): void
    {
        $listener = $this->source('src/GameEngine/Zone/ZoneActivityListener.php');

        self::assertStringNotContainsString('PlayerTraveledEvent', $listener, 'Le voyage compte comme du travail : le foyer serait la derniere zone visitee.');
        self::assertStringContainsString('ZoneGatherEvent', $listener);
    }

    /**
     * Le constat se declenche au **rang** de la derniere etape, pas a un slug.
     *
     * Un slug ecrit dans le listener serait un second endroit ou vit l'ordre de
     * la chaine : NAR-20 en reecrira les libelles, et la cloture partirait au
     * mauvais moment sans que rien ne le signale.
     */
    public function testTheClosureIsTriggeredByRankNotByName(): void
    {
        $listener = $this->source('src/EventListener/ActOneClosureListener.php');

        self::assertSame(10, ActOneClosureListener::CLOSING_STEP);
        self::assertStringNotContainsString('quest_acte1', $listener, 'La cloture designe une quete par son nom : elle survivra mal a NAR-20.');
        self::assertStringContainsString('getArcOrder()', $listener);
    }

    /**
     * Le foyer ne se constate qu'une fois.
     *
     * L'arc `intro` est rejouable par personnage. Sans ce garde, un second
     * passage redonnerait le cran de renommee — et le refus est porte par
     * l'entite, pas par l'appelant, pour qu'un second chemin de cloture ne
     * puisse pas le contourner.
     */
    public function testAHomeIsClaimedOnlyOnce(): void
    {
        $player = new Player();
        $first = $this->zone('mines-profondes');
        $second = $this->zone('foret-des-murmures');

        self::assertFalse($player->hasClaimedHomeZone());
        self::assertTrue($player->claimHomeZone($first));
        self::assertFalse($player->claimHomeZone($second), 'Un second constat a ete accepte : le cran de renommee serait donne deux fois.');

        self::assertSame($first, $player->getHomeZone());
        self::assertTrue($player->hasClaimedHomeZone());
        self::assertNotNull($player->getHomeZoneClaimedAt());
    }

    /**
     * Un acte compte au moins pour un.
     *
     * `record(0)` viendrait d'un appelant qui croit ne rien ajouter ; il
     * ecrirait tout de meme une ligne, et une zone a zero acte se classerait
     * devant celles ou le joueur n'est jamais alle. Mieux vaut qu'un acte
     * enregistre soit un acte.
     */
    public function testAnActAlwaysCountsForAtLeastOne(): void
    {
        $activity = new PlayerZoneActivity(new Player(), $this->zone('mines-profondes'));

        self::assertSame(0, $activity->getActs());

        $activity->record(0);
        self::assertSame(1, $activity->getActs());

        $activity->record(3);
        self::assertSame(4, $activity->getActs());
    }

    private function zone(string $slug): Zone
    {
        $zone = new Zone();
        $zone->setSlug($slug);
        $zone->setName($slug);

        return $zone;
    }

    private function source(string $relative): string
    {
        return (string) file_get_contents(\dirname(__DIR__, 4) . '/' . $relative);
    }

    /**
     * @return array<string, string> chemin relatif => source
     */
    private function phpSources(): array
    {
        $root = \dirname(__DIR__, 4);
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
