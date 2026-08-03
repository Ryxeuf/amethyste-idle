<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Guild;
use App\Entity\App\Player;
use App\Entity\App\Settlement;
use App\Entity\App\SettlementContribution;
use App\Entity\App\Zone;
use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\Enum\SettlementType;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Settlement\CrueQuotaService;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Settlement\SettlementGate;
use App\GameEngine\Settlement\SettlementPanelBuilder;
use App\GameEngine\Settlement\SettlementServiceDirectory;
use App\GameEngine\Settlement\VassalageService;
use App\GameEngine\World\WorldScaleService;
use App\Repository\SettlementContributionRepository;
use App\Repository\SettlementRepository;
use App\Repository\SettlementWeeklyWorkContributionRepository;
use App\Repository\SettlementWeeklyWorkRepository;
use PHPUnit\Framework\TestCase;

/**
 * Ce que l'ecran de zone montre du foyer (FOY-04).
 *
 * Le calcul qui merite le plus d'attention est la **jauge**. Lue depuis zero,
 * elle serait pleine a 97 % des le Bourg et ne bougerait plus jamais, alors que
 * le chemin vers la Cite reste presque entier : le joueur verrait une barre
 * immobile et en conclurait, a raison, que sa frequentation ne sert a rien.
 */
class SettlementPanelBuilderTest extends TestCase
{
    private Zone $zone;
    private ?Settlement $settlement = null;
    private ?SettlementContribution $contribution = null;
    private ?Guild $guild = null;
    private int $guildTotal = 0;

    /** Facteur de monde lu par le panneau (BALANCE § 24.3) ; 1 par defaut. */
    private float $worldScale = 1.0;

    protected function setUp(): void
    {
        $this->zone = new Zone();
        $this->settlement = null;
        $this->contribution = null;
        $this->guild = null;
        $this->guildTotal = 0;
        $this->worldScale = 1.0;
    }

    /**
     * Une zone sans foyer ne montre rien — surtout pas une jauge a zero, qui
     * laisserait croire a un chantier abandonne alors qu'il n'y a simplement
     * rien a batir sur la Voute.
     */
    public function testAZoneWithoutSettlementShowsNothing(): void
    {
        self::assertNull($this->builder()->build($this->zone, new Player()));
    }

    public function testThePanelReportsRankTypeAndTotal(): void
    {
        $this->settlementWith(['trade' => 900, 'war' => 300], SettlementRank::Hamlet);
        $this->settlement->setType(SettlementType::Trading);

        $panel = $this->builder()->build($this->zone);

        self::assertNotNull($panel);
        self::assertSame(SettlementRank::Hamlet, $panel['rank']);
        self::assertSame(SettlementType::Trading, $panel['type']);
        self::assertSame(1200, $panel['total']);
    }

    public function testEachIndexIsReportedWithItsShare(): void
    {
        $this->settlementWith(['trade' => 600, 'war' => 300, 'lore' => 100], SettlementRank::Hamlet);

        $panel = $this->builder()->build($this->zone);

        self::assertNotNull($panel);
        self::assertCount(4, $panel['indices']);
        self::assertSame(SettlementIndex::Trade, $panel['indices'][0]['index']);
        self::assertSame(600, $panel['indices'][0]['value']);
        self::assertSame(60, $panel['indices'][0]['share']);
        self::assertSame(0, $panel['indices'][3]['value']);
    }

    /**
     * Le coeur du panneau : la progression se lit **entre deux paliers**.
     * A 4 000 grains, un Bourg est a mi-chemin de la Cite — pas a 16 % comme le
     * dirait une lecture depuis zero, ni a 50 % de son propre seuil deja franchi.
     */
    public function testProgressIsReadBetweenTwoTiersNotFromZero(): void
    {
        // Bourg (8 000) visant la Cite (25 000), a 16 500 : pile a mi-chemin.
        $this->settlementWith(['trade' => 16500], SettlementRank::Town);

        $panel = $this->builder()->build($this->zone);

        self::assertNotNull($panel);
        self::assertNotNull($panel['next']);
        self::assertSame(SettlementRank::City, $panel['next']['rank']);
        self::assertSame(25000, $panel['next']['threshold']);
        self::assertSame(8500, $panel['next']['missing']);
        self::assertSame(50, $panel['next']['progress']);
    }

    /**
     * BALANCE § 24.3 — le seuil affiche est celui que le tick applique : les
     * seuils calibres a W = 1, mis a l'echelle du monde. Montrer le seuil
     * nominal promettrait une Cite moins chere que ce qu'elle coute.
     */
    public function testTheGaugeShowsTheWorldScaledThreshold(): void
    {
        $this->worldScale = 2.0;
        // Bourg visant la Cite : a W = 2 elle coute 50 000, et 16 500 grains
        // n'en font plus la moitie du chemin depuis le Bourg (16 000).
        $this->settlementWith(['trade' => 16500], SettlementRank::Town);

        $panel = $this->builder()->build($this->zone);

        self::assertNotNull($panel);
        self::assertNotNull($panel['next']);
        self::assertSame(SettlementRank::City, $panel['next']['rank']);
        self::assertSame(50000, $panel['next']['threshold']);
        self::assertSame(33500, $panel['next']['missing']);
    }

