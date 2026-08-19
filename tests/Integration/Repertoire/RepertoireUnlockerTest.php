<?php

namespace App\Tests\Integration\Repertoire;

use App\Entity\App\Player;
use App\Entity\App\RepertoireGesture;
use App\Entity\App\Zone;
use App\Entity\Game\CodexEntry;
use App\Enum\Element;
use App\GameEngine\Repertoire\RepertoireLedger;
use App\GameEngine\Repertoire\RepertoireUnlocker;
use App\Repository\RepertoireGestureRepository;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Le seuil, la dominante et l'idempotence (REP-03).
 */
class RepertoireUnlockerTest extends AbstractIntegrationTestCase
{
    private RepertoireUnlocker $unlocker;
    private RepertoireLedger $ledger;
    private RepertoireGestureRepository $gestures;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var RepertoireUnlocker $unlocker */
        $unlocker = self::getContainer()->get(RepertoireUnlocker::class);
        $this->unlocker = $unlocker;

        /** @var RepertoireLedger $ledger */
        $ledger = self::getContainer()->get(RepertoireLedger::class);
        $this->ledger = $ledger;

        /** @var RepertoireGestureRepository $gestures */
        $gestures = self::getContainer()->get(RepertoireGestureRepository::class);
        $this->gestures = $gestures;
    }

    /**
     * **Le seuil ne descend jamais sous son plancher.**.
     *
     * Un monde qui demarre a une population effective quasi nulle ; sans
     * plancher, son seuil serait quasi nul et il retrouverait tout le bassin sur
     * quelques lectures — l'inverse exact de ce que l'indexation cherche.
     */
    public function testTheThresholdNeverFallsBelowItsFloor(): void
    {
        self::assertGreaterThanOrEqual(40, $this->unlocker->thresholdFor(1));
    }

    /**
     * **Le n-ieme geste coute n crans.** L'horizon s'allonge a mesure que le
     * monde se souvient : c'est la forme d'un savoir, pas d'une liste de
     * courses.
     */
    public function testEachGestureCostsMoreThanTheOneBefore(): void
    {
        $first = $this->unlocker->thresholdFor(1);

        self::assertSame($first * 2, $this->unlocker->thresholdFor(2));
        self::assertSame($first * 5, $this->unlocker->thresholdFor(5));
    }

    /**
     * Sous le seuil, rien n'est retrouve — et c'est le cas normal d'un monde
     * qui vient de naître.
     */
    public function testNothingIsRecoveredBelowTheThreshold(): void
    {
        self::assertSame([], $this->unlocker->unlockDue());
        self::assertSame(0, $this->gestures->recoveredCount());
    }

    /**
     * **La dominante decide, et l'element est la borne.**.
     *
     * Un monde qui n'a lu que du feu ne retrouve pas « le geste d'eau le mieux
     * classe » : il ne retrouve pas de geste d'eau du tout. C'est ce qui fait
     * que *ce qu'un serveur lit est ce dont il se souvient*.
     */
    public function testTheDominantElementBoundsTheDraw(): void
    {
        $this->readMany(Element::Fire, 3);

        $key = $this->unlocker->nextGesture();
        self::assertNotNull($key);
        self::assertSame('frappe-meteorique', $key, 'Le seul geste de feu du bassin.');
    }

    /**
     * La provenance **departage**, elle ne decide pas.
     *
     * Deux gestes de terre existent : l'un tague les Mines, l'autre les Vallons.
     * Un monde qui lit de la terre venue des Mines retrouve celui des Mines —
     * et il aurait retrouve l'autre s'il avait travaille ailleurs.
     */
    public function testTheDominantProvenanceBreaksTheTie(): void
    {
        $mines = $this->zone('mines-profondes');
        $this->readMany(Element::Earth, 3, $mines->getId());

        self::assertSame('lance-de-cristal', $this->unlocker->nextGesture());
    }

    /**
     * **Un geste a condition rare n'est pas eligible tant que sa condition
     * manque**, et il ne bloque pas la file : il est simplement ignore.
     */
    public function testARareGestureIsSkippedUntilItsConditionHolds(): void
    {
        // La Benediction de l'ocean exige que les huit elements aient ete lus ;
        // ce monde n'en a lu qu'un.
        $this->readMany(Element::Water, 3);

        $key = $this->unlocker->nextGesture();
        self::assertNotNull($key);
        self::assertNotSame('benediction-de-l-ocean', $key);
    }

    /**
     * **Idempotence.** Un geste retrouve ne se retrouve pas deux fois — et il ne
     * se re-perd jamais non plus, ce que la table dit par omission : il n'y a
     * aucune colonne pour le reprendre.
     */
    public function testAGestureIsNeverRecoveredTwice(): void
    {
        $this->em->persist(new RepertoireGesture('frappe-meteorique', 1));
        $this->em->flush();

        $this->readMany(Element::Fire, 3);

        self::assertNotSame('frappe-meteorique', $this->unlocker->nextGesture());
    }

    /**
     * Un monde qui n'a rien lu n'a pas de dominante — et le bassin lui reste
     * entier plutot que de se fermer.
     */
    public function testAWorldThatHasReadNothingStillHasCandidates(): void
    {
        self::assertNotNull($this->unlocker->nextGesture());
    }

    /**
     * **La boucle entiere**, celle qu'aucun des tests ci-dessus ne prouve.
     *
     * Un monde lit assez pour franchir son seuil, retrouve un geste, et le
     * journal de monde l'annonce. C'est le jalon ; le reste en sont les pieces.
     *
     * Le second appel ne retrouve rien de plus, et c'est la garantie qui compte
     * pour une commande de calendrier : *rien n'est rejoue* — une relance ne
     * doit pas vider le bassin.
     */
    public function testCrossingTheThresholdRecoversAGestureAndAnnouncesIt(): void
    {
        $this->readEnoughToCross(Element::Fire);

        $recovered = $this->unlocker->unlockDue();

        self::assertSame(['frappe-meteorique'], $recovered);
        self::assertSame(1, $this->gestures->recoveredCount());

        $fact = $this->em->getRepository(CodexEntry::class)->findOneBy(['slug' => 'repertoire-frappe-meteorique']);
        self::assertNotNull($fact, 'Le geste retrouve n\'a pas ete annonce au journal de monde.');
        self::assertSame(CodexEntry::CATEGORY_WORLD_FACT, $fact->getCategory());

        // Relance : rien de plus, et rien de perdu.
        self::assertSame([], $this->unlocker->unlockDue());
        self::assertSame(1, $this->gestures->recoveredCount());
    }

    /**
     * Assez de lectures pour franchir le premier seuil, en respectant le
     * plafond journalier — c'est-a-dire en etalant sur autant de jours qu'il
     * faut. Le plafond de REP-01 est une borne de mesure, pas de jeu : il ne
     * refuse pas la lecture, il refuse la contribution, et le test doit donc
     * changer de jour pour continuer a nourrir le Repertoire.
     */
    private function readEnoughToCross(Element $element): void
    {
        $player = $this->em->getRepository(Player::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($player);
        $player->setCurrentZone($this->zone('village-de-lumiere'));

        $needed = $this->unlocker->thresholdFor(1);
        $done = 0;
        $day = 1;

        while ($done < $needed) {
            $dayKey = sprintf('2026-06-%02d', $day);
            for ($i = 0; $i < 5 && $done < $needed; ++$i) {
                if ($this->ledger->record($player, $element, null, '2026-W23', $dayKey)) {
                    ++$done;
                }
            }
            ++$day;
            self::assertLessThan(200, $day, 'Le plafond journalier ne laisse plus passer aucune lecture.');
        }

        self::assertGreaterThanOrEqual($needed, $this->unlocker->totalReadings());
    }

    private function zone(string $slug): Zone
    {
        $zone = $this->em->getRepository(Zone::class)->findOneBy(['slug' => $slug]);
        self::assertNotNull($zone);

        return $zone;
    }

    private function readMany(Element $element, int $count, ?int $provenanceZoneId = null): void
    {
        $player = $this->em->getRepository(Player::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($player);
        $player->setCurrentZone($this->zone('village-de-lumiere'));

        for ($i = 0; $i < $count; ++$i) {
            $this->ledger->record($player, $element, $provenanceZoneId, '2026-W20', '2026-05-1' . $i);
        }
    }
}
