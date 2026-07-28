<?php

namespace App\Tests\Unit\GameEngine\Economy;

use App\Entity\App\WeeklyOutcrop;
use App\Entity\App\Zone;
use App\Enum\Purity;
use App\GameEngine\Economy\PurityDefinitionLoader;
use App\GameEngine\Economy\PurityDrawer;
use App\GameEngine\Economy\PurityScope;
use App\GameEngine\Economy\WeeklyOutcropSelector;
use App\GameEngine\Progression\ActionYieldResolver;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\Repository\WeeklyOutcropRepository;
use App\Repository\ZoneRepository;
use App\Repository\ZoneVeinRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * L'Affleurement de la semaine (RET-06).
 *
 * La rotation hebdomadaire du monde a **cout d'ecriture nul** (levier Ryzom) :
 * rien n'est cree, rien n'est deplace — une seule ligne change ce que la carte
 * vaut cette semaine.
 *
 * **La discretion est un critere d'acceptance, pas une option.** L'information
 * se decouvre par prospection sur place ou s'achete a qui l'a trouvee ; un
 * affleurement annonce deviendrait une ruee et cesserait d'etre une decouverte.
 * `testNothingPublicNamesTheOutcrop` verrouille cette propriete.
 */
class WeeklyOutcropTest extends TestCase
{
    /**
     * Le tirage est **deterministe** : rejouer la rotation ne deplace pas
     * l'affleurement. Le contraire serait un reroll, et le prospecteur qui l'a
     * trouve le matin aurait perdu son information l'apres-midi.
     */
    public function testTheDrawIsDeterministicForAGivenWeek(): void
    {
        $first = $this->selectFor();
        $second = $this->selectFor();

        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertSame($first->getZone()->getSlug(), $second->getZone()->getSlug());
        self::assertSame($first->getVeinSlug(), $second->getVeinSlug());
    }

    /**
     * Jamais deux semaines de suite la meme zone : sans cette regle, un tirage
     * malchanceux immobiliserait la rotation sur une seule region et retirerait a
     * la brique la seule chose qu'elle produit — une raison de bouger.
     */
    public function testTheSameZoneIsNeverDrawnTwoWeeksRunning(): void
    {
        $previous = new WeeklyOutcrop('2026-W30', $this->zone('mines-profondes', ['fer']), 'fer');

        $selected = $this->selectFor($previous);

        self::assertNotNull($selected);
        self::assertNotSame('mines-profondes', $selected->getZone()->getSlug());
    }

    /**
     * Seuls les filons dont la matiere porte une bande sont eligibles. Faire
     * monter d'un cran la bande d'une botte d'herbes n'aurait aucun effet, et
     * l'affleurement serait muet une semaine sur deux sans que rien ne le dise.
     */
    public function testOnlyVeinsWithinThePurityScopeAreEligible(): void
    {
        $herbsOnly = $this->zone('vallons', ['sauge'], 'herb-sage');

        $report = $this->selector([$herbsOnly])->select(new \DateTimeImmutable('2026-07-27'));

        self::assertNull($report['selected']);
        self::assertSame(0, $report['candidates']);
    }

    /**
     * L'affleurement monte le plafond d'un cran, **apres** la vitalite et non a
     * sa place : un filon ereinte reste ereinte. C'est ce qui empeche la brique
     * de devenir une dispense de gestion.
     */
    public function testTheOutcropRaisesTheCeilingByOneBandWithoutRescuingAnExhaustedVein(): void
    {
        $zone = $this->zone('mines-profondes', ['fer']);
        $drawer = $this->drawerWithOutcrop(new WeeklyOutcrop($this->currentWeek(), $zone, 'fer'));

        // Vitalite a 50 % : plafond « pur », que l'affleurement monte a « parfait ».
        self::assertSame(Purity::Parfait, $drawer->ceiling(50, 100, $zone, 'fer'));

        // Vitalite a 5 % : plafond « trouble », que l'affleurement monte a
        // « clair » — un cran, pas une resurrection.
        self::assertSame(Purity::Clair, $drawer->ceiling(5, 100, $zone, 'fer'));
    }

    public function testAnotherVeinOfTheSameZoneIsNotRaised(): void
    {
        $zone = $this->zone('mines-profondes', ['fer', 'cuivre']);
        $drawer = $this->drawerWithOutcrop(new WeeklyOutcrop($this->currentWeek(), $zone, 'fer'));

        self::assertSame(Purity::Pur, $drawer->ceiling(50, 100, $zone, 'cuivre'));
    }

    public function testWithoutAVeinIdentityNothingIsRaised(): void
    {
        $zone = $this->zone('mines-profondes', ['fer']);
        $drawer = $this->drawerWithOutcrop(new WeeklyOutcrop($this->currentWeek(), $zone, 'fer'));

        self::assertSame(Purity::Pur, $drawer->ceiling(50, 100));
    }

