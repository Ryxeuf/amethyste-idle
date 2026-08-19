<?php

namespace App\Tests\Integration\Reputation;

use App\Entity\App\Player;
use App\Enum\FactionRewardForm;
use App\GameEngine\Reputation\FactionTensionCatalog;
use App\GameEngine\Reputation\HostileConsequenceCatalog;
use App\GameEngine\Reputation\PatronageBonusResolver;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * FAC-10 — le contrat du plan factions.
 *
 * Chaque jalon a ses tests, et ils sont nombreux — vingt-deux fichiers couvrent
 * la tension, le patronage, l'Hostile, la contrefacon, la contrebande, le
 * receleur et les contrats de la Fonderie. Ce fichier n'en refait aucun.
 *
 * Il porte les invariants **transverses** : ceux qu'aucun test de jalon ne peut
 * voir, parce que chacun ne connait que son propre sous-systeme.
 *
 *  1. La tension est symetrique   → FactionTensionCatalogTest (l'axe livre, les refus)
 *                                   + ICI (la reciproque, lue dans les deux sens)
 *  2. Lateral partout             → FactionLadderContractTest (le cote **donnees**)
 *                                   + ICI (le cote **code** : un seul lecteur de statistique)
 *  3. Les bornes d'Hostile        → HostileConsequenceCatalogTest (vocabulaire ferme, boucle cœur)
 *                                   + ICI (la seconde borne : jamais une agression)
 *  4. Aucun geste ne nuit a autrui → ICI, et nulle part ailleurs
 *
 * @see FactionLadderContractTest pour la loi laterale du cote des donnees
 */
class FactionsPlanContractTest extends AbstractIntegrationTestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * L'index ne doit pas pourrir : chaque test cite dans le contrat existe.
     */
    public function testTheContractIndexNamesRealTests(): void
    {
        foreach ([
            'Unit/GameEngine/Reputation/FactionTensionCatalogTest.php',
            'Unit/GameEngine/Reputation/HostileConsequenceCatalogTest.php',
            'Unit/GameEngine/Reputation/HostileConsequenceResolverTest.php',
            'Unit/GameEngine/Reputation/CounterfeitServiceTest.php',
            'Unit/GameEngine/Reputation/ShadowsPlacementTest.php',
            'Unit/GameEngine/Reputation/ShadowsSmugglingTest.php',
            'Unit/GameEngine/Reputation/FactionGateTest.php',
            'Integration/Reputation/FactionLadderContractTest.php',
        ] as $test) {
            self::assertFileExists($this->root() . '/tests/' . $test);
        }
    }

    // =====================================================================
    // 1. La tension est symetrique
    // =====================================================================

    /**
     * **Ce qui monte chez l'un retire chez l'autre, dans les deux sens.**.
     *
     * Le catalogue verifie l'axe livre ; il ne verifie pas la **reciproque**.
     * Or `opponentOf()` parcourt les paires en lisant `left` puis `right`, si
     * bien qu'une paire ecrite a l'envers — ou un jour indexee par un seul de
     * ses membres — rendrait la tension a sens unique : la Fonderie couterait
     * au Cercle, et le Cercle ne couterait rien a la Fonderie. Le defaut serait
     * **muet**, et il donnerait un cote avantageux a l'axe doctrinal.
     */
    public function testTheAxisAnswersFromBothSides(): void
    {
        $catalog = new FactionTensionCatalog($this->root());

        foreach ($catalog->pairs() as $pair) {
            self::assertSame($pair['right'], $catalog->opponentOf($pair['left']));
            self::assertSame($pair['left'], $catalog->opponentOf($pair['right']));
            self::assertSame(
                $catalog->axisOf($pair['left']),
                $catalog->axisOf($pair['right']),
                'Les deux cotes d\'une paire ne nomment pas le meme axe.',
            );
        }
    }

    /**
     * Une maison hors tension n'a **pas** d'oppose, et c'est son identite —
     * la Guilde des Marchands vend aux deux. Une neutre qui repondrait quelque
     * chose ici ferait payer une decote a qui n'a rien renonce.
     */
    public function testANeutralHouseHasNoOpponent(): void
    {
        $catalog = new FactionTensionCatalog($this->root());

        foreach ($catalog->neutralFactions() as $slug) {
            self::assertNull($catalog->opponentOf($slug), sprintf('« %s » est neutre et pourtant opposee.', $slug));
        }
    }

    // =====================================================================
    // 2. Lateral partout — le cote code
    // =====================================================================

    /**
     * **Une seule classe lit une recompense de faction pour en tirer une
     * statistique.**.
     *
     * `FactionLadderContractTest` tient la loi du cote des **donnees** : aucune
     * recompense laterale ne nomme une statistique. Elle ne dit rien du cas
     * inverse — un second lecteur qui irait chercher `stat` dans le
     * `rewardData` d'une forme laterale, ou qui appliquerait le patronage une
     * deuxieme fois ailleurs dans le moteur.
     *
     * *Un systeme qui compte soigneusement ses points et laisse une porte de
     * service ne compte rien* (ARC-16a). Ici la porte de service serait un
     * second lecteur, et il ne se verrait dans aucune donnee.
     */
    public function testExactlyOneClassReadsARewardForAStat(): void
    {
        $readers = [];

        foreach ($this->sourceFiles() as $path) {
            // Les fixtures **ecrivent** les recompenses, elles ne les lisent
            // pas : c'est la que le patronage se declare, statistique comprise.
            // Les juger ici jugerait la donnee une seconde fois, et par un
            // angle plus pauvre que celui de `FactionLadderContractTest`.
            if (str_contains($path, '/DataFixtures/')) {
                continue;
            }

            $source = (string) file_get_contents($path);

            if (!str_contains($source, 'FactionReward')) {
                continue;
            }

            foreach (PatronageBonusResolver::STATS as $stat) {
                if (str_contains($source, "'" . $stat . "'")) {
                    $readers[] = basename($path);
                    continue 2;
                }
            }
        }

        self::assertSame(
            ['PatronageBonusResolver.php'],
            array_values(array_unique($readers)),
            'Une seconde classe lit une recompense de faction pour en tirer une statistique.',
        );
    }

    // =====================================================================
    // 3. Les bornes d'Hostile
    // =====================================================================

    /**
     * **La seconde borne absolue : jamais une agression** (GAME_WORLD § 6.4 d,
     * et § 6.1 — le Serment).
     *
     * Le catalogue verifie la premiere borne (la boucle cœur reste ouverte).
     * La seconde n'est verifiee nulle part, et c'est la plus facile a franchir
     * par inadvertance : une consequence qui retirerait des points de vie, qui
     * engagerait un combat ou qui saisirait de l'equipement porte serait une
     * agression — meme infligee par un PNJ, et meme meritee.
     *
     * Le test lit le **vocabulaire** plutot que les valeurs : c'est la forme
     * d'une consequence qui decide si elle peut agresser, pas son chiffre.
     */
    public function testNoHostileConsequenceIsEverAnAggression(): void
    {
        $catalog = new HostileConsequenceCatalog($this->root());

        $aggressive = ['damage', 'life', 'fight', 'combat', 'kill', 'attack', 'unequip', 'destroy'];
        $offenders = [];
        $seen = 0;

        self::assertNotSame([], $catalog->factions(), 'Aucune consequence declaree : le contrat ne mesure rien.');

        foreach ($catalog->factions() as $slug) {
            foreach ($catalog->consequencesFor($slug) as $consequence) {
                ++$seen;
                foreach ($aggressive as $word) {
                    if (str_contains($consequence['type'], $word)) {
                        $offenders[] = sprintf('%s : %s', $slug, $consequence['type']);
                    }
                }
            }
        }

        self::assertGreaterThan(4, $seen, 'Moins d\'une consequence par maison : le contrat ne mesure rien.');
        self::assertSame([], $offenders, "Une consequence d'Hostile prend la forme d'une agression :\n" . implode("\n", $offenders));
    }

    // =====================================================================
    // 4. Aucun geste d'un joueur ne peut nuire directement a un autre joueur
    // =====================================================================

    /**
     * **Le contrat central du plan, et la traduction testable de la regle 11.**.
     *
     * Il se lit dans la **signature** : un geste qui nuirait a un autre joueur
     * devrait le nommer. Aucun service de reputation ne prend deux joueurs —
     * ni le receleur, ni le placement, ni la contrebande, ni la contrefacon, ni
     * les consequences d'Hostile. Ce qu'un joueur fait chez les Ruelles se paie
     * chez les Chevaliers, jamais sur le dos de quelqu'un.
     *
     * La verification porte sur le **type** des parametres et non sur le nom
     * des methodes : une regle qui chercherait des verbes agressifs ne dirait
     * rien du jour ou l'un s'appellerait autrement.
     */
    public function testNoFactionServiceEverNamesTwoPlayers(): void
    {
        $offenders = [];
        $seen = 0;

        foreach ($this->factionServices() as $class) {
            $reflection = new \ReflectionClass($class);

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                $players = 0;
                foreach ($method->getParameters() as $parameter) {
                    $type = $parameter->getType();
                    if ($type instanceof \ReflectionNamedType && $type->getName() === Player::class) {
                        ++$players;
                    }
                }

                if ($players === 1) {
                    ++$seen;
                }
                if ($players >= 2) {
                    $offenders[] = sprintf('%s::%s()', $reflection->getShortName(), $method->getName());
                }
            }
        }

        // Sans cette ligne, le contrat passerait aussi bien si la reflexion ne
        // voyait aucune signature : *un contrat vide ressemble a un contrat
        // tenu*. Les services de reputation nomment un joueur partout — c'est
        // leur sujet.
        self::assertGreaterThan(10, $seen, 'La reflexion ne voit plus les signatures : le contrat ne mesure rien.');

        self::assertSame([], $offenders, "Un service de faction nomme deux joueurs :\n" . implode("\n", $offenders));
    }

    /**
     * **La contrefacon ne franchit jamais un canal entre joueurs** (FAC-07).
     *
     * *Un joueur ne trompe jamais un joueur* : une fausse matéria vendue a
     * l'hotel des ventes serait exactement le geste que la regle 11 interdit,
     * avec un intermediaire.
     *
     * La liste des canaux n'est **pas** ecrite ici — un test qui n'interroge
     * que sa propre liste ne mesure plus rien des qu'elle vieillit (DOM-09).
     * Elle se **derive** de la forme d'un canal : un fichier qui deplace un
     * objet d'un inventaire a un autre (`setInventory`) **et** qui se demande
     * si l'objet peut circuler (`isExchangeable`) est un canal entre joueurs,
     * et il doit refuser la contrefacon.
     *
     * **La derivation a trouve un canal qui lui echappait**, et c'est ce qui la
     * valide : le coffre de guilde reecrivait `isExchangeable()` en deux
     * conditions separees plutot que de l'appeler, donc il ne repondait a aucun
     * des deux criteres. Il portait bien son verrou — mais rien n'aurait dit le
     * contraire. Le predicat a desormais **un seul endroit**, et le coffre
     * entre dans la derivation comme les autres.
     */
    public function testEveryTransferChannelRefusesACounterfeit(): void
    {
        $channels = [];
        $leaking = [];

        foreach ($this->sourceFiles() as $path) {
            $source = (string) file_get_contents($path);

            if (!str_contains($source, 'setInventory(') || !str_contains($source, 'isExchangeable()')) {
                continue;
            }

            $channels[] = basename($path);

            if (!str_contains($source, 'isCounterfeit()')) {
                $leaking[] = basename($path);
            }
        }

        // Une derivation qui ne trouverait rien passerait en silence, et ce
        // serait le pire des resultats : *un contrat vide ressemble a un
        // contrat tenu*.
        self::assertGreaterThanOrEqual(4, \count($channels), 'La derivation ne trouve plus de canal : elle ne mesure plus rien.');
        self::assertContains('GuildVaultManager.php', $channels, 'Le coffre de guilde est sorti de la derivation : le predicat a ete reecrit quelque part.');

        self::assertSame([], $leaking, sprintf(
            "Ces canaux deplacent un objet echangeable sans refuser la contrefacon : %s.\n"
            . 'Un joueur ne trompe jamais un joueur (FAC-07).',
            implode(', ', $leaking),
        ));
    }

    /**
     * Et la forme `access` ne donne jamais autre chose qu'une porte.
     *
     * Elle est la seule des neuf a designer un **lieu**, donc la seule par
     * laquelle un palier pourrait ouvrir un terrain de chasse plus rentable
     * plutot qu'un quartier de lore. La verification vit ici et non dans
     * `FactionLadderContractTest` parce qu'elle croise deux systemes : la
     * recompense et la zone.
     */
    public function testAnAccessRewardOnlyEverOpensASafeQuarter(): void
    {
        foreach ($this->em->getRepository(\App\Entity\Game\FactionReward::class)->findAll() as $reward) {
            if ($reward->getForm() !== FactionRewardForm::Access) {
                continue;
            }

            $zone = $this->em->getRepository(\App\Entity\App\Zone::class)
                ->findOneBy(['slug' => $reward->getRewardData()['zone'] ?? '']);

            self::assertNotNull($zone);
            self::assertTrue($zone->isSafe(), sprintf('« %s » ouvre un terrain de chasse.', $reward->getLabel()));
            self::assertSame(0, $zone->getTier(), sprintf('« %s » ouvre du contenu de palier.', $reward->getLabel()));
        }
    }

    /**
     * Les services du chantier factions, tels qu'ils vivent sur le disque.
     *
     * @return list<class-string>
     */
    private function factionServices(): array
    {
        $classes = [];

        foreach (glob($this->root() . '/src/GameEngine/Reputation/*.php') ?: [] as $path) {
            $class = 'App\\GameEngine\\Reputation\\' . basename($path, '.php');
            if (class_exists($class)) {
                $classes[] = $class;
            }
        }

        self::assertNotSame([], $classes, 'Aucun service de faction trouve : le contrat ne mesure rien.');

        return $classes;
    }

    /**
     * @return list<string>
     */
    private function sourceFiles(): array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root() . '/src'));
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
