<?php

namespace App\Repository;

use App\Entity\App\AuctionTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Journal economique de l'hotel des ventes (ECO-16b).
 *
 * Les regles anti-abus d'ECO-16a refusent ce qui est **certainement** abusif :
 * commerce entre personnages d'un meme compte, echanges repetes entre deux
 * joueurs. Restent les cas qui ne se prouvent pas a la transaction et ne se
 * voient qu'a l'echelle — un prix hors de toute logique, un pic de vente
 * soudain. Ceux-la ne se bloquent pas, ils **se donnent a voir**.
 *
 * @extends ServiceEntityRepository<AuctionTransaction>
 */
class AuctionTransactionRepository extends ServiceEntityRepository
{
    /**
     * Nombre minimum de ventes d'un objet avant de juger un prix aberrant.
     * En dessous, la moyenne n'a aucune valeur de reference.
     */
    public const OUTLIER_MIN_SAMPLE = 3;

    /** Ecart a la moyenne au-dela duquel une vente est signalee. */
    public const OUTLIER_RATIO = 5.0;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuctionTransaction::class);
    }

    /**
     * La mediane des prix **unitaires** des ventes conclues d'une matiere
     * (FAC-05). Les ventes conclues, jamais les annonces : une annonce est un
     * espoir, une transaction est un prix.
     *
     * La mediane plutot que la moyenne — `findPriceOutliers` montre pourquoi :
     * une seule vente aberrante deplace une moyenne, pas une mediane. Rend
     * `null` sur un marche muet (aucune vente dans la fenetre) : l'appelant
     * choisit sa reference de repli.
     */
    public function medianUnitPriceForSlug(string $slug, \DateTimeImmutable $since): ?int
    {
        $rows = $this->createQueryBuilder('t')
            ->select('l.pricePerUnit AS unitPrice')
            ->join('t.listing', 'l')
            ->join('l.playerItem', 'pi')
            ->join('pi.genericItem', 'gi')
            ->where('t.purchasedAt >= :since')
            ->andWhere('gi.slug = :slug')
            ->setParameter('since', $since)
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getScalarResult();

        $prices = array_map(static fn (array $row): int => (int) $row['unitPrice'], $rows);
        if ([] === $prices) {
            return null;
        }

        sort($prices);
        $count = \count($prices);
        $middle = intdiv($count, 2);

        // Effectif pair : la moyenne des deux valeurs centrales, arrondie au
        // gil — la mediane d'un marche se paie en monnaie entiere.
        if (0 === $count % 2) {
            return (int) round(($prices[$middle - 1] + $prices[$middle]) / 2);
        }

        return $prices[$middle];
    }

    /**
     * @return AuctionTransaction[]
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.listing', 'l')->addSelect('l')
            ->join('l.playerItem', 'pi')->addSelect('pi')
            ->join('pi.genericItem', 'gi')->addSelect('gi')
            ->join('t.buyer', 'b')->addSelect('b')
            ->join('l.seller', 's')->addSelect('s')
            ->orderBy('t.purchasedAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * Couples acheteur/vendeur les plus actifs sur la fenetre.
     *
     * Le plafond d'ECO-16a bloque au-dela d'un seuil ; ce classement montre ce
     * qui se passe **en dessous** — deux joueurs qui frolent la limite chaque
     * jour sont invisibles pour la regle et evidents ici.
     *
     * @return list<array{buyer: string, seller: string, trades: int, volume: int}>
     */
    public function findTopTradingPairs(int $sinceHours = 168, int $limit = 20): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('b.name AS buyer, s.name AS seller, COUNT(t.id) AS trades, COALESCE(SUM(t.totalPrice), 0) AS volume')
            ->join('t.listing', 'l')
            ->join('t.buyer', 'b')
            ->join('l.seller', 's')
            ->where('t.purchasedAt >= :since')
            ->setParameter('since', new \DateTimeImmutable(sprintf('-%d hours', max(1, $sinceHours))))
            ->groupBy('b.name')->addGroupBy('s.name')
            ->orderBy('trades', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();

        return array_map(static fn (array $row): array => [
            'buyer' => (string) $row['buyer'],
            'seller' => (string) $row['seller'],
            'trades' => (int) $row['trades'],
            'volume' => (int) $row['volume'],
        ], $rows);
    }

    /**
     * Ventes dont le prix s'ecarte massivement de la moyenne de l'objet.
     *
     * Un prix aberrant n'est pas une preuve : un objet rare bien negocie en
     * produit un aussi. C'est un **signal**, a lire avec le couple de joueurs
     * concerne — d'ou la presence des deux noms dans le resultat.
     *
     * @return list<array{item: string, price: int, average: float, ratio: float, buyer: string, seller: string, at: \DateTimeInterface}>
     */
    public function findPriceOutliers(int $sinceHours = 168, int $limit = 20): array
    {
        $since = new \DateTimeImmutable(sprintf('-%d hours', max(1, $sinceHours)));

        $averages = [];
        $statRows = $this->createQueryBuilder('t')
            ->select('gi.slug AS slug, AVG(t.totalPrice) AS average, COUNT(t.id) AS sales')
            ->join('t.listing', 'l')
            ->join('l.playerItem', 'pi')
            ->join('pi.genericItem', 'gi')
            ->where('t.purchasedAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('gi.slug')
            ->getQuery()
            ->getResult();

        foreach ($statRows as $row) {
            if ((int) $row['sales'] >= self::OUTLIER_MIN_SAMPLE && (float) $row['average'] > 0.0) {
                $averages[(string) $row['slug']] = (float) $row['average'];
            }
        }

        if ([] === $averages) {
            return [];
        }

        $outliers = [];
        foreach ($this->findRecentSince($since) as $transaction) {
            $item = $transaction->getListing()->getPlayerItem()->getGenericItem();
            $average = $averages[$item->getSlug()] ?? null;
            if (null === $average) {
                continue;
            }

            $ratio = $transaction->getTotalPrice() / $average;
            if ($ratio < self::OUTLIER_RATIO && $ratio > 1 / self::OUTLIER_RATIO) {
                continue;
            }

            $outliers[] = [
                'item' => $item->getName(),
                'price' => $transaction->getTotalPrice(),
                'average' => round($average, 0),
                'ratio' => round($ratio, 2),
                'buyer' => $transaction->getBuyer()->getName(),
                'seller' => $transaction->getListing()->getSeller()->getName(),
                'at' => $transaction->getPurchasedAt(),
            ];
        }

        usort($outliers, static fn (array $a, array $b): int => $b['ratio'] <=> $a['ratio']);

        return \array_slice($outliers, 0, max(1, $limit));
    }

    /**
     * Volume de ventes par jour, pour reperer un pic.
     *
     * @return list<array{day: string, trades: int, volume: int}>
     */
    public function findDailyVolume(int $days = 14): array
    {
        // Le regroupement par jour se fait en PHP : tronquer une date en DQL
        // demande une fonction propre au SGBD, et le volume traite ici (deux
        // semaines de ventes) ne justifie pas ce couplage.
        $rows = $this->createQueryBuilder('t')
            ->select('t.purchasedAt AS purchasedAt, t.totalPrice AS totalPrice')
            ->where('t.purchasedAt >= :since')
            ->setParameter('since', new \DateTimeImmutable(sprintf('-%d days', max(1, $days))))
            ->getQuery()
            ->getResult();

        $byDay = [];
        foreach ($rows as $row) {
            $day = $row['purchasedAt']->format('Y-m-d');
            $byDay[$day] ??= ['day' => $day, 'trades' => 0, 'volume' => 0];
            ++$byDay[$day]['trades'];
            $byDay[$day]['volume'] += (int) $row['totalPrice'];
        }

        krsort($byDay);

        return array_values($byDay);
    }

    /**
     * @return AuctionTransaction[]
     */
    private function findRecentSince(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.listing', 'l')->addSelect('l')
            ->join('l.playerItem', 'pi')->addSelect('pi')
            ->join('pi.genericItem', 'gi')->addSelect('gi')
            ->join('t.buyer', 'b')->addSelect('b')
            ->join('l.seller', 's')->addSelect('s')
            ->where('t.purchasedAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('t.purchasedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
