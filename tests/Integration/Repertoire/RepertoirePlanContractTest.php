<?php

namespace App\Tests\Integration\Repertoire;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\RepertoireGesture;
use App\Entity\App\RepertoireReading;
use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\Enum\SettlementRank;
use App\GameEngine\Materia\MateriaConversionService;
use App\GameEngine\Repertoire\AwakeningAltar;
use App\GameEngine\Repertoire\RepertoireCatalog;
use App\GameEngine\Settlement\SettlementGate;
use App\Repository\RepertoireReadingRepository;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * REP-06 — le contrat du plan Repertoire.
 *
 * Cinq jalons ont leurs tests, et ce fichier n'en refait aucun. Il porte les
 * invariants **transverses** — ceux qu'aucun jalon ne peut voir depuis sa
 * position — et sert de table des matieres, en verifiant que son index ne
 * pourrit pas (le geste de `DungeonsPlanContractTest` et de
 * `FactionsPlanContractTest`).
 *
 *  1. Lateral, jamais vertical  → FoundGesturePoolTest (le bassin)
 *                                 + ICI (l'Autel, second lecteur du bassin)
 *  2. Un seul bassin            → RepertoireCatalogTest (la forme du fichier)
 *                                 + ICI (aucune **table** ne connaît de serveur)
 *  3. Le deblocage jamais repris → ICI, et nulle part ailleurs
 *  4. L'Autel jamais fermable    → ICI, et nulle part ailleurs
 *  5. Le plafond anti-forcage    → RepertoireLedgerTest (il s'applique)
 *                                 + ICI (il ne ferme **jamais** la lecture)
 *  6. **Fondre ne verse rien**   → ICI, et nulle part ailleurs
 */
class RepertoirePlanContractTest extends AbstractIntegrationTestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    public function testTheContractIndexNamesRealTests(): void
    {
        foreach ([
            'Unit/GameEngine/Repertoire/RepertoireCatalogTest.php',
            'Integration/Repertoire/RepertoireLedgerTest.php',
            'Integration/Repertoire/FoundGesturePoolTest.php',
            'Integration/Repertoire/RepertoireUnlockerTest.php',
            'Integration/Repertoire/AwakeningAltarTest.php',
            'Integration/Repertoire/RepertoireStateTest.php',
            'Integration/Settlement/GatedServiceRoutingTest.php',
        ] as $test) {
            self::assertFileExists($this->root() . '/tests/' . $test);
        }
    }

    // =====================================================================
    // 6. Fondre ne verse rien au Repertoire
    // =====================================================================

    /**
     * **Le contrat transverse du plan.**.
     *
     * GAME_WORLD § 12.2 : *« fondre paie l'individu aujourd'hui ; lire ouvre au
     * serveur, pour toujours »*. Le cout collectif de la fonte est **reel** —
     * une materia fondue est un souvenir efface —, et cela ne tient qu'a une
     * chose : fondre ne verse rien au Repertoire.
     *
     * L'asymetrie est correcte dans le code livre (`melt()` ne dispatche rien,
     * `read()` dispatche `MateriaReadEvent`), mais elle n'etait verifiee nulle
     * part. C'est exactement le genre de propriete qui se perd sans bruit le
     * jour ou quelqu'un uniformise les deux chemins **par souci de symetrie** —
     * et l'axe doctrinal du jeu tout entier tomberait avec elle.
     */
    public function testMeltingPoursNothingIntoTheRepertoire(): void
    {
        /** @var RepertoireReadingRepository $readings */
        $readings = self::getContainer()->get(RepertoireReadingRepository::class);
        /** @var MateriaConversionService $conversion */
        $conversion = self::getContainer()->get(MateriaConversionService::class);

        $player = $this->player();
        $before = array_sum($readings->tallyByElement());
        $gilsBefore = $player->getGils();

        $conversion->melt($player, $this->materiaInBag($player));

        // La fonte a bien eu lieu : sans cette ligne, le test passerait aussi
        // bien sur une fonte qui echoue en silence — *un contrat vide ressemble
        // a un contrat tenu*.
        self::assertGreaterThan($gilsBefore, $player->getGils(), 'La fonte n\'a rien rapporte : elle n\'a pas eu lieu.');

        self::assertSame(
            $before,
            array_sum($readings->tallyByElement()),
            'Fondre a nourri le Repertoire : le cout collectif de la fonte n\'existe plus, et l\'axe doctrinal avec lui.',
        );
    }

    /**
     * Et la moitie qui tient l'autre : **lire, si**.
     *
     * Sans elle, le test ci-dessus passerait aussi bien sur un Repertoire qui
     * ne recoit jamais rien de personne — *un contrat vide ressemble a un
     * contrat tenu*.
     */
    public function testReadingPoursIntoTheRepertoire(): void
    {
        /** @var RepertoireReadingRepository $readings */
        $readings = self::getContainer()->get(RepertoireReadingRepository::class);
        /** @var MateriaConversionService $conversion */
        $conversion = self::getContainer()->get(MateriaConversionService::class);

        $player = $this->player();
        $player->setCurrentZone($this->zone('village-de-lumiere'));
        $before = array_sum($readings->tallyByElement());

        $conversion->read($player, $this->materiaInBag($player));

        self::assertGreaterThan(
            $before,
            array_sum($readings->tallyByElement()),
            'Lire ne nourrit plus le Repertoire : le crochet a perdu son abonne.',
        );
    }

    // =====================================================================
    // 3. Le deblocage n'est jamais repris
    // =====================================================================

    /**
     * **Aucun code ne retire un geste retrouve.**.
     *
     * *Le savoir n'est jamais borne* (GAME_DOMAINS § 1). L'entite n'a deja
     * aucun champ pour reprendre un geste ; restait le chemin par lequel on
     * pourrait supprimer sa ligne. Le test se lit sur les sources, parce que
     * c'est la seule facon de mesurer ce qui **n'arrive pas**.
     */
    public function testNoCodeEverTakesBackARecoveredGesture(): void
    {
        $offenders = [];
        $seen = 0;

        foreach ($this->sourceFiles() as $path) {
            $source = (string) file_get_contents($path);

            if (!str_contains($source, 'RepertoireGesture')) {
                continue;
            }

            ++$seen;

            // On cherche une suppression, sous ses deux formes : l'entite
            // passee a `remove()`, ou un DELETE en DQL sur la table.
            if (preg_match('/remove\(\$\w*[Gg]esture\b/', $source) === 1
                || preg_match('/delete\(\s*RepertoireGesture/', $source) === 1) {
                $offenders[] = basename($path);
            }
        }

        // Sans cette ligne, un renommage de l'entite rendrait le test vert en
        // ne regardant plus rien.
        self::assertGreaterThan(2, $seen, 'Plus aucun fichier ne nomme RepertoireGesture : le contrat ne mesure rien.');

        self::assertSame([], $offenders, sprintf(
            "Ces fichiers retirent un geste retrouve : %s.\nLe savoir n'est jamais borne.",
            implode(', ', $offenders),
        ));
    }

    // =====================================================================
    // 4. L'Autel n'est jamais fermable par une guilde
    // =====================================================================

    /**
     * **Une guilde taxe, elle ne ferme jamais** (doctrine D14).
     *
     * La porte de l'Autel se lit sur le **rang du foyer**, et sur rien d'autre.
     * Le test le verifie la ou ca compte : sur une Metropole sans aucune guilde
     * controlante, l'Autel est ouvert. Si la permission dependait d'un
     * gouvernant, ce cas-la serait ferme — et le service de la ville serait
     * devenu un pouvoir de guilde.
     */
    public function testTheAltarOpensOnRankAloneAndNoGuildCanCloseIt(): void
    {
        /** @var SettlementGate $gate */
        $gate = self::getContainer()->get(SettlementGate::class);

        $zone = $this->zone('foret-des-murmures');
        $settlement = $this->em->getRepository(Settlement::class)->findOneBy(['zone' => $zone]);
        self::assertNotNull($settlement, 'La Foret n\'a pas de foyer : le contrat ne mesure rien.');

        self::assertFalse($gate->allows($zone, AwakeningAltar::SERVICE), 'L\'Autel est ouvert sous la Metropole.');

        $settlement->setRank(SettlementRank::Metropolis);
        $this->em->flush();

        self::assertTrue(
            $gate->allows($zone, AwakeningAltar::SERVICE),
            'Une Metropole sans guilde ne peut pas ouvrir l\'Autel : la porte depend de quelqu\'un.',
        );
    }

    // =====================================================================
    // 2. Un seul bassin, ecrit une fois
    // =====================================================================

    /**
     * **Aucune table du Repertoire ne connaît de serveur ni de monde.**.
     *
     * Le canon interdit d'ecrire du contenu pour *un* serveur : le bassin est
     * global, les serveurs le traversent dans des ordres differents. Le
     * catalogue tient la moitie « donnee » (aucune cle ou l'ecrire) ; celle-ci
     * ferme l'autre porte — une colonne de discriminant ferait du Repertoire un
     * contenu par serveur sans qu'aucun fichier de configuration ne le dise.
     */
    public function testNoRepertoireTableKnowsAboutAServer(): void
    {
        $forbidden = ['server', 'world', 'shard', 'realm'];
        $offenders = [];

        foreach ([RepertoireReading::class, RepertoireGesture::class] as $entity) {
            $metadata = $this->em->getClassMetadata($entity);

            foreach (array_merge($metadata->getColumnNames(), $metadata->getAssociationNames()) as $name) {
                foreach ($forbidden as $word) {
                    if (str_contains(mb_strtolower($name), $word)) {
                        $offenders[] = sprintf('%s.%s', $metadata->getTableName(), $name);
                    }
                }
            }
        }

        self::assertSame([], $offenders, "Le Repertoire porte un discriminant de serveur :\n" . implode("\n", $offenders));
    }

    // =====================================================================
    // 5. Le plafond ne ferme jamais la lecture
    // =====================================================================

    /**
     * **Le plafond est une borne de mesure, pas une borne de jeu.**.
     *
     * Un joueur au-dela de son plafond continue de lire : reputation, Codex,
     * accord. Ce qui s'arrete est sa contribution au souvenir du serveur. Le
     * test le lit a la source : le service de conversion ne connaît ni le
     * catalogue du Repertoire ni son plafond — *il ne peut donc pas refuser*.
     */
    public function testTheCapCanNeverRefuseTheReadingItself(): void
    {
        $source = (string) file_get_contents($this->root() . '/src/GameEngine/Materia/MateriaConversionService.php');

        foreach (['RepertoireCatalog', 'RepertoireLedger', 'dailyReadingsPerPlayer'] as $forbidden) {
            self::assertStringNotContainsString(
                $forbidden,
                $source,
                sprintf('La lecture consulte « %s » : le plafond pourrait refuser un geste de personnage.', $forbidden),
            );
        }
    }

    // =====================================================================
    // 1. Lateral, jamais vertical — du cote de l'Autel
    // =====================================================================

    /**
     * **L'Autel ne produit rien qui ne soit au catalogue standard.**.
     *
     * `FoundGesturePoolTest` tient la loi sur le bassin ; l'Autel en est le
     * **second lecteur**, et rien ne garantissait qu'il ne fabrique pas autre
     * chose en chemin. Le test lit la source : aucune creation d'objet
     * generique, aucune ecriture de statistique.
     */
    public function testTheAltarNeverMintsAnythingNew(): void
    {
        $source = (string) file_get_contents($this->root() . '/src/GameEngine/Repertoire/AwakeningAltar.php');

        self::assertStringNotContainsString('new Item(', $source, 'L\'Autel fabrique un objet : un geste retrouve donne une option, jamais un objet inedit.');
        self::assertStringNotContainsString('setDamage', $source);
        self::assertStringNotContainsString('setLevel(', $source);

        // Et la sortie reste bien une materia du catalogue : le bassin la nomme,
        // et `FoundGesturePoolTest` verifie qu'elle existe. On verifie ici que
        // l'Autel lit ce nom-la et n'en invente pas un autre.
        /** @var RepertoireCatalog $catalog */
        $catalog = self::getContainer()->get(RepertoireCatalog::class);
        $slugs = array_column($catalog->foundGestures(), 'awakens');

        self::assertNotSame([], $slugs);
        foreach ($slugs as $slug) {
            self::assertNotNull($this->em->getRepository(Item::class)->findOneBy(['slug' => $slug]));
        }
    }

    // =====================================================================

    private function player(): Player
    {
        $player = $this->em->getRepository(Player::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($player);

        return $player;
    }

    private function zone(string $slug): Zone
    {
        $zone = $this->em->getRepository(Zone::class)->findOneBy(['slug' => $slug]);
        self::assertNotNull($zone);

        return $zone;
    }

    /**
     * Une materia dans le sac du joueur, creee pour l'occasion.
     */
    private function materiaInBag(Player $player): PlayerItem
    {
        $materia = $this->em->getRepository(Item::class)->findOneBy(['slug' => 'm1-fire-bolt'])
            ?? $this->em->getRepository(Item::class)->findOneBy(['type' => Item::TYPE_MATERIA]);
        self::assertNotNull($materia, 'Aucune materia au catalogue : le contrat ne mesure rien.');

        $bag = null;
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->isBag()) {
                $bag = $inventory;
                break;
            }
        }
        self::assertNotNull($bag, 'Le joueur n\'a pas de sac.');

        $item = new PlayerItem();
        $item->setGenericItem($materia);
        $item->setInventory($bag);
        $this->em->persist($item);
        $this->em->flush();

        return $item;
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
