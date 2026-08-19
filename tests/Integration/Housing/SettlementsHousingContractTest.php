<?php

namespace App\Tests\Integration\Housing;

use App\Entity\App\Player;
use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\GameEngine\Housing\Homecoming;
use App\GameEngine\Housing\HouseRentRouting;
use App\GameEngine\Housing\HousingManager;
use App\GameEngine\Housing\ResidenceGrain;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * FOY-21 — le contrat de la vague logement.
 *
 * Quatre jalons ont leurs tests, et ce fichier n'en refait aucun. Il porte les
 * invariants **transverses** : ceux qu'aucun jalon ne peut voir depuis sa
 * position, parce que chacun ne connaît que son propre bout.
 *
 *  1. Jamais d'expulsion      → ResidentialParcelsTest (le comportement d'un chemin)
 *                               + ICI (la **forme** : aucun chemin ne peut expulser)
 *  2. Le plancher jamais gate → HousingManagerTest (la capacite ignoree)
 *                               + HouseRentRoutingTest (le Quartier, **nomme**)
 *                               + ICI (la **regle**, derivee de la constante)
 *  3. Le loyer route ou detruit → HouseRentRoutingTest (chaque destination)
 *                               + ICI (les deux chemins comptes ensemble)
 *  4. Le retour sans exploit  → HomecomingAndHearthTest (les refus)
 *                               + ICI (la **signature** : aucune destination)
 *  5. La cheminee du plancher → ICI, et nulle part ailleurs
 *
 * Chaque invariant porte son garde-fou de non-vacuite : *un contrat vide
 * ressemble a un contrat tenu*.
 */