    /**
     * **La discretion, rendue executable.** L'affleurement ne doit etre lu que
     * par le tirage de purete et par sa propre commande de rotation. Le jour ou
     * un controleur, une API ou un gabarit le lira, il sera annonce — et la
     * brique aura perdu la seule chose qu'elle produit.
     */
    public function testNothingPublicNamesTheOutcrop(): void
    {
        $root = \dirname(__DIR__, 4);

        $allowed = [
            'src/GameEngine/Economy/PurityDrawer.php',
            'src/GameEngine/Economy/WeeklyOutcropSelector.php',
            'src/Command/WeeklyOutcropRotateCommand.php',
            'src/Entity/App/WeeklyOutcrop.php',
            'src/Repository/WeeklyOutcropRepository.php',
        ];

        $offenders = [];
        foreach (['src/Controller', 'templates', 'assets'] as $directory) {
            $path = $root . '/' . $directory;
            if (!is_dir($path)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                    continue;
                }

                if (!\in_array($file->getExtension(), ['php', 'twig', 'js'], true)) {
                    continue;
                }

                $relative = str_replace($root . '/', '', $file->getPathname());
                if (\in_array($relative, $allowed, true)) {
                    continue;
                }

                $contents = (string) file_get_contents($file->getPathname());
                if (str_contains($contents, 'WeeklyOutcrop') || str_contains($contents, 'outcrop')) {
                    $offenders[] = $relative;
                }
            }
        }

        self::assertSame([], $offenders, sprintf(
            "L'affleurement est nomme par : %s.\nIl ne doit se decouvrir que par prospection.",
            implode(', ', $offenders),
        ));
    }

    private function currentWeek(): string
    {
        return (new \DateTimeImmutable())->format('o-\WW');
    }

    /**
     * @param list<string> $veins
     */
    private function zone(string $slug, array $veins, string $item = 'ore-iron'): Zone
    {
        $zone = new Zone();
        $zone->setSlug($slug);
        $zone->setName($slug);
        $zone->setGatherConfig([
            'resources' => array_map(
                static fn (string $vein): array => ['slug' => $vein, 'item' => $item],
                $veins,
            ),
        ]);

        return $zone;
    }

    private function selectFor(?WeeklyOutcrop $previous = null): ?WeeklyOutcrop
    {
        $zones = [
            $this->zone('mines-profondes', ['fer']),
            $this->zone('crete-de-ventombre', ['cobalt']),
            $this->zone('dunes-d-ambre', ['ambre']),
        ];

        $report = $this->selector($zones, $previous)->select(new \DateTimeImmutable('2026-07-27'));

        return $report['selected'];
    }

    /**
     * @param list<Zone> $zones
     */
    private function selector(array $zones, ?WeeklyOutcrop $previous = null): WeeklyOutcropSelector
    {
        $zoneRepository = $this->createMock(ZoneRepository::class);
        $zoneRepository->method('findAll')->willReturn($zones);

        $outcropRepository = $this->createMock(WeeklyOutcropRepository::class);
        $outcropRepository->method('findForWeek')->willReturn(null);
        $outcropRepository->method('findPrevious')->willReturn($previous);

        $loader = $this->createMock(PurityDefinitionLoader::class);
        $loader->method('load')->willReturn($this->definition());

        return new WeeklyOutcropSelector(
            $this->createMock(EntityManagerInterface::class),
            $zoneRepository,
            $outcropRepository,
            new PurityScope($loader),
            ...$this->palenessStubs(),
        );
    }

    private function drawerWithOutcrop(WeeklyOutcrop $outcrop): PurityDrawer
    {
        $loader = $this->createMock(PurityDefinitionLoader::class);
        $loader->method('load')->willReturn($this->definition());

        $outcropRepository = $this->createMock(WeeklyOutcropRepository::class);
        $outcropRepository->method('findForWeek')->willReturn($outcrop);

        return new PurityDrawer(new PurityScope($loader), $loader, new ActionYieldResolver(), $outcropRepository, ...$this->palenessStubs());
    }

    /**
     * @return array{scope: array{slug_prefixes: list<string>, excluded_slugs: list<string>, included_slugs: list<string>}, draw: array<string, mixed>}
     */
    private function definition(): array
    {
        return [
            'scope' => ['slug_prefixes' => ['ore-'], 'excluded_slugs' => [], 'included_slugs' => []],
            'draw' => [
                'base_weights' => ['trouble' => 60, 'clair' => 30, 'pur' => 9, 'parfait' => 1],
                'vitality_ceilings' => [
                    ['at_least' => 0.66, 'band' => Purity::Parfait],
                    ['at_least' => 0.33, 'band' => Purity::Pur],
                    ['at_least' => 0.10, 'band' => Purity::Clair],
                    ['at_least' => 0.0, 'band' => Purity::Trouble],
                ],
                'skill_weight_per_point' => 1,
                'skill_weight_cap' => 25,
            ],
        ];
    }

    /**
     * FOY-11 : ces tests portent sur l'affleurement et le tirage, pas sur la
     * Paleur. Un depot sans filon pali laisse la seconde borne inactive — l'etat
     * normal d'un monde qu'on n'a pas encore ereinte.
     *
     * @return array{0: ZoneVeinRepository, 1: SettlementDefinitionLoader}
     */
    private function palenessStubs(): array
    {
        $veinRepository = $this->createMock(ZoneVeinRepository::class);
        $veinRepository->method('findOneByZoneAndSlug')->willReturn(null);

        $settlementLoader = $this->createMock(SettlementDefinitionLoader::class);
        $settlementLoader->method('load')->willReturn(['paleness' => [
            'rise_per_pressure' => 0.08,
            'daily_recovery' => 0.04,
            'max' => 0.6,
            'visible_from' => 0.1,
            'dulls_purity_from' => 0.3,
        ]]);

        return [$veinRepository, $settlementLoader];
    }
}
