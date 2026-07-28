<?php

namespace App\Tests\Unit\Translation;

use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\Enum\SettlementType;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Loi transverse : tout ce que l'ecran de zone nomme a un libelle, dans les deux
 * langues (FOY-04).
 *
 * Le panneau du foyer compose ses clefs de traduction : `'game.settlement.rank.'
 * ~ rank.value`. C'est ce qui lui permet de suivre les enums sans les recopier —
 * mais c'est aussi ce qui le rend invisible a `TranslationCatalogAuditTest`, qui
 * reconnait `'clef'|trans` et ne peut rien faire d'une concatenation.
 *
 * Le defaut est donc a portee de main et parfaitement muet : ajouter un rang, un
 * type ou un service gate, et voir s'afficher `game.settlement.service.zone_bank`
 * en clair sur l'ecran d'un joueur. Aucun test ne casse, aucune exception n'est
 * levee — la chaine brute *est* le rendu.
 *
 * Cette loi ferme le trou : chaque valeur d'enum et chaque service declare doit
 * avoir son libelle en francais **et** en anglais.
 */
class SettlementLabelCoverageTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function catalog(string $locale): array
    {
        $path = \dirname(__DIR__, 3) . '/translations/messages.' . $locale . '.json';
        self::assertFileExists($path);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) file_get_contents($path), true, 512, \JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * @return array<string, string>
     */
    private function labels(string $locale, string $group): array
    {
        $catalog = $this->catalog($locale);
        $labels = $catalog['game']['settlement'][$group] ?? null;

        self::assertIsArray($labels, sprintf('Le groupe "game.settlement.%s" manque en "%s".', $group, $locale));

        /** @var array<string, string> $labels */
        return $labels;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function groupProvider(): array
    {
        return [
            'rangs' => ['rank', array_map(static fn (SettlementRank $r): string => $r->value, SettlementRank::cases())],
            'types' => ['type', array_map(static fn (SettlementType $t): string => $t->value, SettlementType::cases())],
            'indices' => ['index', array_map(static fn (SettlementIndex $i): string => $i->value, SettlementIndex::cases())],
        ];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('groupProvider')]
    public function testEveryEnumValueHasALabelInBothLocales(string $group, array $expected): void
    {
        foreach (['fr', 'en'] as $locale) {
            $labels = $this->labels($locale, $group);
            $missing = array_values(array_diff($expected, array_keys($labels)));

            self::assertSame([], $missing, sprintf(
                'Libelles manquants dans "game.settlement.%s" en "%s" : %s.',
                $group,
                $locale,
                implode(', ', $missing),
            ));

            foreach ($labels as $key => $label) {
                self::assertNotSame('', trim($label), sprintf('Le libelle "%s" est vide en "%s".', $key, $locale));
            }
        }
    }

    /**
     * Un service gate sans libelle s'afficherait en clair sur l'ecran de zone,
     * sous la forme d'une clef de traduction. C'est le rendu le plus laid qu'un
     * oubli de configuration puisse produire, et rien d'autre ne l'attrape.
     */
    public function testEveryGatedServiceHasALabelInBothLocales(): void
    {
        $services = array_keys((new SettlementDefinitionLoader(\dirname(__DIR__, 3)))->load()['services']);
        self::assertNotEmpty($services);

        foreach (['fr', 'en'] as $locale) {
            $labels = $this->labels($locale, 'service');
            $missing = array_values(array_diff($services, array_keys($labels)));

            self::assertSame([], $missing, sprintf(
                'Services gates sans libelle en "%s" : %s.',
                $locale,
                implode(', ', $missing),
            ));
        }
    }

    /**
     * L'inverse compte aussi : un libelle qui ne correspond a aucun service
     * declare est un vestige, et un vestige finit par etre pris pour une
     * intention.
     */
    public function testNoLabelDescribesAServiceThatDoesNotExist(): void
    {
        $services = array_keys((new SettlementDefinitionLoader(\dirname(__DIR__, 3)))->load()['services']);

        foreach (['fr', 'en'] as $locale) {
            $stale = array_values(array_diff(array_keys($this->labels($locale, 'service')), $services));

            self::assertSame([], $stale, sprintf(
                'Libelles de services orphelins en "%s" : %s.',
                $locale,
                implode(', ', $stale),
            ));
        }
    }
}
