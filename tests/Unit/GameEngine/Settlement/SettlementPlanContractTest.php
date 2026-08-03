<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Entity\App\ZoneVein;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Le contrat transverse du pilier territorial (FOY-16).
 *
 * Chaque brique du plan se teste de son cote — seize fichiers, plus de deux
 * cents methodes. Ce fichier teste ce qu'aucune ne peut tester seule : ce qui
 * n'est vrai que **de l'ensemble**, et qui casserait sans qu'aucun test de
 * comportement ne bouge.
 *
 * Cinq proprietes, tirees directement des decisions du plan :
 *
 * 1. **Rien en dur.** Tout parametre declare est lu, et le calibrage vit dans
 *    `settlements.yaml`. Un parametre ecrit et jamais consulte est un mensonge
 *    silencieux — la pire espece, parce qu'il se lit comme une garantie.
 * 2. **Un foyer ne depasse jamais son quota.** Le rang ne se pose qu'a deux
 *    endroits, et l'un des deux passe par la Crue.
 * 3. **Un service existant n'est jamais gate** (decision A, FOY-05).
 * 4. **Un filon pali n'est jamais sterile** (GAME_WORLD § 12.1) — c'est ce qui
 *    le distingue d'une Etale.
 * 5. **La Paleur est par filon, jamais par zone** (§ 3.5) : c'est la decision
 *    qui garantit qu'elle ne frappe que l'exploitation concentree.
 *
 * Les controles sont **lexicaux et structurels**, deliberement : ils
 * verrouillent la forme du code pour qu'une regression se voie a l'ecriture, la
 * ou un test de comportement ne verrait que la brique qu'il connait.
 */
class SettlementPlanContractTest extends TestCase
{
    /**
     * Les deux seuls fichiers autorises a poser le rang d'un foyer.
     *
     * Le tick le fait apres etre passe par la Crue ; le seeder pose le monde
     * livre, qui n'a pas d'histoire a respecter. Un troisieme ecrivain
     * contournerait le quota sans que rien ne le dise.
     *
     * @var list<string>
     */
    private const RANK_WRITERS = [
        'src/GameEngine/Settlement/SettlementTickService.php',
        'src/GameEngine/Settlement/SettlementSeeder.php',
    ];

    private function root(): string
    {
        return \dirname(__DIR__, 4);
    }

    /**
     * @return array<string, mixed>
     */
    private function shipped(): array
    {
        /** @var array<string, mixed> $raw */
        $raw = Yaml::parseFile($this->root() . '/config/game/settlements.yaml');

        return $raw;
    }

    /**
     * @return list<string> chemins relatifs des sources PHP du projet
     */
    private function sources(): array
    {
        $files = [];
        $directories = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root() . '/src', \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($directories as $file) {
            if ($file instanceof \SplFileInfo && 'php' === $file->getExtension()) {
                $files[] = ltrim(str_replace($this->root(), '', $file->getPathname()), '/');
            }
        }

        sort($files);

        return $files;
    }

    // =====================================================================
    // 1. Rien en dur
    // =====================================================================

    /**
     * Tout parametre declare est **lu**.
     *
     * « Rien en dur » n'est tenu que si l'inverse l'est aussi : un bloc de
     * calibrage que le chargeur ignore se lit comme une garantie et n'en est
     * pas une. C'est exactement la famille de defaut que ce plan a passe son
     * temps a deterrer — un seuil ecrit, jamais applique, et rien pour le dire.
     */
    public function testEveryDeclaredParameterIsActuallyRead(): void
    {
        $loader = (string) file_get_contents($this->root() . '/src/GameEngine/Settlement/SettlementDefinitionLoader.php');

        $unread = [];
        foreach (array_keys($this->shipped()) as $key) {
            if (!str_contains($loader, sprintf("'%s'", $key))) {
                $unread[] = $key;
            }
        }

        self::assertSame(
            [],
            $unread,
            'Ces blocs de settlements.yaml ne sont lus par personne. Un parametre declare et jamais applique '
            . 'se lit comme une garantie, et n\'en est pas une.',
        );
    }

