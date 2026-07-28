<?php

namespace App\GameEngine\Retention;

use App\Entity\App\Player;
use App\Enum\InfluenceActivityType;
use App\Repository\PlayerWeeklyCommissionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ce qui fait avancer la commission de la semaine (RET-02b).
 *
 * Une seule regle, et elle est plus etroite qu'il n'y parait : **seule l'activite
 * demandee compte**. Faire avancer une commission de chasse en pechant reviendrait
 * a dire que la semaine n'attendait rien de precis, et un rendez-vous qui accepte
 * n'importe quoi n'est plus un rendez-vous.
 *
 * L'avancement ne se **plafonne pas** a l'objectif : depasser est normal — on ne
 * compte pas ses prises au dernier poisson pres — et c'est la jauge qui borne
 * l'affichage a 100 %, pas le compteur. Ecreter en base ferait perdre
 * l'information au moment ou elle sert : savoir de combien on a depasse dit si
 * l'objectif etait bien calibre.
 *
 * Le flush est laisse a l'appelant, comme au depot de sediment : l'avancement
 * d'une commission ne doit jamais etre la raison d'un aller-retour en base
 * supplementaire sur une action de jeu.
 */
class WeeklyCommissionProgress
{
    public function __construct(
        private readonly PlayerWeeklyCommissionRepository $commissionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param int $amount unites accomplies — la quantite recoltee, pas le nombre d'actions
     *
     * @return bool `true` si la commission vient de passer complete
     */
    public function advance(Player $player, InfluenceActivityType $activity, int $amount = 1, ?\DateTimeImmutable $now = null): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $now ??= new \DateTimeImmutable();
        $commission = $this->commissionRepository->findCurrent($player, WeeklyCommissionGenerator::weekKey($now));

        if ($commission === null || $commission->getActivity() !== $activity) {
            return false;
        }

        $wasComplete = $commission->isComplete();
        $commission->addProgress($amount);
        $this->entityManager->flush();

        return !$wasComplete && $commission->isComplete();
    }
}
