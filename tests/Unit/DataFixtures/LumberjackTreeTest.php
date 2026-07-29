<?php

namespace App\Tests\Unit\DataFixtures;

use App\GameEngine\Progression\ActionYieldResolver;
use PHPUnit\Framework\TestCase;

/**
 * L'arbre du bucheron au gabarit de recolte (DOM-05).
 *
 * ZON-34 avait livre huit nœuds et **aucun emplacement d'outil**, en disant
 * pourquoi : « la hache demande un type d'outil, un bit d'equipement et un
 * emplacement d'interface neufs — un changement de mecanisme, pas de donnees.
 * Elle arrivera avec le charpentier, a qui elle sert. » Le charpentier existe
 * depuis ECO-30 ; la promesse se solde ici.
 *
 * Ce qui se verrouille : la forme du gabarit (§ 5.2), les quatre paliers de
 * hache, et le plafond de rendement — un arbre assez long finirait par rendre le
 * budget d'energie sans effet, et le joueur assidu retrouverait par le rendement
 * le debit que le plafond quotidien lui refuse.
 */
class LumberjackTreeTest extends TestCase
{
    /**
     * Les quatre paliers de hache, dans l'ordre.
     *
     * @var list<string>
     */
    private const AXE_TIERS = ['axe-bronze', 'axe-iron', 'axe-steel', 'axe-mithril'];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    private function tree(): string
    {
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/Game/SkillFixtures.php');

        $start = strpos($source, 'private function getLumberjackSkills(): array');
        self::assertNotFalse($start, 'L\'arbre du bucheron a disparu.');

        $end = strpos($source, 'private function getCarpenterSkills(): array', $start);
        self::assertNotFalse($end, 'La fin de l\'arbre du bucheron est introuvable.');

        return substr($source, $start, $end - $start);
    }

    /**
     * @return list<string>
     */
    private function nodes(): array
    {
        preg_match_all("/'(lumber_[a-z_0-9]+)' => \[/", $this->tree(), $matches);

        return $matches[1];
    }

    /**
     * La profondeur cible du gabarit : ~15 nœuds (GAME_DOMAINS § 5).
     */
    public function testTheTreeReachesTheTemplateDepth(): void
    {
        self::assertCount(15, $this->nodes());
    }

    /**
     * Deux entrees a 0 point, comme les autres arbres.
     *
     * Un seul nœud d'entree ferait de l'arbre un couloir : le debutant n'aurait
     * pas de choix a faire, seulement un ordre a suivre.
     */
    public function testTheTreeOpensOnTwoFreeNodes(): void
    {
        preg_match_all("/'requiredPoints' => (\d+)/", $this->tree(), $matches);

        $free = \count(array_filter($matches[1], static fn (string $points): bool => $points === '0'));

        self::assertSame(2, $free, 'L\'arbre du bucheron doit ouvrir sur deux nœuds gratuits : la matiere, et l\'outil qui la coupe.');
    }

    // =====================================================================
    // La hache
    // =====================================================================

    /**
     * L'emplacement de hache se debloque au nœud d'entree, comme la pioche du
     * mineur ou la faucille de l'herboriste.
     */
    public function testTheAxeSlotOpensOnAnEntryNode(): void
    {
        self::assertStringContainsString(
            "['action' => 'tool_slot.unlock', 'slot' => 'axe']",
            $this->tree(),
            'Aucun nœud ne debloque l\'emplacement de hache : l\'outil existe et reste inequipable.',
        );
    }

    /**
     * Chaque palier de hache est autorise par un nœud.
     *
     * Un palier qu'aucun nœud n'ouvre est un objet qu'on peut acheter et jamais
     * equiper — le defaut qu'`EquipmentController` avait deja eu a defaire pour
     * les pioches.
     */
    public function testEveryAxeTierIsUnlockedBySomeNode(): void
    {
        $tree = $this->tree();

        $orphans = [];
        foreach (self::AXE_TIERS as $slug) {
            if (!str_contains($tree, sprintf("'%s'", $slug))) {
                $orphans[] = $slug;
            }
        }

        self::assertSame([], $orphans, 'Ces paliers de hache ne sont ouverts par aucun nœud : ils s\'achetent et ne s\'equipent pas.');
    }

