<?php

namespace App\Repository;

use App\Entity\App\RepertoireReading;
use App\Entity\App\Zone;
use App\Enum\Element;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RepertoireReading>
 */
class RepertoireReadingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RepertoireReading::class);
    }

    /**
     * Le baton exact de ce contexte, s'il existe deja.
     *
     * La recherche porte sur **les cinq colonnes** de la contrainte unique, y
     * compris celles qui peuvent etre nulles : `findOneBy` traduit `null` en
     * `IS NULL`, donc une provenance inconnue trouve bien la ligne des
     * provenances inconnues plutot que d'en creer une a chaque lecture.
     */
    public function findContext(string $weekKey, Element $element, ?Zone $provenance, Zone $readingZone, ?string $settlementRank): ?RepertoireReading
    {
        return $this->findOneBy([
            'weekKey' => $weekKey,
            'element' => $element->value,
            'provenanceZone' => $provenance,
            'readingZone' => $readingZone,
            'settlementRank' => $settlementRank,
        ]);
    }

    /**
     * Le decompte par element, toutes semaines confondues ou sur une semaine.
     *
     * C'est l'un des trois agregats sur lesquels REP-03 lira ses dominantes.
     * Il se calcule en base plutot qu'en memoire : le Repertoire d'un serveur
     * d'un an compte des milliers de lignes, et *une dominante qui charge tout
     * pour additionner ne tiendra pas la premiere annee*.
     *
     * @return array<string, int> valeur d'`Element` => total
     */
    public function tallyByElement(?string $weekKey = null): array
    {
        return $this->tallyBy('r.element', $weekKey);
    }

    /**
     * @return array<string, int> slug de zone => total (les inconnues exclues)
     */
    public function tallyByProvenance(?string $weekKey = null): array
    {
        return $this->tallyByZone('provenanceZone', $weekKey);
    }

    /**
     * @return array<string, int> slug de zone => total
     */
    public function tallyByReadingZone(?string $weekKey = null): array
    {
        return $this->tallyByZone('readingZone', $weekKey);
    }

    /**
     * @return array<string, int> valeur de `SettlementRank` => total (les zones sans foyer exclues)
     */
    public function tallyBySettlementRank(?string $weekKey = null): array
    {
        return $this->tallyBy('r.settlementRank', $weekKey);
    }

    /**
     * @return array<string, int>
     */
    private function tallyBy(string $field, ?string $weekKey): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select(sprintf('%s AS bucket, SUM(r.tally) AS total', $field))
            ->where(sprintf('%s IS NOT NULL', $field))
            ->groupBy('bucket');

        if ($weekKey !== null) {
            $qb->andWhere('r.weekKey = :week')->setParameter('week', $weekKey);
        }

        $tally = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $tally[(string) $row['bucket']] = (int) $row['total'];
        }

        return $tally;
    }

    /**
     * @return array<string, int>
     */
    private function tallyByZone(string $association, ?string $weekKey): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('z.slug AS bucket, SUM(r.tally) AS total')
            ->join(sprintf('r.%s', $association), 'z')
            ->groupBy('bucket');

        if ($weekKey !== null) {
            $qb->andWhere('r.weekKey = :week')->setParameter('week', $weekKey);
        }

        $tally = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $tally[(string) $row['bucket']] = (int) $row['total'];
        }

        return $tally;
    }
}
