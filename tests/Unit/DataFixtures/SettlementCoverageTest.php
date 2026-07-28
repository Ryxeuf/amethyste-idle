<?php

namespace App\Tests\Unit\DataFixtures;

use App\Enum\SettlementRank;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * Loi transverse : toute zone du monde a un foyer, ou une raison de ne pas en
 * avoir (FOY-01).
 *
 * Le defaut que ce test empeche est le silence habituel de cette famille : une
 * zone ajoutee au YAML du monde sans qu'on pense a son foyer n'echoue nulle
 * part. Elle s'ouvre, on y joue, on y depose du sediment... qui ne se depose
 * dans rien. Six semaines plus tard, un joueur demande pourquoi sa zone ne
 * monte pas, et la reponse est « on a oublie ».
 *
 * D'ou l'exigence d'exhaustivite : chaque zone reelle est **soit** dans `seed`,
 * **soit** dans `without_settlement` avec une raison ecrite. Une zone dans
 * aucune des deux listes fait rougir la CI le jour ou elle est ajoutee, pas le
 * jour ou elle est jouee.
 *
 * L'inverse compte autant : une entree qui ne correspond a aucune zone reelle
 * est un vestige, et un vestige non signale finit par etre pris pour une
 * intention.
 */
class SettlementCoverageTest extends TestCase
{
    /**
     * Zones du seed qui n'existent pas encore, et le jalon qui les apportera.
     *
     * Cette liste doit rester **vide ou courte**. Elle existe pour qu'un seed
     * ecrit en avance d'une zone soit une decision visible et datee, pas un
     * slug mort.
     *
     * @var array<string, string>
     */
    private const PLANNED_ZONES = [];

    /**
     * @return array<string, mixed>
     */
    private function definition(): array
    {
        return (new SettlementDefinitionLoader($this->projectDir()))->load();
    }

    /**
     * @return list<string>
     */
    private function realZoneSlugs(): array
    {
        $loader = new ZoneDefinitionLoader($this->projectDir());
        $world = $loader->loadFile($loader->defaultFile());

        $slugs = [];
        foreach ($world['zones'] as $zone) {
            $slugs[] = $zone['slug'];
        }

        return $slugs;
    }

    private function projectDir(): string
    {
        return \dirname(__DIR__, 3);
    }

    public function testTheShippedConfigLoads(): void
    {
        $definition = $this->definition();

        self::assertNotEmpty($definition['ranks']);
        self::assertNotEmpty($definition['sediment']);
        self::assertGreaterThan(0.0, $definition['decay_rate']);
    }

    public function testEveryZoneIsEitherSeededOrExplicitlyWithoutSettlement(): void
    {
        $definition = $this->definition();
        $covered = array_merge(
            array_keys($definition['seed']),
            array_keys($definition['without_settlement']),
        );

        $uncovered = array_values(array_diff($this->realZoneSlugs(), $covered));

        self::assertSame([], $uncovered, sprintf(
            "Ces zones n'ont ni foyer seede ni raison ecrite de ne pas en avoir : %s.\n"
            . 'Ajoutez-les a `seed` ou a `without_settlement` dans config/game/settlements.yaml.',
            implode(', ', $uncovered),
        ));
    }

    public function testNoSettlementEntryPointsAtAGhostZone(): void
    {
        $definition = $this->definition();
        $real = $this->realZoneSlugs();

        $ghosts = array_values(array_diff(
            array_merge(array_keys($definition['seed']), array_keys($definition['without_settlement'])),
            $real,
            array_keys(self::PLANNED_ZONES),
        ));

        self::assertSame([], $ghosts, sprintf(
            'Ces entrees de settlements.yaml ne correspondent a aucune zone : %s.',
            implode(', ', $ghosts),
        ));
    }

    public function testNoZoneIsBothSeededAndDeclaredWithoutSettlement(): void
    {
        $definition = $this->definition();

        $both = array_values(array_intersect(
            array_keys($definition['seed']),
            array_keys($definition['without_settlement']),
        ));

        self::assertSame([], $both, sprintf(
            'Ces zones se contredisent — seedees ET declarees sans foyer : %s.',
            implode(', ', $both),
        ));
    }

    /**
     * Le seed est **narratif, pas protecteur** (BALANCE § 23.5) : il pose ce que
     * la zone offre deja, il ne donne pas d'avance. Un seed au-dessus du Bourg
     * offrirait un marche que personne n'a bati — exactement le retro-gate que
     * la decision A ecarte, mais dans l'autre sens.
     */
    public function testSeededRanksStayBelowTheGuildThreshold(): void
    {
        foreach ($this->definition()['seed'] as $slug => $entry) {
            self::assertTrue(
                SettlementRank::Town->isAtLeast($entry['rank']),
                sprintf('La zone "%s" est seedee en %s : au-dela du Bourg, le foyer serait offert, pas bati.', $slug, $entry['rank']->value),
            );
        }
    }

    /**
     * Le stock seede doit tenir le rang seede : un Hameau annonce avec un stock
     * de Campement redescendrait au premier tick, et le monde livre s'ouvrirait
     * en se contredisant.
     */
    public function testSeededStockSupportsTheSeededRank(): void
    {
        $definition = $this->definition();

        foreach ($definition['seed'] as $slug => $entry) {
            if ($entry['rank'] === SettlementRank::Ruin) {
                continue;
            }

            $threshold = $definition['ranks'][$entry['rank']->value];
            self::assertGreaterThanOrEqual($threshold, $entry['stock'], sprintf(
                'La zone "%s" est seedee en %s (seuil %d) avec seulement %d grains.',
                $slug,
                $entry['rank']->value,
                $threshold,
                $entry['stock'],
            ));
        }
    }

    /**
     * Le seed ne pose **aucun type** : le stock est reparti a parts egales sur
     * les quatre indices, donc aucun ne domine. L'identite d'une ville se gagne
     * en jouant, elle ne se decrete pas dans un fichier.
     *
     * La divisibilite par quatre n'est pas de la coquetterie : `SettlementSeeder`
     * pose `intdiv($stock, 4)` par indice, donc un stock non divisible pose
     * **moins que ce qu'il annonce**. Un Hameau ecrit a 1 201 grains en poserait
     * 1 200 — sous son propre seuil — et redescendrait au premier tick sans que
     * le fichier ne mente nulle part visiblement.
     */
    public function testSeededStockSplitsEvenlyAcrossTheFourIndices(): void
    {
        foreach ($this->definition()['seed'] as $slug => $entry) {
            self::assertSame(0, $entry['stock'] % 4, sprintf(
                'Le stock seede de "%s" (%d) n\'est pas divisible par 4 : le reste donnerait un type a la zone.',
                $slug,
                $entry['stock'],
            ));
        }
    }
}