    /**
     * Les seuils de rang ne sont ecrits **nulle part** dans le moteur.
     *
     * Le calibrage se retend en observant le serveur ; une constante de classe
     * imposerait un redeploiement, et — pire — cohabiterait un temps avec la
     * valeur du fichier sans que personne ne sache laquelle gagne.
     */
    public function testRankThresholdsNeverLeakIntoTheEngine(): void
    {
        /** @var array<string, int> $ranks */
        $ranks = $this->shipped()['ranks'];
        $offenders = [];

        foreach ($this->sources() as $relative) {
            if (!str_starts_with($relative, 'src/GameEngine/Settlement/')) {
                continue;
            }

            $content = $this->stripComments((string) file_get_contents($this->root() . '/' . $relative));
            foreach ($ranks as $rank => $threshold) {
                if (preg_match('/\b' . $threshold . '\b/', $content)) {
                    $offenders[] = sprintf('%s (%s = %d)', $relative, $rank, $threshold);
                }
            }
        }

        self::assertSame([], $offenders, 'Un seuil de rang code en dur double le fichier de calibrage.');
    }

    // =====================================================================
    // 2. Un foyer ne depasse jamais son quota
    // =====================================================================

    /**
     * Le rang d'un foyer ne se pose qu'aux deux endroits prevus.
     *
     * La Crue est un plafond ; un plafond ne vaut que s'il n'existe aucune
     * porte a cote. Un troisieme ecrivain n'aurait pas eu a mentir pour
     * contourner le quota — il lui aurait suffi de ne pas le connaitre.
     */
    public function testASettlementRankIsOnlyEverSetInTwoPlaces(): void
    {
        $writers = [];

        foreach ($this->sources() as $relative) {
            $content = $this->stripComments((string) file_get_contents($this->root() . '/' . $relative));
            if (str_contains($content, '$settlement->setRank(')) {
                $writers[] = $relative;
            }
        }

        sort($writers);
        $expected = self::RANK_WRITERS;
        sort($expected);

        self::assertSame($expected, $writers, 'Poser un rang ailleurs, c\'est contourner la Crue sans le savoir.');
    }

    /**
     * Et celui des deux qui fait monter un foyer passe par la Crue.
     */
    public function testTheTickAsksTheCrueBeforeRaisingASettlement(): void
    {
        $tick = (string) file_get_contents($this->root() . '/src/GameEngine/Settlement/SettlementTickService.php');

        self::assertStringContainsString('highestAllowed', $tick);
    }

    // =====================================================================
    // 3. Un service existant n'est jamais gate
    // =====================================================================

    /**
     * La decision A, verifiee sur le fichier livre.
     *
     * Le chargeur refuse deja l'intersection ; ce test verifie que la liste des
     * intouchables n'a pas ete **videe**, ce que le chargeur laisserait passer
     * sans broncher. Une regle sans sujet est une regle qui ne s'applique plus.
     */
    public function testNoExistingServiceIsEverGatedByRank(): void
    {
        $shipped = $this->shipped();

        /** @var array<string, mixed> $services */
        $services = $shipped['services'];
        /** @var array<string, string> $neverGated */
        $neverGated = $shipped['never_gated'];

        self::assertNotEmpty($neverGated, 'Vider `never_gated`, c\'est desarmer la decision A sans la contredire.');
        self::assertSame([], array_intersect(array_keys($services), array_keys($neverGated)));

        foreach ($neverGated as $service => $reason) {
            self::assertNotSame('', trim($reason), sprintf('"%s" est intouchable sans dire pourquoi.', $service));
        }
    }

    // =====================================================================
    // 4. Un filon pali n'est jamais sterile
    // =====================================================================