    /**
     * Les quatre haches existent, au meme profil que les quatre autres outils.
     */
    public function testTheFourAxesAreDeclared(): void
    {
        // Lecture ligne a ligne : une expression a quantificateurs imbriques sur
        // le fichier entier depasse la limite de retour arriere de PCRE et rend
        // zero correspondance **sans erreur** (le piege releve par ZON-33).
        $current = null;
        $tiers = [];
        foreach (explode("\n", (string) file_get_contents($this->root() . '/fixtures/game/item/tool.yaml')) as $line) {
            if (preg_match("/^\s+slug: '(axe-[a-z]+)'/", $line, $match) === 1) {
                $current = $match[1];

                continue;
            }
            if ($current !== null && preg_match("/^\s+toolTier: (\d)/", $line, $match) === 1) {
                $tiers[$current] = (int) $match[1];
                $current = null;
            }
        }

        self::assertSame(
            ['axe-bronze' => 1, 'axe-iron' => 2, 'axe-steel' => 3, 'axe-mithril' => 4],
            $tiers,
            'Les quatre paliers de hache ne couvrent pas bronze/fer/acier/mithril, comme les quatre autres outils.',
        );
    }

    /**
     * Une hache doit pouvoir s'acheter.
     *
     * Un outil qu'aucun etal ne vend est un outil que le nœud d'entree autorise
     * sans que personne puisse l'obtenir — un blocage sans message.
     */
    public function testTheEntryAxeIsSoldSomewhere(): void
    {
        $sold = false;
        foreach ((array) glob($this->root() . '/src/DataFixtures/*PnjFixtures.php') as $file) {
            if (str_contains((string) file_get_contents((string) $file), "'axe-bronze'")) {
                $sold = true;
                break;
            }
        }

        self::assertTrue($sold, 'Aucun etal ne vend de hache de bronze : le nœud d\'entree ouvre un emplacement qu\'on ne peut pas remplir.');
    }

    // =====================================================================
    // Le plafond de rendement
    // =====================================================================

    /**
     * L'arbre seul ne sature pas le plafond de rendement.
     *
     * `ActionYieldResolver::MAX_BONUS_PERCENT` borne le cumul **toutes
     * competences confondues**. Un arbre qui l'atteindrait a lui seul rendrait
     * inertes tous les autres nœuds de rendement du jeu — et le joueur assidu
     * retrouverait par le rendement le debit que le budget d'energie lui refuse.
     */
    public function testTheTreeAloneDoesNotSaturateTheYieldCeiling(): void
    {
        preg_match_all("/'category' => 'gather_percent', 'percent' => (\d+)/", $this->tree(), $matches);

        $total = array_sum(array_map('intval', $matches[1]));

        self::assertGreaterThan(0, $total, 'L\'arbre n\'accorde plus aucun rendement.');
        self::assertLessThan(
            ActionYieldResolver::MAX_BONUS_PERCENT,
            $total,
            'L\'arbre du bucheron sature a lui seul le plafond de rendement.',
        );
    }

    /**
     * Chaque prerequis nomme un nœud de l'arbre.
     *
     * Un prerequis qui nomme un nœud absent rend la competence inatteignable
     * sans que rien ne le dise — la meme famille de defaut qu'ECO-18 avait
     * trouvee entre arbres et recettes.
     */
    public function testEveryPrerequisiteNamesANodeOfThisTree(): void
    {
        $nodes = $this->nodes();

        preg_match_all("/'requirements' => \[([^\]]+)\]/", $this->tree(), $blocks);

        $unknown = [];
        foreach ($blocks[1] as $block) {
            preg_match_all("/'(lumber_[a-z_0-9]+)'/", $block, $required);
            foreach ($required[1] as $slug) {
                if (!\in_array($slug, $nodes, true)) {
                    $unknown[] = $slug;
                }
            }
        }

        self::assertSame([], array_values(array_unique($unknown)), 'Ces prerequis nomment des nœuds absents de l\'arbre.');
    }
}
