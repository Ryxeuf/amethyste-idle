<?php

namespace App\Tests\Integration\GameEngine\Zone;

use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\Entity\Game\Quest;
use App\GameEngine\Zone\WorldEntityZoneBackfiller;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Une entite de monde est **la ou le jeu l'envoie chercher** (pivot PBBG, ZON-04).
 *
 * **La panne que ces lois ferment.** L'ecran de zone liste ses habitants
 * strictement par `pnj.zone_id` : une entite mal rattachee n'est pas mal placee,
 * elle **n'existe plus**. Aucune erreur n'est levee, aucune page ne casse — le
 * joueur ouvre la zone, ne voit pas le personnage, et conclut qu'il s'est trompe.
 *
 * C'est ce qui est arrive a la maitresse d'armes du Fanal, premiere porte de la
 * chaine de l'acte I : la carte « Le Fanal » porte **deux** zones (le village et
 * son Quartier des Jardins), et la resolution carte -> zone n'en nommait aucune.
 *
 * Les lois ci-dessous portent sur la **base reelle**, pas sur le texte des
 * fixtures : `FanalPopulationTest` verifiait deja qu'un maitre d'armes est
 * **ecrit**, ce qui etait vrai pendant toute la duree de la panne.
 */
class WorldEntityZoneAttachmentTest extends AbstractIntegrationTestCase
{
    /**
     * Aucune entite orpheline, aucune entite egaree.
     *
     * La loi transverse : toute entite dont la carte a une zone est rattachee a
     * la **bonne** zone de cette carte. `off_graph` (carte hors graphe : donjon
     * instancie, carte de test) reste legitime et n'est pas compte.
     */
    public function testNoWorldEntityIsOrphanOrMisplaced(): void
    {
        /** @var WorldEntityZoneBackfiller $backfiller */
        $backfiller = self::getContainer()->get(WorldEntityZoneBackfiller::class);

        $faults = [];
        foreach ($backfiller->stats() as $table => $stats) {
            if ($stats['orphans'] > 0) {
                $faults[] = sprintf('%s : %d orpheline(s)', $table, $stats['orphans']);
            }
            if ($stats['misplaced'] > 0) {
                $faults[] = sprintf('%s : %d egaree(s) dans une autre zone de sa carte', $table, $stats['misplaced']);
            }
        }

        self::assertSame([], $faults, sprintf(
            "Des entites de monde sont hors de leur zone : %s.\n"
            . 'Reparer avec `app:zone:audit --fix` ; verifier au passage que la carte partagee designe bien sa zone principale (`source_map_primary`).',
            implode(' ; ', $faults),
        ));
    }

    /**
     * Une carte **partagee** designe une zone et une seule.
     *
     * Le partage est permis — le Fanal et son lotissement le font — mais il doit
     * etre tranche : sans zone principale, `findEnabledBySourceMap()` rend la
     * premiere ligne venue, c'est-a-dire un resultat qui change des qu'une zone
     * est mise a jour (donc a chaque import).
     *
     * **Une carte seule sur son nom n'a rien a declarer**, et ce n'est pas une
     * tolerance : c'est la meme regle que celle du loader. Un drapeau qu'on pose
     * par obligation cesse de vouloir dire quelque chose, et le repli par `id`
     * n'a de toute facon rien a departager quand il n'y a qu'une candidate. Deux
     * revendications restent refusees partout, partagee ou non.
     */
    public function testEverySharedSourceMapDesignatesExactlyOnePrimaryZone(): void
    {
        /** @var list<Zone> $zones */
        $zones = $this->em->getRepository(Zone::class)->findBy(['enabled' => true]);

        /** @var array<int, list<Zone>> $byMap */
        $byMap = [];
        foreach ($zones as $zone) {
            $map = $zone->getSourceMap();
            if (null !== $map) {
                $byMap[$map->getId()][] = $zone;
            }
        }

        foreach ($byMap as $mapId => $sharing) {
            $primaries = array_filter($sharing, static fn (Zone $z): bool => $z->isSourceMapPrimary());
            $expected = \count($sharing) > 1 ? 1 : 0;

            self::assertLessThanOrEqual(1, \count($primaries), sprintf(
                'La carte #%d est revendiquee par %d zones a la fois (%s) : la designation ne designe plus rien.',
                $mapId,
                \count($primaries),
                implode(', ', array_map(static fn (Zone $z): string => $z->getSlug(), $primaries)),
            ));

            if (1 === $expected) {
                self::assertCount(1, $primaries, sprintf(
                    'La carte #%d est partagee par %d zones (%s) et n\'en designe aucune comme principale : '
                    . 'les entites qui ne connaissent que leur carte atterriront dans l\'une ou l\'autre.',
                    $mapId,
                    \count($sharing),
                    implode(', ', array_map(static fn (Zone $z): string => $z->getSlug(), $sharing)),
                ));
            }
        }
    }

    /**
     * Le premier donneur de l'acte I est joignable depuis le Fanal.
     *
     * La loi la plus concrete du lot : c'est l'ecran ou le bandeau de tutoriel
     * envoie un joueur au tout premier pas, et le seul chemin vers un habitant
     * depuis ZON-27.
     */
    public function testTheWeaponMistressIsListedInTheBeacon(): void
    {
        $pnj = $this->em->getRepository(Pnj::class)->findOneBy(['slug' => 'fanal-maitresse-armes-ysold']);
        self::assertNotNull($pnj, 'La maitresse d\'armes du Fanal n\'existe pas en base : la chaine de l\'acte I n\'a pas de premiere porte.');

        $zone = $pnj->getZone();
        self::assertNotNull($zone, 'La maitresse d\'armes n\'a aucune zone : l\'ecran de zone listant par zone, elle est injoignable.');
        self::assertSame('village-de-lumiere', $zone->getSlug(), sprintf(
            'La maitresse d\'armes est dans "%s" et non au Fanal — l\'acte I envoie le joueur au mauvais endroit.',
            $zone->getSlug(),
        ));
    }

    /**
     * Tout PNJ designe par un objectif « parler a » est joignable.
     *
     * Une quete qui nomme un interlocuteur sans zone est une quete qu'aucun
     * joueur ne peut terminer — et elle bloque toute la chaine derriere elle,
     * les arcs etant strictement sequentiels.
     */
    public function testEveryTalkToObjectivePointsAtAReachablePnj(): void
    {
        /** @var list<Quest> $quests */
        $quests = $this->em->getRepository(Quest::class)->findAll();
        $pnjRepository = $this->em->getRepository(Pnj::class);

        $unreachable = [];
        foreach ($quests as $quest) {
            foreach ($quest->getRequirements()['talk_to'] ?? [] as $entry) {
                $pnjId = (int) ($entry['pnj_id'] ?? 0);

                // 0 est la valeur d'attente des fixtures de quete, recalee par
                // `QuestChainFixtures` : la laisser telle quelle est un autre
                // defaut, mais il a son propre test (`QuestReferenceTest`).
                if (0 === $pnjId) {
                    continue;
                }

                $pnj = $pnjRepository->find($pnjId);
                if (!$pnj instanceof Pnj) {
                    $unreachable[] = sprintf('%s -> PNJ #%d inexistant', $quest->getName(), $pnjId);
                    continue;
                }

                if (null === $pnj->getZone()) {
                    $unreachable[] = sprintf('%s -> %s (aucune zone)', $quest->getName(), $pnj->getName());
                }
            }
        }

        self::assertSame([], $unreachable, sprintf(
            "Ces objectifs « parler a » designent un PNJ qu'aucun ecran n'atteint : %s.",
            implode(', ', $unreachable),
        ));
    }
}
