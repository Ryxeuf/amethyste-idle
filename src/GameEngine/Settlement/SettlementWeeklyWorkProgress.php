<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Player;
use App\Entity\App\SettlementWeeklyWork;
use App\Entity\App\SettlementWeeklyWorkContribution;
use App\Entity\App\Zone;
use App\Enum\InfluenceActivityType;
use App\GameEngine\Retention\WeeklyCommissionGenerator;
use App\Repository\SettlementWeeklyWorkContributionRepository;
use App\Repository\SettlementWeeklyWorkRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ce qui remplit le chantier de la semaine (RET-05).
 *
 * **Le chantier est celui du lieu, pas du joueur.** On y contribue en jouant
 * **la ou il se tient** : la meme action ne remplit pas le meme chantier selon
 * l'endroit. C'est ce qui fait du chantier un rendez-vous **de zone** et non une
 * liste de courses personnelle — et ce qui donne aux joueurs une raison de se
 * retrouver au meme endroit sans avoir a jouer en meme temps.
 *
 * **L'avancement s'ecrete a la cible** — contrairement a la commission
 * personnelle (RET-02b), ou depasser est normal. Ici le compteur est collectif :
 * laisser vingt joueurs empiler du depassement sur un besoin deja rempli
 * masquerait ceux qui restent, or c'est exactement ce que le chantier existe
 * pour montrer.
 *
 * **La contribution retenue est celle qui a compte.** Un joueur qui abat trente
 * creatures sur un besoin auquel il en manquait cinq se voit crediter cinq :
 * crediter trente ferait de la liste des contributeurs un classement de
 * l'acharnement plutot qu'un releve de l'aide reellement apportee.
 */
class SettlementWeeklyWorkProgress
{
    public function __construct(
        private readonly SettlementWeeklyWorkRepository $workRepository,
        private readonly SettlementWeeklyWorkContributionRepository $contributionRepository,
        private readonly SettlementDepositService $depositService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return bool `true` si le chantier vient d'etre complete
     */
    public function contribute(Player $player, InfluenceActivityType $activity, int $amount = 1, ?Zone $zone = null, ?\DateTimeImmutable $now = null): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $zone ??= $player->getCurrentZone();
        if ($zone === null) {
            return false;
        }

        $now ??= new \DateTimeImmutable();
        $work = $this->workRepository->findCurrentForZone($zone, WeeklyCommissionGenerator::weekKey($now));
        if ($work === null || $work->getCompletedAt() !== null) {
            return false;
        }

        $retained = $work->contribute($activity, $amount);
        if ($retained <= 0) {
            return false;
        }

        $this->contributionFor($work, $player)->addUnits($retained);

        $completed = false;
        if ($work->isComplete()) {
            $work->setCompletedAt($now);
            // Le bonus de cloture est un depot **hors plafond** (RET-02b) : un
            // chantier se remplit une fois par semaine et n'est pas grindable.
            // Le plafonner mangerait la recompense collective d'une zone tres
            // frequentee, ce qui punirait exactement l'activite recherchee.
            $this->depositService->deposit($player, 'settlement_work', $zone, $now);
            $completed = true;
        }

        $this->entityManager->flush();

        return $completed;
    }

    private function contributionFor(SettlementWeeklyWork $work, Player $player): SettlementWeeklyWorkContribution
    {
        $contribution = $this->contributionRepository->findOneFor($work, $player);
        if ($contribution === null) {
            $contribution = new SettlementWeeklyWorkContribution($work, $player);
            $this->entityManager->persist($contribution);
        }

        return $contribution;
    }
}
