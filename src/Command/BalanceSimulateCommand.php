<?php

namespace App\Command;

use App\Enum\MonsterRank;
use App\GameEngine\Balance\EncounterAnchor;
use App\GameEngine\Balance\EncounterOutcome;
use App\GameEngine\Balance\EncounterSimulator;
use App\GameEngine\Balance\ReferenceBuild;
use App\GameEngine\Balance\ReferenceBuildFactory;
use App\GameEngine\Balance\ReferenceCharacterFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * `app:balance:simulate` — la sœur **dynamique** d'`app:balance:report`
 * (ARC-17c-b).
 *
 * `app:balance:report` est statique : il compte, il rapproche, il signale des
 * anomalies. Il ne joue **aucun** combat, et c'est ce qui l'empeche de repondre
 * a la seule question qui compte pour les archetypes — *combien de tours, et a
 * quel prix ?* GAME_ARCHETYPES § 9 sexies l'a montre sur quatre exercices
 * manuels : sur un combat le guerrier domine, sur une journee c'est le
 * guerisseur. **Aucun exercice individuel ne pouvait le voir** ; c'est la
 * comparaison qui le revele, donc c'est une table croisee qu'il faut produire.
 *
 * Cette commande livre les trois scenarios **solo** — un commun, une elite, un
 * boss —, ceux dont la mesure ne demande qu'un personnage. La journee et le
 * donjon a quatre, qui demandent respectivement un budget d'energie et une
 * composition, sont ARC-17c-c avec les seuils tenus en CI.
 */
#[AsCommand(
    name: 'app:balance:simulate',
    description: 'Joue les builds de reference contre le bestiaire et rend la table croisee du canon',
)]
class BalanceSimulateCommand extends Command
{
    public function __construct(
        private readonly ReferenceBuildFactory $buildFactory,
        private readonly ReferenceCharacterFactory $characterFactory,
        private readonly EncounterSimulator $simulator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('tier', 't', InputOption::VALUE_OPTIONAL, 'Palier d\'adversaire a jouer (0 a 4)', '2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tier = max(0, min(4, (int) $input->getOption('tier')));

        $io->title(sprintf('Simulation d\'equilibrage — palier %d', $tier));

        $builds = $this->buildFactory->all();
        if ([] === $builds) {
            $io->error('Aucun build de reference : aucun arbre n\'est au gabarit, il n\'y a personne a jouer.');

            return Command::FAILURE;
        }

        // La couverture se dit **avant** les chiffres, jamais apres. Une table
        // qui se lirait comme complete alors qu'elle ne joue que cinq arbres
        // donnerait a ses moyennes une autorite qu'elles n'ont pas.
        $io->section('Ce que cette table joue');
        $io->listing($this->buildFactory->coverage());
        $io->writeln(sprintf(' <comment>%d builds, %d cases de la grille sur 12.</comment>', \count($builds), \count($this->buildFactory->coverage())));
        $io->newLine();

        foreach ([MonsterRank::Common, MonsterRank::Elite, MonsterRank::Boss] as $rank) {
            $this->renderScenario($io, $builds, $tier, $rank);
        }

        $io->note('Les statuts, les depots et la mitigation d\'armure ne sont pas joues : voir EncounterSimulator. Le controle est donc sous-estime par cet instrument.');

        return Command::SUCCESS;
    }

    /**
     * @param list<ReferenceBuild> $builds
     */
    private function renderScenario(SymfonyStyle $io, array $builds, int $tier, MonsterRank $rank): void
    {
        [$min, $max] = EncounterAnchor::TURN_BANDS[$rank->value];

        $io->section(sprintf('Scenario : un %s de palier %d (bande %d-%d tours)', $rank->value, $tier, $min, $max));

        $rows = [];
        foreach ($builds as $build) {
            $outcome = $this->simulator->simulate($this->characterFactory->of($build), $tier, $rank);
            $rows[] = $this->rowOf($outcome, $build->cell());
        }

        $io->table(['Build', 'Case', 'Tours', 'Issue', 'PV restants', 'Cout en PV', 'Ressource', 'Bande'], $rows);
    }

    /**
     * @return list<string>
     */
    private function rowOf(EncounterOutcome $outcome, string $cell): array
    {
        return [
            $outcome->buildLabel,
            $cell,
            $outcome->resolved ? (string) $outcome->turns : sprintf('> %d', EncounterSimulator::MAX_TURNS),
            $this->verdictOf($outcome),
            sprintf('%d / %d', $outcome->lifeRemaining, $outcome->maxLife),
            sprintf('%.0f %%', $outcome->lifeCostShare()),
            $outcome->resourceSpent > 0 ? (string) $outcome->resourceSpent : '—',
            $outcome->isWithinBand() ? 'oui' : 'non',
        ];
    }

    private function verdictOf(EncounterOutcome $outcome): string
    {
        if (!$outcome->resolved) {
            return 'sans fin';
        }

        return $outcome->victory ? 'victoire' : 'mort';
    }
}
