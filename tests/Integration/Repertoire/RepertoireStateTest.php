<?php

namespace App\Tests\Integration\Repertoire;

use App\Entity\App\Player;
use App\Entity\App\RepertoireGesture;
use App\Entity\App\Zone;
use App\Enum\Element;
use App\GameEngine\Repertoire\RepertoireLedger;
use App\GameEngine\Repertoire\RepertoireState;
use App\GameEngine\Repertoire\RepertoireUnlocker;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * L'etat du Repertoire, et ce qu'il ne dit pas (REP-05).
 */
class RepertoireStateTest extends AbstractIntegrationTestCase
{
    private RepertoireState $state;
    private RepertoireLedger $ledger;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var RepertoireState $state */
        $state = self::getContainer()->get(RepertoireState::class);
        $this->state = $state;

        /** @var RepertoireLedger $ledger */
        $ledger = self::getContainer()->get(RepertoireLedger::class);
        $this->ledger = $ledger;
    }

    /**
     * Un monde vierge se lit sans mentir : rien de lu, rien de retrouve, et le
     * bassin entier devant lui.
     */
    public function testABlankWorldReadsAsBlank(): void
    {
        $snapshot = $this->state->snapshot();

        self::assertSame(0, $snapshot['readings']);
        self::assertSame(1, $snapshot['rank']);
        self::assertNull($snapshot['dominant_element']);
        self::assertSame([], $snapshot['recovered']);
        self::assertGreaterThan(0, $snapshot['remaining']);
    }

    /**
     * **La non-revelation, verifiee sur les donnees et pas sur une intention.**.
     *
     * Le prochain geste existe — `RepertoireUnlocker` sait le calculer — et il
     * ne doit apparaitre **nulle part** dans ce que les ecrans recoivent. Le
     * test cherche sa cle dans toutes les valeurs de l'instantane, y compris
     * les imbriquees : *on sait qu'on approche, pas de quoi*.
     */
    public function testTheNextGestureNeverLeaksIntoTheSnapshot(): void
    {
        $this->read(Element::Fire, 3);

        /** @var RepertoireUnlocker $unlocker */
        $unlocker = self::getContainer()->get(RepertoireUnlocker::class);
        $next = $unlocker->nextGesture();

        self::assertNotNull($next, 'Aucun geste a venir : le test ne mesure rien.');

        $flat = [];
        $snapshot = $this->state->snapshot();
        array_walk_recursive($snapshot, static function (mixed $value) use (&$flat): void {
            if (\is_string($value)) {
                $flat[] = $value;
            }
        });

        self::assertNotContains($next, $flat, sprintf('Le prochain geste « %s » fuit dans l\'etat.', $next));
    }

    /**
     * Ce qui est retrouve se lit, dans l'ordre de decouverte, avec sa phrase.
     */
    public function testRecoveredGesturesReadInDiscoveryOrder(): void
    {
        $this->em->persist(new RepertoireGesture('frappe-meteorique', 1));
        $this->em->persist(new RepertoireGesture('maelstrom', 2));
        $this->em->flush();

        $snapshot = $this->state->snapshot();

        self::assertSame(['frappe-meteorique', 'maelstrom'], array_column($snapshot['recovered'], 'key'));
        self::assertSame([1, 2], array_column($snapshot['recovered'], 'rank'));
        self::assertNotSame('', $snapshot['recovered'][0]['revelation']);

        // Le rang suivant avance avec eux, et le seuil avec lui.
        self::assertSame(3, $snapshot['rank']);
    }

    /**
     * **La progression est bornee a 100.**.
     *
     * Au-dela du seuil, le geste est **du** et attend seulement que le
     * calendrier passe. Afficher 140 % ferait croire a un retard, quand c'est
     * une avance.
     */
    public function testProgressNeverExceedsAHundred(): void
    {
        $this->read(Element::Fire, 3);

        $snapshot = $this->state->snapshot();

        self::assertGreaterThanOrEqual(0, $snapshot['progress']);
        self::assertLessThanOrEqual(100, $snapshot['progress']);
    }

    /**
     * **Une seule source.** L'etat rend exactement ce que les services de
     * REP-01 et REP-03 calculent — il n'a pas sa propre idee de la dominante ni
     * du seuil, et c'est ce qui empeche deux ecrans d'en montrer deux
     * differentes.
     */
    public function testTheStateEchoesTheServicesRatherThanRecomputing(): void
    {
        $this->read(Element::Water, 4);

        /** @var RepertoireUnlocker $unlocker */
        $unlocker = self::getContainer()->get(RepertoireUnlocker::class);

        $snapshot = $this->state->snapshot();

        self::assertSame($unlocker->totalReadings(), $snapshot['readings']);
        self::assertSame($unlocker->thresholdFor(1), $snapshot['threshold']);
        self::assertSame('water', $snapshot['dominant_element']);
    }

    private function read(Element $element, int $count): void
    {
        $player = $this->em->getRepository(Player::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($player);

        $zone = $this->em->getRepository(Zone::class)->findOneBy(['slug' => 'village-de-lumiere']);
        self::assertNotNull($zone);
        $player->setCurrentZone($zone);

        for ($i = 0; $i < $count; ++$i) {
            $this->ledger->record($player, $element, null, '2026-W30', '2026-07-2' . $i);
        }
    }
}