    public function testAFreshRuinStartsItsGaugeAtZero(): void
    {
        $this->settlementWith([], SettlementRank::Ruin);

        $panel = $this->builder()->build($this->zone);

        self::assertNotNull($panel);
        self::assertNotNull($panel['next']);
        self::assertSame(SettlementRank::Camp, $panel['next']['rank']);
        self::assertSame(0, $panel['next']['progress']);
        self::assertSame(150, $panel['next']['missing']);
    }

    /**
     * Un chiffre qui monte sans promesse n'est qu'une barre. C'est la phrase
     * « au Bourg, le marche ouvre » qui transforme une frequentation en projet.
     */
    public function testTheNextTierSaysWhatItWillOpen(): void
    {
        $this->settlementWith(['trade' => 2000], SettlementRank::Hamlet);

        $panel = $this->builder()->build($this->zone);

        self::assertNotNull($panel);
        self::assertNotNull($panel['next']);
        self::assertSame(SettlementRank::Town, $panel['next']['rank']);
        self::assertSame(['regional_market'], $panel['next']['opens']);
    }

    /**
     * Seulement ce que **ce** palier ouvre : reciter l'acquis noierait la
     * promesse.
     */
    public function testTheNextTierDoesNotRepeatWhatIsAlreadyOpen(): void
    {
        $this->settlementWith(['trade' => 9000], SettlementRank::Town);

        $panel = $this->builder()->build($this->zone);

        self::assertNotNull($panel);
        self::assertNotNull($panel['next']);
        self::assertNotContains('regional_market', $panel['next']['opens']);
        self::assertContains('zone_bank', $panel['next']['opens']);
    }

    /**
     * La ligne « au palier suivant » promet ; le bloc des services donne la
     * porte (FOY-06). Les deux doivent tenir sur le meme panneau, sinon le
     * joueur lit une promesse sans jamais voir ce qu'elle a deja tenu.
     */
    public function testThePanelCarriesTheDoorsTheRankHasAlreadyOpened(): void
    {
        $this->settlementWith(['trade' => 9000], SettlementRank::Town);

        $panel = $this->builder()->build($this->zone);

        self::assertNotNull($panel);

        $open = array_column(array_filter($panel['services'], static fn (array $row): bool => $row['open']), 'service');
        self::assertSame(['regional_market'], array_values($open));
    }

    public function testASettlementAtTheSummitHasNoNextTier(): void
    {
        $this->settlementWith(['trade' => 70000], SettlementRank::Metropolis);

        $panel = $this->builder()->build($this->zone);

        self::assertNotNull($panel);
        self::assertNull($panel['next']);
    }

    /**
     * Le foyer a decroche. Le signaler prepare l'annonce d'etiage de FOY-10 :
     * une retrogradation ne doit jamais etre une surprise.
     */
    public function testASlippingSettlementIsFlaggedWithItsFormerRank(): void
    {
        $this->settlementWith(['trade' => 200], SettlementRank::Town);
        $this->settlement->setRank(SettlementRank::Camp);

        $panel = $this->builder()->build($this->zone);

        self::assertNotNull($panel);
        self::assertTrue($panel['ebbing']);
        self::assertSame(SettlementRank::Town, $panel['highestRank']);
    }

    public function testASettlementAtItsBestIsNotFlaggedAsSlipping(): void
    {
        $this->settlementWith(['trade' => 2000], SettlementRank::Hamlet);

        $panel = $this->builder()->build($this->zone);

        self::assertNotNull($panel);
        self::assertFalse($panel['ebbing']);
    }

    /**
     * Un chiffre personnel seul laisse croire qu'on porte la ville a soi tout
     * seul. Le chiffre collectif est ce qui donne envie d'y revenir ensemble.
     */
    public function testThePanelShowsWhatThePlayerAndTheirGuildBuilt(): void
    {
        $this->settlementWith(['trade' => 2000], SettlementRank::Hamlet);
        $player = new Player();
        $this->contribution = new SettlementContribution($this->settlement, $player);
        $this->contribution->addGrains(140);
        $this->guild = new Guild();
        $this->guildTotal = 900;

        $panel = $this->builder()->build($this->zone, $player);

        self::assertNotNull($panel);
        self::assertSame(140, $panel['contribution']);
        self::assertSame(900, $panel['guildContribution']);
    }

    public function testAPlayerWithoutAGuildSeesOnlyTheirOwnShare(): void
    {
        $this->settlementWith(['trade' => 2000], SettlementRank::Hamlet);
        $player = new Player();
        $this->contribution = new SettlementContribution($this->settlement, $player);
        $this->contribution->addGrains(140);

        $panel = $this->builder()->build($this->zone, $player);

        self::assertNotNull($panel);
        self::assertSame(140, $panel['contribution']);
        self::assertSame(0, $panel['guildContribution']);
    }

