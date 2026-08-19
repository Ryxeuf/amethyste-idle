<?php

namespace App\Tests\Integration\Repertoire;

use App\Entity\App\Player;
use App\Entity\App\RepertoireReading;
use App\Entity\App\Zone;
use App\Enum\Element;
use App\GameEngine\Repertoire\RepertoireCatalog;
use App\GameEngine\Repertoire\RepertoireLedger;
use App\Repository\RepertoireReadingRepository;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Le versement contexte, les agregats et le plafond (REP-01).
 */
class RepertoireLedgerTest extends AbstractIntegrationTestCase
{
    private RepertoireLedger $ledger;
    private RepertoireReadingRepository $readings;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var RepertoireLedger $ledger */
        $ledger = self::getContainer()->get(RepertoireLedger::class);
        $this->ledger = $ledger;

        /** @var RepertoireReadingRepository $readings */
        $readings = self::getContainer()->get(RepertoireReadingRepository::class);
        $this->readings = $readings;
    }

    /**
     * Une lecture ouvre un decompte, la suivante l'incremente.
     *
     * *Une ligne n'est pas une lecture, c'est un baton.* Deux lectures du meme
     * contexte n'ecrivent donc qu'une ligne — sans quoi le Repertoire d'un
     * serveur d'un an serait un journal, avec ce que cela suppose de purge.
     */
    public function testTwoReadingsOfTheSameContextShareOneTally(): void
    {
        $player = $this->playerInZone($this->anyZone());

        self::assertTrue($this->ledger->record($player, Element::Fire, null, '2026-W10', '2026-03-02'));
        self::assertTrue($this->ledger->record($player, Element::Fire, null, '2026-W10', '2026-03-02'));

        $rows = $this->readings->findBy(['weekKey' => '2026-W10']);
        self::assertCount(1, $rows);
        self::assertSame(2, $rows[0]->getTally());
    }

    /**
     * **Une provenance inconnue reste inconnue**, et elle se regroupe avec les
     * autres inconnues plutot que d'ouvrir une ligne par lecture.
     *
     * C'est le point ou le plan proposait un repli sur la zone de lecture ; le
     * refuser garde trois axes distincts au lieu de deux axes et une copie.
     */
    public function testAnUnknownProvenanceGroupsWithTheOtherUnknowns(): void
    {
        $zone = $this->anyZone();
        $player = $this->playerInZone($zone);

        $this->ledger->record($player, Element::Water, null, '2026-W11', '2026-03-09');
        $this->ledger->record($player, Element::Water, $zone->getId(), '2026-W11', '2026-03-09');

        $rows = $this->readings->findBy(['weekKey' => '2026-W11']);
        self::assertCount(2, $rows, 'Une provenance connue et une inconnue sont le meme contexte.');

        $provenances = $this->readings->tallyByProvenance('2026-W11');
        self::assertSame([$zone->getSlug() => 1], $provenances, 'Les inconnues comptent comme une provenance.');
    }

    /**
     * Les trois agregats du canon se lisent, et ils ne disent pas la meme chose.
     */
    public function testTheThreeAggregatesReadIndependently(): void
    {
        $zone = $this->anyZone();
        $player = $this->playerInZone($zone);

        $this->ledger->record($player, Element::Fire, $zone->getId(), '2026-W12', '2026-03-16');
        $this->ledger->record($player, Element::Earth, null, '2026-W12', '2026-03-16');

        self::assertSame(
            ['earth' => 1, 'fire' => 1],
            $this->sorted($this->readings->tallyByElement('2026-W12')),
        );
        self::assertSame([$zone->getSlug() => 1], $this->readings->tallyByProvenance('2026-W12'));
        self::assertSame([$zone->getSlug() => 2], $this->readings->tallyByReadingZone('2026-W12'));
    }

    /**
     * **Le plafond arrete la contribution, jamais la lecture.**.
     *
     * Le service rend `false` : le geste du joueur, lui, se poursuit ailleurs —
     * reputation, Codex, accord. Ce qui se ferme est le souvenir du serveur.
     */
    public function testTheCapStopsContributingWithoutStoppingTheReading(): void
    {
        $player = $this->playerInZone($this->anyZone());
        $cap = self::getContainer()->get(RepertoireCatalog::class)->dailyReadingsPerPlayer();

        for ($i = 0; $i < $cap; ++$i) {
            self::assertTrue($this->ledger->record($player, Element::Fire, null, '2026-W13', '2026-03-23'));
        }

        self::assertFalse($this->ledger->record($player, Element::Fire, null, '2026-W13', '2026-03-23'));
        self::assertSame($cap, $this->readings->findBy(['weekKey' => '2026-W13'])[0]->getTally());
    }

    /**
     * Et le plafond est **journalier** : le lendemain, le compteur repart de
     * zero sans qu'aucune tache n'ait eu a le remettre a zero.
     */
    public function testTheCapResetsWithTheDayAndNeedsNoCron(): void
    {
        $player = $this->playerInZone($this->anyZone());
        $cap = self::getContainer()->get(RepertoireCatalog::class)->dailyReadingsPerPlayer();

        for ($i = 0; $i < $cap; ++$i) {
            $this->ledger->record($player, Element::Fire, null, '2026-W14', '2026-03-30');
        }

        self::assertFalse($this->ledger->record($player, Element::Fire, null, '2026-W14', '2026-03-30'));
        self::assertTrue($this->ledger->record($player, Element::Fire, null, '2026-W14', '2026-03-31'));
    }

    /**
     * **Aucune colonne ne nomme un joueur.** C'est la doctrine du jalon, et
     * elle se verifie sur la table plutot que dans un commentaire : *le
     * Repertoire est la memoire du serveur, pas un journal de joueurs*.
     */
    public function testTheRepertoireNeverNamesAPlayer(): void
    {
        $metadata = $this->em->getClassMetadata(RepertoireReading::class);

        $named = array_values(array_filter(
            array_merge($metadata->getColumnNames(), $metadata->getAssociationNames()),
            static fn (string $name): bool => str_contains(mb_strtolower($name), 'player'),
        ));

        self::assertSame([], $named, sprintf(
            'Le Repertoire nomme un joueur : %s. Il est la memoire du serveur, pas un journal de joueurs.',
            implode(', ', $named),
        ));
    }

    /**
     * **Le crochet a enfin un abonne.**.
     *
     * `MateriaReadEvent` a ete declare par FAC-04b sans abonne, avec la
     * promesse que REP s'y brancherait. Un evenement dispatche que personne
     * n'ecoute est **silencieux par nature** : sans ce test, la lecture
     * continuerait de partir dans le vide et rien ne le dirait.
     */
    public function testTheReadingHookHasItsSubscriber(): void
    {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');

        $listeners = array_map(
            static fn (array|callable $listener): string => \is_array($listener) ? \get_class($listener[0]) : \get_class($listener),
            $dispatcher->getListeners(\App\Event\Game\MateriaReadEvent::NAME),
        );

        self::assertContains(
            \App\EventListener\RepertoireReadingListener::class,
            $listeners,
            'La lecture repart dans le vide : le Repertoire n\'ecoute plus.',
        );
    }

    private function anyZone(): Zone
    {
        $zone = $this->em->getRepository(Zone::class)->findOneBy(['slug' => 'village-de-lumiere']);
        self::assertNotNull($zone);

        return $zone;
    }

    private function playerInZone(Zone $zone): Player
    {
        $player = $this->em->getRepository(Player::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($player, 'Aucun joueur de fixture : le contrat ne mesure rien.');
        $player->setCurrentZone($zone);

        return $player;
    }

    /**
     * @param array<string, int> $tally
     *
     * @return array<string, int>
     */
    private function sorted(array $tally): array
    {
        ksort($tally);

        return $tally;
    }
}
