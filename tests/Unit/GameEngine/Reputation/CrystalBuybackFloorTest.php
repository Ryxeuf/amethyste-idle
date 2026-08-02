<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Entity\Game\Item;
use App\GameEngine\Reputation\CrystalBuybackFloor;
use App\GameEngine\Reputation\HostileConsequenceResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Le plancher d'achat du cristal — bas, garanti, et jamais ailleurs (FAC-04a).
 *
 * GAME_WORLD § 12.2 : la Fonderie rachete toujours le cristal. Le plancher ne
 * vaut qu'a son comptoir et que pour l'amethystite ; il se ferme aux Hostiles
 * (la consequence buyback_floor_closed de FAC-03 prend vie ici) — et quand il
 * se ferme, le rachat commun reste : la boucle cœur ne ferme jamais.
 */
class CrystalBuybackFloorTest extends TestCase
{
    public function testTheCrystalHasAGuaranteedPriceAtTheFoundryCounter(): void
    {
        $floor = $this->floor(closed: false);

        self::assertSame(
            CrystalBuybackFloor::FLOOR_PRICE,
            $floor->floorFor($this->counter(), $this->crystal(), new Player()),
        );
    }

    public function testTheFloorHoldsNowhereElse(): void
    {
        $floor = $this->floor(closed: false);
        $player = new Player();

        $otherPnj = $this->createMock(Pnj::class);
        $otherPnj->method('getSlug')->willReturn('mines-cantiniere-brida');

        self::assertNull($floor->floorFor($otherPnj, $this->crystal(), $player), 'Le plancher ne vaut qu\'au comptoir de la Fonderie.');
        self::assertNull($floor->floorFor(null, $this->crystal(), $player), 'Un PNJ sans slug (fixtures heritees) ne porte pas le plancher.');

        $ore = $this->createMock(Item::class);
        $ore->method('getSlug')->willReturn('ore-iron');
        self::assertNull($floor->floorFor($this->counter(), $ore, $player), 'Le plancher ne vaut que pour l\'amethystite.');
    }

    /**
     * Hostile chez la Fonderie : elle ne rachete plus votre cristal. Le
     * plancher se ferme — la garantie disparait, jamais le droit de vendre.
     */
    public function testTheFloorClosesForHostiles(): void
    {
        $floor = $this->floor(closed: true);

        self::assertNull($floor->floorFor($this->counter(), $this->crystal(), new Player()));
    }

    /**
     * « Bas mais garanti » : au-dessus du rachat commun (30 %), en dessous du
     * prix d'achat. Les bornes sont relues depuis la donnee reelle — si le
     * prix de l'amethystite bouge, ce test dit de recalibrer le plancher.
     */
    public function testTheFloorIsLowButAboveTheCommonRate(): void
    {
        $yaml = Yaml::parseFile(\dirname(__DIR__, 4) . '/fixtures/game/item/ore.yaml')['App\Entity\Game\Item'];
        $price = $yaml['ore_amethyst_crystal (extends item)']['price'];

        $commonRate = max(1, (int) ($price * 0.3));

        self::assertGreaterThan($commonRate, CrystalBuybackFloor::FLOOR_PRICE, 'Un plancher sous le taux commun ne garantirait rien.');
        self::assertLessThan($price, CrystalBuybackFloor::FLOOR_PRICE, 'Un plancher au prix d\'achat remplacerait le marche au lieu de le proteger.');
    }

    /**
     * Le comptoir du plancher existe reellement : chaque slug de la constante
     * est declare dans les PNJ de zone. Une coquille ferait un plancher qui ne
     * s'applique nulle part — un silence, jamais une erreur.
     */
    public function testEveryCounterSlugIsADeclaredZonePnj(): void
    {
        $world = (string) file_get_contents(\dirname(__DIR__, 4) . '/config/game/zones/world_1.yaml');

        foreach (CrystalBuybackFloor::COUNTER_SLUGS as $slug) {
            self::assertStringContainsString(
                'slug: ' . $slug,
                $world,
                sprintf('Le comptoir "%s" du plancher n\'est declare dans aucune zone.', $slug),
            );
        }
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

    private function floor(bool $closed): CrystalBuybackFloor
    {
        $resolver = $this->createMock(HostileConsequenceResolver::class);
        $resolver->method('isCrystalBuybackClosed')->willReturn($closed);

        return new CrystalBuybackFloor($resolver);
    }

    private function counter(): Pnj
    {
        $pnj = $this->createMock(Pnj::class);
        $pnj->method('getSlug')->willReturn('mines-comptoir-de-la-fonderie');

        return $pnj;
    }

    private function crystal(): Item
    {
        $item = $this->createMock(Item::class);
        $item->method('getSlug')->willReturn(CrystalBuybackFloor::CRYSTAL_SLUG);

        return $item;
    }
}
