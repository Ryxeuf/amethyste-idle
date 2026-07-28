<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Entity\App\SettlementWeeklyWork;
use App\GameEngine\Retention\WeeklyCommissionGenerator;
use App\Repository\SettlementRepository;
use App\Repository\SettlementWeeklyWorkRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ouverture du chantier de la semaine (RET-05).
 *
 * **Ce que la ville demande, personne ne le choisit.** Les besoins se deduisent
 * du **type** du foyer, qui se deduit lui-meme de l'indice dominant (FOY-03) :
 * c'est donc la frequentation passee qui decide de ce que la ville reclame. Un
 * Comptoir demande de la matiere, un Bastion qu'on tienne les abords.
 *
 * **La clef de semaine est celle de tout le monde** : la meme que les defis de
 * guilde et les commissions personnelles. Le contrat RET-07 tient a ce qu'il
 * n'y ait **qu'une** rotation du lundi ; cinq horloges finiraient par deriver, et
 * un joueur verrait ses rendez-vous s'ouvrir a des moments differents sans
 * raison lisible.
 *
 * **Idempotent par semaine** : rejouer la generation ne recree ni ne reinitialise
 * un chantier existant. Un `--force` ne doit pas effacer l'effort de la semaine.
 */
class SettlementWeeklyWorkGenerator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementWeeklyWorkRepository $workRepository,
        private readonly SettlementDefinitionLoader $loader,
    ) {
    }

    /**
     * @return array{created: int, skipped: int, without_demand: int}
     */
    public function generate(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $weekKey = WeeklyCommissionGenerator::weekKey($now);
        $definition = $this->loader->load()['weekly_work'];

        $report = ['created' => 0, 'skipped' => 0, 'without_demand' => 0];

        foreach ($this->settlementRepository->findAll() as $settlement) {
            if ($this->workRepository->findOneFor($settlement, $weekKey) !== null) {
                ++$report['skipped'];
                continue;
            }

            $needs = $this->needsFor($settlement, $definition);
            if ($needs === []) {
                // Aucune demande declaree pour ce type : le foyer passe son tour
                // plutot que d'ouvrir un chantier vide, qui serait rempli
                // d'avance et se cloturerait sans que personne n'y touche.
                ++$report['without_demand'];
                continue;
            }

            $this->entityManager->persist(new SettlementWeeklyWork($settlement, $weekKey, $needs));
            ++$report['created'];
        }

        $this->entityManager->flush();

        return $report;
    }

    /**
     * Les besoins d'un foyer, tels que son type et son rang les dictent.
     *
     * @param array{demands: array<string, list<string>>, targets: array<string, int>, rank_multipliers: array<string, int>} $definition
     *
     * @return list<array{activity: string, target: int, progress: int}>
     */
    public function needsFor(Settlement $settlement, array $definition): array
    {
        $type = $settlement->getType();
        // Sous le Hameau, un foyer n'a pas encore d'identite (FOY-03) : il
        // demande le minimum vital. Le laisser sans chantier l'aurait prive du
        // seul rendez-vous collectif qu'un Campement puisse offrir.
        $identity = null === $type ? 'none' : $type->value;
        $demands = $definition['demands'][$identity] ?? [];

        $multiplier = $definition['rank_multipliers'][$settlement->getRank()->value] ?? 1;

        $needs = [];
        foreach ($demands as $activity) {
            $target = $definition['targets'][$activity] ?? null;
            if ($target === null) {
                continue;
            }

            $needs[] = [
                'activity' => $activity,
                'target' => max(1, $target * $multiplier),
                'progress' => 0,
            ];
        }

        return $needs;
    }
}
