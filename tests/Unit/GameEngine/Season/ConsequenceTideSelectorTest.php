<?php

namespace App\Tests\Unit\GameEngine\Season;

use App\Entity\App\Parameter;
use App\Entity\App\Zone;
use App\Entity\App\ZoneVein;
use App\Enum\ConsequenceTide;
use App\Enum\SettlementRank;
use App\GameEngine\Season\ConsequenceTideSelector;
use App\GameEngine\Season\TideDefinitionLoader;
use App\GameEngine\Settlement\CrueQuotaService;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\Repository\ZoneVeinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * La saison devient une boucle plutot qu'un calendrier (FOY-15).
 *
 * Quatre proprietes portent le jalon :
 *
 * 1. La Paleur se declenche sur un **etat** avéré — assez de filons portent une
 *    trace visible. Sous le seuil, rien ne se passe, et c'est le cas normal.
 * 2. L'Appel de la Crue se declenche sur une **variation** : une place s'est
 *    liberee. L'etat ne dirait rien, puisqu'au lancement toutes les places sont
 *    libres et que l'Appel sonnerait a chaque maree.
 * 3. Quand les deux sont vraies, **la Paleur passe devant** : la consequence
 *    negative est celle qui enseigne.
 * 4. La premiere cloture **pose le repere sans rien declencher** : un monde neuf
 *    a toutes ses places libres, et les annoncer serait un contresens.
 */
class ConsequenceTideSelectorTest extends TestCase
{
    // =====================================================================
    // La Paleur — un etat avere
    // =====================================================================

    public function testEnoughPaleVeinsSummonThePaleness(): void
    {
        self::assertSame(ConsequenceTide::Paleness, $this->selector($this->veins(6), [], null)->select());
    }

    /**
     * Sous le seuil, **rien**. C'est le resultat normal : la plupart des marees
     * ne sont pas des consequences, et en forcer une a chaque cloture rendrait
     * le declenchement insignifiant.
     */
    public function testAWorldBarelyPressedSummonsNothing(): void
    {
        self::assertNull($this->selector($this->veins(5), [], null)->select());
    }

    /**
     * Le seuil de visibilite est celui de FOY-11. Compter les traces
     * imperceptibles ferait sonner la Paleur pour un monde que personne ne voit
     * palir.
     */
    public function testInvisibleMarksAreNotCounted(): void
    {
        $veins = [];
        for ($i = 0; $i < 20; ++$i) {
            $veins[] = (new ZoneVein($this->zone('crete'), 'filon-' . $i, 10))->setPaleness(0.05);
        }

        self::assertNull($this->selector($veins, [], null)->select());
    }

    // =====================================================================
    // L'Appel de la Crue — une variation
    // =====================================================================

    /**
     * Une place de plus qu'au dernier releve : le monde a franchi un palier de
     * population, ou une regression a rendu une place. Du point de vue des
     * joueurs, la nouvelle est la meme.
     */
    public function testAFreshlyOpenedSlotSummonsTheCrueCall(): void
    {
        $selector = $this->selector([], ['town' => 2, 'city' => 1, 'metropolis' => 0], '{"town":1,"city":1,"metropolis":0}');

        self::assertSame(ConsequenceTide::CrueCall, $selector->select());
    }

    /**
     * L'etat ne suffit pas : des places libres qui l'etaient deja ne sont pas
     * une nouvelle.
     */
    public function testStandingFreeSlotsAreNotAnEvent(): void
    {
        $selector = $this->selector([], ['town' => 2, 'city' => 1, 'metropolis' => 0], '{"town":2,"city":1,"metropolis":0}');

        self::assertNull($selector->select());
    }

    /**
     * Une place **prise** ne declenche rien non plus. L'Appel annonce une
     * ouverture, jamais une fermeture.
     */
    public function testASlotBeingTakenSummonsNothing(): void
    {
        $selector = $this->selector([], ['town' => 0, 'city' => 1, 'metropolis' => 0], '{"town":1,"city":1,"metropolis":0}');

        self::assertNull($selector->select());
    }

