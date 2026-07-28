<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\Enum\SettlementType;
use App\Event\Zone\SettlementRankChangedEvent;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Ce qui n'est plus frequente s'amincit — et le type se decide tout seul
 * (FOY-03).
 *
 * Chaque indice perd 2 % par jour, demi-vie d'environ 35 jours : un foyer
 * delaisse une maree entiere descend visiblement, un foyer delaisse une semaine
 * ne s'effondre pas. La consequence structurante est que le stock d'equilibre
 * vaut cinquante fois le flux quotidien : **le rang d'un foyer est, a terme, une
 * photographie de sa frequentation reelle** (BALANCE § 23.2).
 *
 * Le tick est **idempotent a la journee**. Le rejouer le meme jour ne retire
 * rien de plus, et l'oublier trois jours n'offre pas trois jours de repit : la
 * decroissance compose sur les jours ecoules. Le calendrier du jeu n'est
 * consomme par aucun worker a ce jour (cf. `DefaultScheduleProvider`) — raison
 * de plus pour que ce service ne suppose jamais qu'il a tourne hier.
 */
class SettlementTickService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementDefinitionLoader $loader,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return array{processed: int, decayed: int, promoted: int, demoted: int, typed: int, skipped: int}
     */
    public function tick(?\DateTimeImmutable $now = null, bool $force = false): array
    {
        $now ??= new \DateTimeImmutable();
        $definition = $this->loader->load();

        $report = ['processed' => 0, 'decayed' => 0, 'promoted' => 0, 'demoted' => 0, 'typed' => 0, 'skipped' => 0];

        foreach ($this->settlementRepository->findAll() as $settlement) {
            $days = $this->daysSinceLastTick($settlement, $now);

            if ($days < 1 && !$force) {
                ++$report['skipped'];
                continue;
            }

            $report['decayed'] += $this->applyDecay($settlement, $definition['decay_rate'], $days);
            $this->advanceDecayAnchor($settlement, $now, $days);

            $change = $this->applyRank($settlement, $definition['ranks'], $now);
            if ($change === 1) {
                ++$report['promoted'];
            } elseif ($change === -1) {
                ++$report['demoted'];
            }

            if ($this->applyType($settlement, $definition, $now)) {
                ++$report['typed'];
            }

            ++$report['processed'];
        }

        $this->entityManager->flush();

        return $report;
    }

    /**
     * Jours **entiers** ecoules depuis la derniere decroissance.
     *
     * Un foyer jamais tique n'est pas rattrape sur toute son anciennete : on
     * pose l'ancre et on attend demain. Rattraper ferait fondre le seed du monde
     * livre a la premiere execution, ce qui reviendrait a retro-gater par la
     * bande exactement ce que la decision A ecarte.
     */
    private function daysSinceLastTick(Settlement $settlement, \DateTimeImmutable $now): int
    {
        $anchor = $settlement->getDecayedAt();
        if ($anchor === null) {
            $settlement->setDecayedAt($now);

            return 0;
        }

        $seconds = $now->getTimestamp() - $anchor->getTimestamp();

        return $seconds > 0 ? intdiv($seconds, 86400) : 0;
    }

    private function applyDecay(Settlement $settlement, float $rate, int $days): int
    {
        $lost = 0;

        foreach (SettlementIndex::cases() as $index) {
            $before = $settlement->getSediment($index);
            $after = SettlementRankCalculator::decay($before, $rate, $days);
            if ($after !== $before) {
                $settlement->setSediment($index, $after);
                $lost += $before - $after;
            }
        }

        return $lost;
    }

    /**
     * L'ancre avance de jours **entiers**, pas jusqu'a maintenant : sinon un
     * tick joue a 23 h 59 puis a 00 h 01 mangerait le reste de la journee, et le
     * taux reel derivierait au gre de l'heure d'execution.
     */
    private function advanceDecayAnchor(Settlement $settlement, \DateTimeImmutable $now, int $days): void
    {
        $anchor = $settlement->getDecayedAt();
        if ($anchor === null) {
            $settlement->setDecayedAt($now);

            return;
        }

        if ($days > 0) {
            $settlement->setDecayedAt($anchor->modify(sprintf('+%d days', $days)));
        }
    }

    /**
     * @param array<string, int> $thresholds
     *
     * @return int 1 montee, -1 descente, 0 inchange
     */
    private function applyRank(Settlement $settlement, array $thresholds, \DateTimeImmutable $now): int
    {
        $before = $settlement->getRank();
        $after = SettlementRankCalculator::rankFor($settlement->getTotalSediment(), $thresholds);

        if ($after === $before) {
            return 0;
        }

        $settlement->setRank($after);
        $settlement->setRankedAt($now);

        $this->eventDispatcher->dispatch(
            new SettlementRankChangedEvent($settlement, $before, $after),
            SettlementRankChangedEvent::NAME,
        );

        return $after->level() > $before->level() ? 1 : -1;
    }

    /**
     * L'hysteresis du type (BALANCE § 23.4).
     *
     * Un pretendant doit depasser le second d'une marge **et tenir une maree
     * entiere**. C'est la duree qui fait l'identite : sans elle, le type
     * changerait au gre des semaines et une ville ne serait jamais rien de
     * precis.
     *
     * Le type ne se perd pas en perdant l'avance — il se perd en **la cedant a
     * un autre**, dans les memes conditions. Une seule exception : sous le
     * Hameau, il n'y a pas d'identite du tout. BALANCE § 23.4 pose les deux
     * regles cote a cote sans les arbitrer ; la seconde l'emporte ici, parce
     * qu'un Campement qui se souviendrait d'avoir ete un Comptoir afficherait
     * une identite que plus rien ne soutient.
     *
     * @param array{dominance_margin: float, sustain_days: int, minimum_type_rank: SettlementRank} $definition
     */
    private function applyType(Settlement $settlement, array $definition, \DateTimeImmutable $now): bool
    {
        if (!$settlement->getRank()->isAtLeast($definition['minimum_type_rank'])) {
            $had = $settlement->getType() !== null;
            $settlement->setType(null);
            $settlement->setDominantCandidate(null);
            $settlement->setDominantSince(null);

            return $had;
        }

        $challenger = SettlementRankCalculator::challenger(
            $settlement->getAllSediment(),
            $definition['dominance_margin'],
        );

        if ($challenger !== $settlement->getDominantCandidate()) {
            $settlement->setDominantCandidate($challenger);
            $settlement->setDominantSince($challenger === null ? null : $now);

            return false;
        }

        $since = $settlement->getDominantSince();
        if ($challenger === null || $since === null) {
            return false;
        }

        if ($since > $now->modify(sprintf('-%d days', $definition['sustain_days']))) {
            return false;
        }

        $type = SettlementType::fromIndex($challenger);
        if ($settlement->getType() === $type) {
            return false;
        }

        $settlement->setType($type);

        return true;
    }
}
