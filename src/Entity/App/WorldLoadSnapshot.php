<?php

namespace App\Entity\App;

use App\Repository\WorldLoadSnapshotRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Charge du monde sur une journee (FOY-17).
 *
 * Le dimensionnement du monde — facteur `W`, quotas de Crue, seuils de foyer —
 * repose sur « la population active ». Encore faut-il la definir, et
 * [BALANCE.md § 22.5](../../../docs/BALANCE.md) tranche : **on mesure la charge,
 * pas les tetes**.
 *
 * Un decompte de comptes se gonfle avec des comptes secondaires ; une mesure
 * d'energie depensee, non — parce qu'un joueur qui fait tourner trois comptes a
 * fond **exerce reellement la pression de trois joueurs** sur les filons, et le
 * monde doit bien se dimensionner pour trois. Il n'y a plus rien a exploiter :
 * on ne peut pas gonfler le monde sans produire exactement la charge pour
 * laquelle il se dimensionne.
 *
 * **Pourquoi un instantane journalier plutot qu'un compteur global.**
 * Incrementer une ligne unique a chaque depense d'energie ferait de cette ligne
 * un point de contention sur le chemin le plus chaud du jeu. Chaque joueur porte
 * donc son propre cumul (`Player::actionEnergySpentTotal`), et le tick quotidien
 * en prend la somme : la difference avec l'instantane de la veille donne la
 * depense du jour. Aucune ecriture partagee, et un historique exploitable.
 */
#[ORM\Entity(repositoryClass: WorldLoadSnapshotRepository::class)]
#[ORM\Table(name: 'world_load_snapshot')]
#[ORM\UniqueConstraint(name: 'uq_world_load_snapshot_day', columns: ['day'])]
class WorldLoadSnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    /**
     * Jour mesure (minuit). Unique : le tick est idempotent.
     */
    #[ORM\Column(name: 'day', type: 'date_immutable')]
    private \DateTimeImmutable $day;

    /**
     * Somme de `Player::actionEnergySpentTotal` sur tous les personnages, au
     * moment de la capture. Monotone croissante — c'est ce qui permet de
     * deduire la depense d'un jour par simple difference.
     */
    #[ORM\Column(name: 'cumulative_energy', type: 'bigint')]
    private string $cumulativeEnergy = '0';

    /**
     * Energie depensee **ce jour-la**, deduite de l'instantane precedent.
     */
    #[ORM\Column(name: 'daily_energy', type: 'integer')]
    private int $dailyEnergy = 0;

    #[ORM\Column(name: 'captured_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $capturedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDay(): \DateTimeImmutable
    {
        return $this->day;
    }

    public function setDay(\DateTimeImmutable $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getCumulativeEnergy(): int
    {
        return (int) $this->cumulativeEnergy;
    }

    public function setCumulativeEnergy(int $cumulativeEnergy): self
    {
        $this->cumulativeEnergy = (string) max(0, $cumulativeEnergy);

        return $this;
    }

    public function getDailyEnergy(): int
    {
        return $this->dailyEnergy;
    }

    public function setDailyEnergy(int $dailyEnergy): self
    {
        $this->dailyEnergy = max(0, $dailyEnergy);

        return $this;
    }

    public function getCapturedAt(): \DateTimeImmutable
    {
        return $this->capturedAt;
    }

    public function setCapturedAt(\DateTimeImmutable $capturedAt): self
    {
        $this->capturedAt = $capturedAt;

        return $this;
    }
}