    /**
     * **La premiere cloture pose le repere sans rien declencher.** Un monde neuf
     * a toutes ses places libres ; les annoncer comme une nouvelle serait un
     * contresens, et l'Appel sonnerait a la toute premiere maree.
     */
    public function testTheFirstCloseOnlySetsTheMarker(): void
    {
        $selector = $this->selector([], ['town' => 3, 'city' => 2, 'metropolis' => 1], null);

        self::assertNull($selector->select());
    }

    // =====================================================================
    // La preseance
    // =====================================================================

    /**
     * Quand les deux sont vraies, la Paleur passe devant : c'est la consequence
     * qui **enseigne**, et la faire ceder a une bonne nouvelle reviendrait a
     * dire au serveur que sa sur-extraction est sans suite.
     */
    public function testThePalenessOutranksTheCrueCall(): void
    {
        $selector = $this->selector($this->veins(8), ['town' => 2, 'city' => 1, 'metropolis' => 0], '{"town":1,"city":1,"metropolis":0}');

        self::assertSame(ConsequenceTide::Paleness, $selector->select());
    }

    public function testThePalenessComesFirstInThePrecedenceOrder(): void
    {
        self::assertLessThan(ConsequenceTide::CrueCall->precedence(), ConsequenceTide::Paleness->precedence());
    }

    // =====================================================================
    // Le repere
    // =====================================================================

    public function testRememberingTheSlotsWritesThemDown(): void
    {
        $parameter = (new Parameter())->setName(ConsequenceTideSelector::PARAM_FREE_SLOTS)->setValue('');

        $this->selector([], ['town' => 2, 'city' => 0, 'metropolis' => 0], null, $parameter)->rememberFreeSlots();

        self::assertSame('{"town":2,"city":0,"metropolis":0}', $parameter->getValue());
    }

    // =====================================================================
    // Fixtures
    // =====================================================================

    private function zone(string $slug): Zone
    {
        return (new Zone())->setSlug($slug)->setName(ucfirst($slug));
    }

    /**
     * @return list<ZoneVein>
     */
    private function veins(int $pale): array
    {
        $veins = [];
        for ($i = 0; $i < $pale; ++$i) {
            $veins[] = (new ZoneVein($this->zone('crete'), 'filon-' . $i, 10))->setPaleness(0.20);
        }

        return $veins;
    }

    /**
     * @param list<ZoneVein>    $veins
     * @param array<string,int> $freeSlots places libres par rang, telles que la Crue les voit maintenant
     */
    private function selector(array $veins, array $freeSlots, ?string $remembered, ?Parameter $parameter = null): ConsequenceTideSelector
    {
        $veinRepository = $this->createMock(ZoneVeinRepository::class);
        $veinRepository->method('findAll')->willReturn($veins);

        $crue = $this->createMock(CrueQuotaService::class);
        $crue->method('quotaFor')->willReturnCallback(
            static fn (SettlementRank $rank): ?int => \array_key_exists($rank->value, $freeSlots) ? $freeSlots[$rank->value] : null,
        );
        // Aucun occupant : le quota vaut alors exactement le nombre de places
        // libres, ce qui rend le scenario lisible sans construire de foyers.
        $crue->method('occupants')->willReturn([]);

        $settlementLoader = $this->createMock(SettlementDefinitionLoader::class);
        $settlementLoader->method('load')->willReturn(['paleness' => [
            'rise_per_pressure' => 0.08,
            'daily_recovery' => 0.04,
            'max' => 0.60,
            'visible_from' => 0.10,
            'dulls_purity_from' => 0.30,
        ]]);

        $loader = $this->createMock(TideDefinitionLoader::class);
        $loader->method('load')->willReturn(['paleness_threshold' => 6, 'tides' => []]);

        $parameter ??= (new Parameter())
            ->setName(ConsequenceTideSelector::PARAM_FREE_SLOTS)
            ->setValue($remembered ?? '');

        $parameterRepository = $this->createMock(EntityRepository::class);
        $parameterRepository->method('findOneBy')->willReturn($parameter);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($parameterRepository);

        return new ConsequenceTideSelector(
            $entityManager,
            $veinRepository,
            $crue,
            $settlementLoader,
            $loader,
        );
    }
}
