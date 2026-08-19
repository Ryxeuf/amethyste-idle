<?php

namespace App\Tests\Unit\GameEngine\Season;

use App\Entity\App\InfluenceSeason;
use App\Enum\ConsequenceTide;
use App\Enum\TideVoice;
use App\GameEngine\Season\ConsequenceTideSelector;
use App\GameEngine\Season\RotationTideSelector;
use App\GameEngine\Season\TideDefinitionLoader;
use App\GameEngine\Season\TideSelector;
use PHPUnit\Framework\TestCase;

/**
 * L'ordre des trois voix (NAR-15).
 *
 * GAME_SEASONS § 0 : *conséquence déclenchée > colonne vertébrale datée >
 * rotation*. Cet ordre n'existe qu'ici — c'est la reponse a la mise en garde du
 * plan (« ne pas creer un second selecteur concurrent ») : chaque voix garde son
 * service, et un seul endroit decide laquelle parle.
 */
class TideSelectorTest extends TestCase
{
    private function selector(?ConsequenceTide $consequence, ?string $rotation = 'the_choir'): TideSelector
    {
        $consequences = $this->createMock(ConsequenceTideSelector::class);
        $consequences->method('select')->willReturn($consequence);

        $rotationSelector = $this->createMock(RotationTideSelector::class);
        $rotationSelector->method('select')->willReturn($rotation);

        return new TideSelector($consequences, $rotationSelector, new TideDefinitionLoader(\dirname(__DIR__, 4)));
    }

    private function season(int $number, ?string $theme = null): InfluenceSeason
    {
        $season = new InfluenceSeason();
        $season->setName(sprintf('Marée %d', $number));
        $season->setSlug(sprintf('maree-%d', $number));
        $season->setSeasonNumber($number);
        $season->setStartsAt(new \DateTime('2026-09-01'));
        $season->setEndsAt(new \DateTime('2026-09-29'));

        if ($theme !== null) {
            $season->setTheme($theme);
        }

        return $season;
    }

    /**
     * **Un créneau qui porte déjà son thème n'est bousculé par personne** —
     * c'est la règle que FOY-15 a posée, et elle passe avant les trois voix.
     */
    public function testATideAlreadyPosedIsNeverPreempted(): void
    {
        $choice = $this->selector(ConsequenceTide::Paleness)->selectFor($this->season(3, 'La Marée d\'Ambre'));

        self::assertSame(TideVoice::None, $choice->voice);
        self::assertFalse($choice->isComposable());
    }

    /**
     * Voix 1 — sur un créneau libre, la conséquence passe devant la rotation.
     */
    public function testAConsequencePreemptsAFreeSlot(): void
    {
        $choice = $this->selector(ConsequenceTide::Paleness)->selectFor($this->season(3));

        self::assertSame(TideVoice::Consequence, $choice->voice);
        self::assertSame('paleness', $choice->tide);
        self::assertTrue($choice->isComposable());
    }

    /**
     * **Voix 2 — et c'est tout le jalon.**.
     *
     * Sans la colonne vertébrale, une conséquence — ou une rotation — prendrait
     * le créneau M2, et « La Première Pierre » n'arriverait jamais : aucun code
     * ne connaissait ces créneaux. GAME_SEASONS § 3 est explicite, les
     * conséquences préemptent *le prochain créneau de rotation* ; un créneau que
     * la partition a réservé n'en est pas un.
     */
    public function testACanonSlotIsNotPreemptedByAConsequence(): void
    {
        $choice = $this->selector(ConsequenceTide::Paleness)->selectFor($this->season(2));

        self::assertSame(TideVoice::Canon, $choice->voice);
        self::assertSame('La Première Pierre', $choice->theme);
        self::assertSame('NAR-16', $choice->milestone);
    }

    public function testACanonSlotIsNotTakenByTheRotationEither(): void
    {
        $choice = $this->selector(null)->selectFor($this->season(4));

        self::assertSame(TideVoice::Canon, $choice->voice);
        self::assertSame('Le Procès de la Fonderie', $choice->theme);
    }

    /**
     * **La colonne réserve, elle n'improvise pas.**.
     *
     * À ce jalon aucune marée canon n'a d'arc : leur contenu arrive avec
     * NAR-16 à NAR-19. Le créneau reste donc **vide plutôt que pris** — composer
     * quoi que ce soit sous le nom que le canon a promis livrerait un arc que
     * personne n'a écrit.
     */
    public function testAReservedSlotComposesNothing(): void
    {
        $choice = $this->selector(null)->selectFor($this->season(8));

        self::assertFalse($choice->isComposable());
        self::assertNull($choice->tide);
    }

    /**
     * Voix 3 — un créneau libre que rien ne réclame joue un gabarit.
     */
    public function testAFreeSlotFallsToTheRotation(): void
    {
        $choice = $this->selector(null)->selectFor($this->season(3));

        self::assertSame(TideVoice::Rotation, $choice->voice);
        self::assertSame('the_choir', $choice->tide);
        self::assertTrue($choice->isComposable());
    }

    /**
     * Et si la rotation ne rend rien, le créneau reste vide plutôt que de faire
     * échouer le tick : *la partition n'est pas ce qui doit casser le
     * calendrier*.
     */
    public function testAnEmptyRotationLeavesTheSlotEmpty(): void
    {
        $choice = $this->selector(null, null)->selectFor($this->season(3));

        self::assertSame(TideVoice::None, $choice->voice);
        self::assertFalse($choice->isComposable());
    }
}