class SettlementsHousingContractTest extends AbstractIntegrationTestCase
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
            'Unit/GameEngine/Housing/HousingManagerTest.php',
            'Unit/GameEngine/Housing/ResidentialParcelsTest.php',
            'Unit/GameEngine/Housing/GardenServiceTest.php',
            'Integration/Housing/HouseRentRoutingTest.php',
            'Integration/Housing/HomecomingAndHearthTest.php',
        ] as $test) {
            self::assertFileExists($this->root() . '/tests/' . $test);
        }
    }

    // =====================================================================
    // 1. Jamais d'expulsion
    // =====================================================================

    /**
     * **Aucun chemin du logement ne peut retirer une demeure.**.
     *
     * `ResidentialParcels` l'ecrit dans son en-tete — *« aucun chemin de ce
     * service ne touche une demeure existante, la borne tient par
     * construction »* — et rien ne l'oppose. Un commentaire n'est pas un
     * invariant : le jour ou une passe de reconciliation naîtra (« aligner les
     * demeures sur la capacite apres une contraction de W »), elle sera ecrite
     * de bonne foi, elle passera tous les tests de jalon, et elle expulsera.
     *
     * `ResidentialParcelsTest::testMoreHousesThanParcelsEvictsNoOne` verifie
     * **un** chemin. Celui-ci verifie qu'il n'en existe pas d'autre.
     *
     * La destruction se lit sur la **forme** : effacer une demeure, ou lui
     * retirer son proprietaire. Le second est le plus insidieux — la ligne
     * reste en base, et le joueur n'a plus de chez-soi.
     */
    public function testNoHousingPathCanEvict(): void
    {
        $offenders = [];
        $scanned = 0;

        foreach ($this->housingSources() as $path => $source) {
            ++$scanned;

            foreach (explode("\n", $source) as $number => $line) {
                // Effacer une demeure.
                if (preg_match('/->remove\(\s*\$\w*(?:house|House|dwelling|Dwelling)\w*/', $line) === 1) {
                    $offenders[] = sprintf('%s:%d — %s', basename($path), $number + 1, trim($line));
                    continue;
                }

                // Lui retirer son proprietaire. `setOwner()` est legitime **a
                // la construction** et nulle part ailleurs : un proprietaire se
                // nomme quand la demeure naît, il ne se remplace jamais.
                if (str_contains($line, 'setOwner(')) {
                    if (!str_contains($source, 'new PlayerHouse(')) {
                        $offenders[] = sprintf('%s:%d — %s', basename($path), $number + 1, trim($line));
                    }
                    if (preg_match('/setOwner\(\s*null\s*\)/', $line) === 1) {
                        $offenders[] = sprintf('%s:%d — %s', basename($path), $number + 1, trim($line));
                    }
                }
            }
        }

        self::assertGreaterThanOrEqual(5, $scanned, 'Le scan ne lit plus le moteur du logement : il ne mesure rien.');

        self::assertSame([], $offenders, sprintf(
            "Ces lignes retirent une demeure ou son proprietaire :\n%s\n"
            . 'La capacite ne gate que l\'ouverture d\'une parcelle — jamais le maintien (FOY-18, decision A).',
            implode("\n", $offenders),
        ));
    }

    /**
     * Et le depot de donnees n'offre aucun verbe pour le faire.
     *
     * Le test precedent lit les appelants ; celui-ci ferme la porte de service
     * — une methode `deleteHouse()` au depot serait invisible au scan tant que
     * personne ne l'appelle, et disponible le jour ou quelqu'un la cherche.
     */
    public function testTheRepositoryOffersNoWayToDeleteADwelling(): void
    {
        $source = (string) file_get_contents($this->root() . '/src/Repository/PlayerHouseRepository.php');

        preg_match_all('/public function (\w+)/', $source, $matches);
        self::assertNotEmpty($matches[1], 'Le depot ne se lit plus.');

        foreach ($matches[1] as $method) {
            self::assertDoesNotMatchRegularExpression(
                '/^(delete|remove|evict|purge|drop)/i',
                $method,
                sprintf('`PlayerHouseRepository::%s()` offre un verbe de destruction : jamais d\'expulsion.', $method),
            );
        }
    }

    // =====================================================================
    // 2. Le plancher n'est jamais gate
    // =====================================================================

    /**
     * **Ce qui rend le plancher inconditionnel, c'est que personne n'en tire
     * rien** — et cela se verifie sur la regle, pas sur son unique cas.
     *
     * `HouseRentRoutingTest` nomme le Quartier des Jardins. C'est correct, et
     * c'est insuffisant : *une regle illustree par son unique instance ne
     * vieillit pas*. Le jour ou une seconde zone de plancher sera ajoutee a la
     * constante, rien ne verifiera qu'elle est, elle aussi, hors foyer — et un
     * plancher qui rapporte a une guilde est un plancher qu'une guilde a
     * interet a fermer.
     *
     * Trois sources se croisent ici, et aucun jalon n'en voit plus d'une : la
     * **constante** (le code), le **monde seme** (la base) et la
     * **declaration** (`without_settlement`, qui distingue une omission d'une
     * decision).
     */
    public function testEveryFloorZoneIsOutsideAnySettlement(): void
    {
        $declared = (new SettlementDefinitionLoader($this->root()))->load()['without_settlement'] ?? [];

        self::assertNotEmpty(HousingManager::RESIDENTIAL_ZONE_SLUGS, 'Le plancher a disparu : la garantie ne tient plus.');

        foreach (HousingManager::RESIDENTIAL_ZONE_SLUGS as $slug) {
            $zone = $this->em->getRepository(Zone::class)->findOneBy(['slug' => $slug]);
            self::assertNotNull($zone, sprintf('Le plancher nomme « %s », qui n\'existe pas : un plancher sans sol.', $slug));

            self::assertNull(
                $this->em->getRepository(Settlement::class)->findOneBy(['zone' => $zone]),
                sprintf(
                    '« %s » est un plancher **et** un foyer : son loyer irait a une guilde, '
                    . 'qui aurait donc interet a le fermer.',
                    $slug,
                ),
            );

            self::assertArrayHasKey(
                $slug,
                $declared,
                sprintf('« %s » n\'a pas de foyer sans que ce soit ecrit : une omission se lit comme une decision.', $slug),
            );
        }
    }

    /**
     * **La capacite ne se consulte jamais sans exempter le plancher d'abord.**.
     *
     * `HousingManagerTest::testTheGardensFloorIgnoresCapacity` verifie que le
     * chemin livre le fait. Celui-ci verifie que c'est la **seule** facon dont
     * la capacite peut etre lue : un second appelant qui poserait la question
     * sans l'exemption fermerait le plancher, et il le fermerait exactement le
     * jour ou la ville est pleine — c'est-a-dire le jour ou le plancher sert.
     */
    public function testCapacityIsNeverConsultedWithoutExemptingTheFloor(): void
    {
        $seen = 0;
        $offenders = [];

        foreach ($this->housingSources() + $this->controllerSources() as $path => $source) {
            if (str_ends_with($path, 'ResidentialParcels.php')) {
                continue; // C'est elle qui repond ; elle ne se consulte pas.
            }

            foreach (explode("\n", $source) as $number => $line) {
                if (!str_contains($line, 'canOpenParcel(')) {
                    continue;
                }
                ++$seen;

                // L'exemption vit dans la meme expression : le plancher passe
                // avant la question, sinon la question a deja ete posee.
                if (!str_contains($line, 'RESIDENTIAL_ZONE_SLUGS')
                    && !str_contains((string) $this->lineBefore($source, $number), 'RESIDENTIAL_ZONE_SLUGS')) {
                    $offenders[] = sprintf('%s:%d — %s', basename($path), $number + 1, trim($line));
                }
            }
        }

        self::assertGreaterThanOrEqual(1, $seen, 'Plus personne ne consulte la capacite : le test ne mesure rien.');
        self::assertSame([], $offenders, sprintf(
            "Ces appels demandent la capacite sans exempter le plancher :\n%s",
            implode("\n", $offenders),
        ));
    }

    // =====================================================================
    // 3. Le loyer est route ou detruit, jamais perdu en route
    // =====================================================================

    /**
     * **Tout ce qui prend le loyer le fait suivre.**.
     *
     * `HouseRentRoutingTest` verifie chaque destination — la guilde, le sink —
     * une fois le loyer entre dans le routeur. Il ne peut pas voir ce qui se
     * passe **avant** : un chemin qui debiterait la bourse sans appeler le
     * routeur ne serait ni route ni brule, il serait **perdu**, et la
     * difference ne se verrait nulle part puisque les deux disparitions se
     * ressemblent du cote du joueur.
     *
     * C'est le defaut que FOY-19 a failli livrer : router le paiement a la main
     * et pas le prelevement automatique ferait dependre le revenu d'une guilde
     * du bouton sur lequel ses habitants ont appuye. La regle se lit donc en
     * **comptant** : autant de routages que de debits, dans chaque fichier.
     */
    public function testEveryRentDebitIsRouted(): void
    {
        $debiting = [];

        foreach ($this->sourceFiles() as $path) {
            $source = (string) file_get_contents($path);

            $debits = preg_match_all('/removeGils\(\s*PlayerHouse::RENT_AMOUNT/', $source);
            if ($debits === 0) {
                continue;
            }

            $routes = preg_match_all('/rentRouting->route\(\s*\$\w+,\s*PlayerHouse::RENT_AMOUNT/', $source);
            $debiting[basename($path)] = ['debits' => $debits, 'routes' => $routes];
        }

        self::assertNotEmpty($debiting, 'Plus personne ne prend le loyer : le contrat ne mesure rien.');

        foreach ($debiting as $file => $counts) {
            self::assertSame($counts['debits'], $counts['routes'], sprintf(
                '%s prend le loyer %d fois et ne le fait suivre que %d fois : la difference est perdue, '
                . 'ni versee ni brulee.',
                $file,
                $counts['debits'],
                $counts['routes'],
            ));
        }
    }

    /**
     * **Et le loyer non route ne rend jamais les gils au joueur.**.
     *
     * Le sink est un sink. Le routeur n'a qu'une porte de sortie vers un
     * beneficiaire, et ce beneficiaire est une **guilde** : s'il pouvait
     * nommer un joueur, le loyer d'un lotissement deviendrait un revenu privé,
     * c'est-a-dire un transfert entre joueurs que la doctrine ne connaît pas.
     */
    public function testTheRoutingCanOnlyEverPayAGuild(): void
    {
        $reflection = new \ReflectionClass(HouseRentRouting::class);
        $named = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor()) {
                continue;
            }

            $return = $method->getReturnType();
            if ($return instanceof \ReflectionNamedType) {
                $named[] = $return->getName();
            }

            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();
                if ($type instanceof \ReflectionNamedType) {
                    self::assertNotSame(
                        Player::class,
                        $type->getName(),
                        sprintf('`HouseRentRouting::%s()` nomme un joueur : un loyer ne revient jamais a quelqu\'un.', $method->getName()),
                    );
                }
            }
        }

        self::assertContains(
            \App\Entity\App\Guild::class,
            $named,
            'Le routeur ne rend plus de guilde : le loyer politique a perdu son unique beneficiaire.',
        );
    }

    // =====================================================================
    // 4. Le retour au logis, sans exploit de voyage
    // =====================================================================

    /**
     * **Il n'y a aucun endroit ou ecrire « ailleurs ».**.
     *
     * `HomecomingAndHearthTest` verifie que le retour mene chez soi et que les
     * deux refus d'etat tiennent. Cela reste un **comportement** : une
     * surcharge, un parametre optionnel ajoute plus tard « pour la
     * flexibilite », et le comportement change sans qu'aucun de ces tests ne
     * parle.
     *
     * L'invariant se lit donc dans la **signature** — *un geste qui emmenerait
     * ailleurs devrait le nommer*. C'est le meme motif qu'ARC-16a
     * (`bonusType`/`bonusValue` retires de l'entite) : ce qu'on ne peut pas
     * ecrire ne peut pas deriver.
     *
     * L'enjeu n'est pas theorique : un retour vers une destination libre
     * contournerait les durees de trajet, donc le graphe de zones, donc ce que
     * le pivot PBBG a mis a la place de la carte navigable.
     */
    public function testHomecomingTakesNoDestination(): void
    {
        $reflection = new \ReflectionClass(Homecoming::class);
        $inspected = 0;

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor()) {
                continue;
            }
            ++$inspected;

            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();
                if (!$type instanceof \ReflectionNamedType) {
                    continue;
                }

                self::assertNotSame(Zone::class, $type->getName(), sprintf(
                    '`Homecoming::%s()` prend une destination (`$%s`) : ce n\'est plus un retour au logis, '
                    . 'c\'est un teleporteur — et le graphe de zones devient decoratif.',
                    $method->getName(),
                    $parameter->getName(),
                ));
            }
        }

        self::assertGreaterThanOrEqual(3, $inspected, 'La reflexion ne voit plus les methodes : le contrat ne mesure rien.');

        // Le garde-fou du garde-fou : la classe rend bien une zone, sinon
        // l'absence de parametre `Zone` ne prouverait rien du tout.
        $return = $reflection->getMethod('comeHome')->getReturnType();
        self::assertInstanceOf(\ReflectionNamedType::class, $return);
        self::assertSame(Zone::class, $return->getName());
    }

    /**
     * **Et la destination se lit sur la demeure, jamais sur le joueur.**.
     *
     * L'autre facon d'ouvrir une destination libre serait de laisser le joueur
     * porter la sienne (une zone « d'attache » qu'on reglerait a volonte). Le
     * retour lit donc `PlayerHouse::getZone()`, et le contrat le fige : c'est
     * l'achat d'une parcelle — payant, borne par la capacite, et unique — qui
     * decide ou l'on rentre.
     */
    public function testTheDestinationComesFromTheDwelling(): void
    {
        $source = (string) file_get_contents($this->root() . '/src/GameEngine/Housing/Homecoming.php');

        self::assertStringContainsString('$house->getZone()', $source);
        self::assertStringNotContainsString(
            'getHomeZone(',
            $source,
            'Le retour lit une zone d\'attache portee par le joueur : elle se reglerait a volonte.',
        );
    }

    // =====================================================================
    // 5. Ce que le croisement des trois jalons a trouve
    // =====================================================================

    /**
     * **Une cheminee sans ville ne fume pas, et le compteur le dit.**.
     *
     * Le plancher est le seul endroit du jeu ou une demeure existe **sans
     * foyer** — c'est meme ce qui le rend inconditionnel (FOY-19). Aucun test
     * de jalon ne pouvait voir ce cas : FOY-20 pose ses demeures dans une zone
     * a foyer, et FOY-19 sait que le plancher n'a pas de percepteur mais ne
     * connaît pas les cheminees.
     *
     * Au croisement, un defaut livre : `SettlementDepositService::deposit()`
     * rend zero quand la zone n'a pas de foyer, et la cheminee etait comptee
     * **allumee** quand meme. Aucune consequence de jeu — mais *un compteur qui
     * compte des grains qui ne sont pas tombes ne mesure plus rien*, et c'est ce
     * compteur qu'un operateur lit pour savoir que le calendrier tourne.
     *
     * Corollaire : la cle de jour n'est pas posee. Rien n'a eu lieu, donc rien
     * a marquer — et le jour ou un foyer levera dans cette zone, la cheminee
     * fumera sans qu'on ait a rattraper quoi que ce soit.
     */
    public function testAHearthWithoutACityIsCountedAsSkipped(): void
    {
        $floor = $this->em->getRepository(Zone::class)->findOneBy([
            'slug' => HousingManager::RESIDENTIAL_ZONE_SLUGS[0],
        ]);
        self::assertNotNull($floor);
        self::assertNull(
            $this->em->getRepository(Settlement::class)->findOneBy(['zone' => $floor]),
            'La premisse a change : le plancher a un foyer.',
        );

        /** @var HousingManager $housing */
        $housing = self::getContainer()->get(HousingManager::class);
        /** @var ResidenceGrain $hearths */
        $hearths = self::getContainer()->get(ResidenceGrain::class);

        $player = $this->em->getRepository(Player::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($player);
        $player->setCurrentZone($floor);
        $player->setGils(1_000_000);

        $house = $housing->buyLand($player, $floor, 'Le Logis du plancher');
        $now = $house->getRentDueAt()->modify('-1 day');

        $before = $hearths->burnHearths($now);
        self::assertSame(0, $before['burned'], 'Une cheminee a fume sans ville a soutenir.');
        self::assertGreaterThanOrEqual(1, $before['skipped']);

        // Et rien n'a ete marque : la demeure reste candidate demain.
        self::assertFalse($house->hasBurnedItsHearthOn($now->format('Y-m-d')));
    }

    // =====================================================================

    /**
     * @return array<string, string> chemin => contenu
     */
    private function housingSources(): array
    {
        $sources = [];

        foreach (glob($this->root() . '/src/GameEngine/Housing/*.php') ?: [] as $path) {
            $sources[$path] = (string) file_get_contents($path);
        }

        $repository = $this->root() . '/src/Repository/PlayerHouseRepository.php';
        $sources[$repository] = (string) file_get_contents($repository);

        return $sources;
    }

    /**
     * @return array<string, string> chemin => contenu
     */
    private function controllerSources(): array
    {
        $path = $this->root() . '/src/Controller/Game/HousingController.php';

        return [$path => (string) file_get_contents($path)];
    }

    private function lineBefore(string $source, int $number): ?string
    {
        $lines = explode("\n", $source);

        return $lines[$number - 1] ?? null;
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
