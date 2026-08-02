<?php

namespace App\GameEngine\Dungeon;

use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use App\Enum\MonsterRank;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Tire la rencontre d'une etape de donjon dans la faune du palier (DON-03).
 *
 * Le donjon ne definit pas ses creatures : il **puise dans la faune de son
 * palier** (GAME_DUNGEONS §3). La faune, c'est ce qui est reellement place
 * dans le graphe — les especes qu'un `Mob` rattache a une zone incarne, la
 * meme definition que MAT-08 pour l'obtenabilite des materia. C'est ce qui
 * ecarte d'office les mannequins d'entrainement (aucune zone) et les boss
 * narratifs reserves (jamais places), sans aucune liste a entretenir.
 *
 * Un donjon T4 se peuple donc tout seul le jour ou le palier T4 est peuple —
 * et si une case tier x rang n'est pas encore placee, le repli tire dans les
 * especes livrees du palier (mannequins exclus par leur `trainingMode`), pour
 * qu'un donjon ne soit jamais vide par accident de repartition.
 */
class DungeonEncounterPicker
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function pick(int $tier, MonsterRank $rank): ?Monster
    {
        $candidates = $this->candidates($tier, $rank);
        if ([] === $candidates) {
            return null;
        }

        return $candidates[$this->roll(\count($candidates))];
    }

    /**
     * @return list<Monster>
     */
    public function candidates(int $tier, MonsterRank $rank): array
    {
        // GROUP BY sur la cle primaire plutot que DISTINCT : l'entite porte
        // des colonnes json (traductions) sans operateur d'egalite en
        // PostgreSQL, et la dependance fonctionnelle sur l'id couvre le reste.
        $placed = $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(Monster::class, 'm')
            ->join(Mob::class, 'mob', 'WITH', 'mob.monster = m')
            ->where('mob.zone IS NOT NULL')
            ->andWhere('m.tier = :tier')
            ->andWhere('m.rank = :rank')
            ->andWhere('m.trainingMode IS NULL')
            ->groupBy('m.id')
            ->setParameter('tier', $tier)
            ->setParameter('rank', $rank)
            ->getQuery()
            ->getResult();

        if ([] !== $placed) {
            return $placed;
        }

        // Repli : la case n'est pas placee dans le graphe — les especes
        // livrees du palier suffisent, un donjon ne doit jamais etre vide
        // parce qu'une redistribution de faune est passee par la.
        return $this->entityManager->getRepository(Monster::class)
            ->findBy(['tier' => $tier, 'rank' => $rank, 'trainingMode' => null]);
    }

    /**
     * Indice aleatoire 0..count-1 — surchargeable en test pour un tirage
     * deterministe.
     */
    protected function roll(int $count): int
    {
        return random_int(0, max(0, $count - 1));
    }
}