    public function testAnAnonymousRenderStillDescribesTheSettlement(): void
    {
        $this->settlementWith(['trade' => 2000], SettlementRank::Hamlet);

        $panel = $this->builder()->build($this->zone);

        self::assertNotNull($panel);
        self::assertSame(0, $panel['contribution']);
        self::assertSame(0, $panel['guildContribution']);
    }

    /**
     * @param array<string, int> $sediment
     */
    private function settlementWith(array $sediment, SettlementRank $rank): void
    {
        $this->settlement = new Settlement($this->zone);
        $this->settlement->setRank($rank);
        foreach (SettlementIndex::cases() as $index) {
            $this->settlement->setSediment($index, $sediment[$index->value] ?? 0);
        }
    }

    private function builder(): SettlementPanelBuilder
    {
        $settlementRepository = $this->createMock(SettlementRepository::class);
        $settlementRepository->method('findOneByZone')->willReturnCallback(
            fn (Zone $zone): ?Settlement => $zone === $this->zone ? $this->settlement : null,
        );

        $contributionRepository = $this->createMock(SettlementContributionRepository::class);
        $contributionRepository->method('findOneFor')->willReturnCallback(
            fn (): ?SettlementContribution => $this->contribution,
        );
        $contributionRepository->method('sumForGuild')->willReturnCallback(
            fn (): int => $this->guildTotal,
        );

        $guildManager = $this->createMock(GuildManager::class);
        $guildManager->method('getPlayerGuild')->willReturnCallback(fn (): ?Guild => $this->guild);

        $definition = [
            'ranks' => ['camp' => 150, 'hamlet' => 1200, 'town' => 8000, 'city' => 25000, 'metropolis' => 60000],
            'decay_rate' => 0.02,
            'dominance_margin' => 0.25,
            'sustain_days' => 28,
            'minimum_type_rank' => SettlementRank::Hamlet,
            'sediment' => [],
            'daily_cap_per_player' => 60,
            'diminishing_threshold' => 40,
            'diminishing_factor' => 0.5,
            'services' => [
                'regional_market' => SettlementRank::Town,
                'zone_bank' => SettlementRank::City,
                'group_dungeon' => SettlementRank::City,
                'awakening_altar' => SettlementRank::Metropolis,
            ],
            'never_gated' => ['shop' => 'boutiques existantes'],
            'seed' => [],
            'without_settlement' => [],
        ];

        $loader = $this->createMock(SettlementDefinitionLoader::class);
        $loader->method('load')->willReturn($definition);

        $gate = new SettlementGate($settlementRepository, $loader);

        $worldScale = $this->createMock(WorldScaleService::class);
        $worldScale->method('current')->willReturnCallback(fn (): float => $this->worldScale);

        return new SettlementPanelBuilder(
            $settlementRepository,
            $contributionRepository,
            $loader,
            $gate,
            $guildManager,
            new SettlementServiceDirectory($gate),
            $this->workRepository(),
            $this->createMock(SettlementWeeklyWorkContributionRepository::class),
            $this->crueQuota(),
            $this->vassalage(),
            $worldScale,
            // FOY-18 : les parcelles ont leur propre test — ici, le mock rend
            // null (un rang qui ne loge pas), l'etat le plus neutre.
            $this->createMock(\App\GameEngine\Housing\ResidentialParcels::class),
        );
    }

    /**
     * RET-05 : ces tests portent sur le foyer, pas sur son chantier. Une zone
     * sans chantier ouvert est l'etat normal avant le premier lundi.
     */
    private function workRepository(): SettlementWeeklyWorkRepository
    {
        $repository = $this->createMock(SettlementWeeklyWorkRepository::class);
        $repository->method('findCurrentForZone')->willReturn(null);

        return $repository;
    }

    /**
     * FOY-08 : ces tests portent sur le foyer, pas sur la Crue. Un quota qui
     * autorise tout laisse le panneau sans bloc d'attente — l'etat d'un monde
     * qui n'a pas encore de concurrence pour ses places.
     */
    private function crueQuota(): CrueQuotaService
    {
        $quota = $this->createMock(CrueQuotaService::class);
        $quota->method('allows')->willReturn(true);
        $quota->method('occupants')->willReturn([]);

        return $quota;
    }

    /**
     * FOY-09 : ces tests portent sur le foyer, pas sur son voisinage. Une zone
     * sans grande voisine est l'etat de bordure — celui de la plupart des
     * foyers tant que le monde n'a pas de capitale.
     */
    private function vassalage(): VassalageService
    {
        $vassalage = $this->createMock(VassalageService::class);
        $vassalage->method('overlordOf')->willReturn(null);
        $vassalage->method('capFor')->willReturn(null);

        return $vassalage;
    }
}