    /**
     * Le plafond de Paleur reste strictement sous 1, et aucun seuil d'effet ne
     * le depasse.
     *
     * C'est ce qui distingue la Paleur d'une **Etale** (GAME_WORLD § 12.1) : la
     * premiere est ce qu'un serveur fait a un filon et defait en le laissant
     * respirer, la seconde est un lieu ancien et permanent.
     */
    public function testAPaleVeinIsNeverSterile(): void
    {
        /** @var array<string, float> $paleness */
        $paleness = $this->shipped()['paleness'];

        self::assertLessThan(1.0, $paleness['max']);
        self::assertGreaterThan(0.0, $paleness['max']);

        foreach (['visible_from', 'dulls_purity_from'] as $threshold) {
            self::assertLessThanOrEqual(
                $paleness['max'],
                $paleness[$threshold],
                sprintf('"%s" au-dessus du plafond ne se declencherait jamais.', $threshold),
            );
        }
    }

    /**
     * Et la recolte garde son plancher d'une unite.
     *
     * Le plafond de configuration ne suffit pas : c'est le rendement qui decide
     * si un filon est sterile ou non, et il doit rendre au moins une unite
     * quoi qu'il arrive — a sec comme pali.
     */
    public function testGatheringAlwaysYieldsAtLeastOneUnit(): void
    {
        $gather = (string) file_get_contents($this->root() . '/src/GameEngine/Zone/GatherService.php');

        self::assertStringContainsString('return max(1, $stock > 0 ? min($yield, $stock) : 1);', $gather);
    }

    // =====================================================================
    // 5. La Paleur est par filon, jamais par zone
    // =====================================================================

    /**
     * La grandeur vit sur le filon, et sur rien d'autre.
     *
     * Un agregat de zone punirait cinquante joueurs occasionnels pour ce qu'une
     * guilde a fait a un seul filon (§ 3.5). Le jour ou quelqu'un ajouterait une
     * colonne `paleness` sur `Settlement` ou `Zone` « pour simplifier », le
     * pilier changerait de sens sans qu'aucun test de comportement ne bouge.
     */
    public function testPalenessLivesOnTheVeinAndNowhereElse(): void
    {
        self::assertTrue(method_exists(ZoneVein::class, 'getPaleness'));
        self::assertFalse(method_exists(Settlement::class, 'getPaleness'));
        self::assertFalse(method_exists(Zone::class, 'getPaleness'));
    }

    /**
     * Le vocabulaire declaratif du fichier est fige.
     *
     * Renommer un bloc ne casse rien a la compilation : le chargeur lirait
     * simplement `null` et le defaut s'installerait en silence. Cette liste est
     * la pour qu'un renommage soit un acte delibere.
     */
    public function testTheDeclarativeVocabularyIsFrozen(): void
    {
        $expected = [
            'anti_exploit', 'crue', 'decay', 'doctrine', 'housing', 'never_gated', 'paleness',
            'ranks', 'regression', 'restoration', 'sediment', 'seed', 'services', 'type',
            'weekly_work', 'without_settlement', 'workshop',
        ];

        $actual = array_keys($this->shipped());
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * Le fichier livre passe sa propre validation.
     *
     * Chaque brique teste le chargeur sur des tableaux construits ; celui-ci
     * verifie que le fichier **reellement charge au demarrage** les satisfait
     * tous a la fois.
     */
    public function testTheShippedFilePassesEveryInvariantAtOnce(): void
    {
        $definition = (new SettlementDefinitionLoader($this->root()))->load();

        self::assertNotEmpty($definition['ranks']);
        self::assertNotEmpty($definition['sediment']);
        self::assertGreaterThan(0.0, $definition['decay_rate']);
    }

    /**
     * Code seul, commentaires retires : un docbloc a le droit de citer ce qu'il
     * interdit — c'est meme ainsi qu'il l'explique.
     */
    private function stripComments(string $source): string
    {
        $code = '';
        foreach (token_get_all($source) as $token) {
            if (\is_array($token) && \in_array($token[0], [\T_COMMENT, \T_DOC_COMMENT], true)) {
                continue;
            }

            $code .= \is_array($token) ? $token[1] : $token;
        }

        return $code;
    }
}
