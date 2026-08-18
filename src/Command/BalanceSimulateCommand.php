<?php

namespace App\Command;

use App\Enum\MonsterRank;
use App\GameEngine\Balance\CompositionFactory;
use App\GameEngine\Balance\DailyAnchor;
use App\GameEngine\Balance\DaySimulator;
use App\GameEngine\Balance\EncounterAnchor;
use App\GameEngine\Balance\EncounterOutcome;
use App\GameEngine\Balance\EncounterSimulator;
use App\GameEngine\Balance\GroupEncounterSimulator;
use App\GameEngine\Balance\ReferenceBuild;
use App\GameEngine\Balance\ReferenceBuildFactory;
use App\GameEngine\Balance\ReferenceCharacterFactory;
use App\GameEngine\Dungeon\GroupDungeonCombatService;
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
        private readonly DaySimulator $daySimulator,
        private readonly CompositionFactory $compositionFactory,
        private readonly GroupEncounterSimulator $groupSimulator,
        private readonly GroupDungeonCombatService $dungeon,
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
        // **Le denominateur est le nombre de cases *atteignables*, pas douze.**
        // Les 24 arbres n'occupent que neuf des douze cases de la grille
        // theorique : compter sur douze ferait dire au simulateur qu'il lui
        // manque quatre cases quand une seule est a sa portee.
        $reachable = $this->buildFactory->reachableCells();
        $io->writeln(sprintf(
            ' <comment>%d builds, %d cases sur les %d que la grille des 24 arbres rend atteignables (la grille theorique en compte 12).</comment>',
            \count($builds),
            \count($this->buildFactory->coverage()),
            $reachable,
        ));
        $io->newLine();

        foreach ([MonsterRank::Common, MonsterRank::Elite, MonsterRank::Boss] as $rank) {
            $this->renderScenario($io, $builds, $tier, $rank);
        }

        $this->renderDay($io, $builds, $tier);
        $this->renderEliteMortality($io, $builds, $tier);
        $this->renderGroup($io, $tier);
        $this->renderContextMatrix($io, $builds, $tier);

        $io->note('Les statuts, les depots et la mitigation d\'armure ne sont pas joues : voir EncounterSimulator. Le controle est donc sous-estime par cet instrument.');

        return Command::SUCCESS;
    }

    /**
     * La journee, et l'ancre de fonction qu'elle seule permet de calculer.
     *
     * **C'est le seul invariant qui ne se verifie pas sur un archetype isole**
     * (§ 9 sexies, correction 16) : il compare les quatre fonctions entre elles,
     * ce qu'aucun exercice individuel ne pouvait voir.
     *
     * @param list<ReferenceBuild> $builds
     */
    private function renderDay(SymfonyStyle $io, array $builds, int $tier): void
    {
        $io->section(sprintf('Scenario : une journee de palier %d', $tier));

        $rows = [];
        $restByBuild = [];

        foreach ($builds as $build) {
            $outcome = $this->daySimulator->simulate($this->characterFactory->of($build), $tier);

            // **L'ancre ne lit que les journees menees a leur terme.** Une
            // journee arretee a la troisieme rencontre coute peu, et la compter
            // ferait passer le build qui meurt le plus vite pour le plus
            // econome — l'inverse exact de ce qu'on mesure.
            if ($outcome->clearedItsCommons()) {
                $restByBuild[$outcome->buildLabel] = $outcome->restSeconds;
            }

            $rows[] = [
                $outcome->buildLabel,
                $build->cell(),
                sprintf('%d / %d', $outcome->encountersCleared, $outcome->encountersBudgeted),
                sprintf('%.0f %%', $outcome->completionShare()),
                $outcome->deaths > 0 ? 'oui' : 'non',
                (string) $outcome->lifeLost,
                $outcome->resourceSpent > 0 ? (string) $outcome->resourceSpent : '—',
                sprintf('%d mn', $outcome->restMinutes()),
            ];
        }

        $io->table(['Build', 'Case', 'Rencontres', 'Journee jouee', 'Tombe', 'PV perdus', 'Ressource', 'Attente'], $rows);

        $excluded = \count($builds) - \count($restByBuild);

        if (\count($restByBuild) < 2) {
            $io->writeln(sprintf(
                ' <comment>Ancre de fonction : incalculable — %d build(s) seulement menent leur journee a terme. Un ecart se mesure entre deux.</comment>',
                \count($restByBuild),
            ));
            $io->newLine();

            return;
        }

        $spread = DailyAnchor::restSpread($restByBuild);

        $io->writeln(sprintf(
            ' <comment>Ancre de fonction : ecart d\'attente x%s pour une borne de x%s — %s%s.</comment>',
            is_finite($spread) ? number_format($spread, 2, ',', ' ') : '∞',
            number_format(DailyAnchor::MAX_REST_SPREAD, 1, ',', ' '),
            DailyAnchor::isWithinFunctionAnchor($restByBuild) ? 'tenue' : 'NON tenue',
            $excluded > 0 ? sprintf(' (%d build(s) ecarte(s), journee non menee a terme)', $excluded) : '',
        ));
        $io->newLine();
    }

    /**
     * La mortalite solo des elites — l'un des cinq seuils du § 9 octies.
     *
     * *Une elite tue un joueur seul, quel que soit son archetype* : le canon la
     * chiffre en **part de barre de vie** (102-129 %), et pas en frequence. Une
     * elite qui couterait 40 % ne serait pas une elite ; une elite qui en
     * couterait 300 % ne laisserait aucune place au jeu.
     *
     * @param list<ReferenceBuild> $builds
     */
    private function renderEliteMortality(SymfonyStyle $io, array $builds, int $tier): void
    {
        $io->section(sprintf('Mortalite solo des elites (palier %d)', $tier));

        $rows = [];
        foreach ($builds as $build) {
            $outcome = $this->simulator->simulate($this->characterFactory->of($build), $tier, MonsterRank::Elite);

            $rows[] = [
                $outcome->buildLabel,
                sprintf('%.0f %%', $outcome->lifeCostShare()),
                $outcome->victory ? 'survit' : 'tombe',
            ];
        }

        $io->table(['Build', 'Part de barre', 'Issue'], $rows);
    }

    /**
     * Le donjon a quatre, dans ses quatre compositions.
     *
     * Le seuil du § 9 octies est le plus politique des cinq : *un groupe sans
     * tank ni soigneur vient a bout d'une elite de son palier*. S'il tombe, un
     * role est devenu necessaire — ce que le § 7 bis interdit.
     */
    private function renderGroup(SymfonyStyle $io, int $tier): void
    {
        $io->section(sprintf('Scenario : un donjon a quatre, elite de palier %d', $tier));

        $compositions = $this->compositionFactory->all();
        if ([] === $compositions) {
            $io->writeln(' <comment>Aucune composition jouable : les quatre fonctions ne sont pas toutes au gabarit.</comment>');
            $io->newLine();

            return;
        }

        $rows = [];
        foreach ($compositions as $label => $members) {
            $outcome = $this->groupSimulator->simulate($members, $tier, MonsterRank::Elite, $label);

            $rows[] = [
                $label,
                $outcome->resolved ? (string) $outcome->turns : sprintf('> %d', GroupEncounterSimulator::MAX_ROUNDS),
                $outcome->victory ? 'victoire' : ($outcome->resolved ? 'defaite' : 'sans fin'),
                sprintf('%d / %d', $outcome->membersStanding(), $outcome->memberCount),
                sprintf('%.0f %%', $outcome->encounterShareCleared()),
            ];
        }

        $io->table(['Composition', 'Rondes', 'Issue', 'Debout', 'Rencontre entamee'], $rows);
        $io->writeln(' <comment>Le donjon ne connait aucun soin et aucune mitigation : ces quatre lignes ne different que par les barres de vie et les degats echanges. Le seuil « aucun role n\'est necessaire » est donc tenu par construction — ARC-18 et ARC-19 lui donneront un sens.</comment>');
        $io->newLine();
    }

    /**
     * La matrice contexte x fonction du § 9 septies.3.
     *
     * *Aucune fonction ne doit dominer dans les **deux** colonnes* : une
     * fonction meilleure seule **et** en groupe n'est pas un archetype, c'est un
     * choix par defaut.
     *
     * @param list<ReferenceBuild> $builds
     */
    private function renderContextMatrix(SymfonyStyle $io, array $builds, int $tier): void
    {
        $io->section(sprintf('Matrice contexte x fonction (palier %d)', $tier));

        /** @var array<string, list<float>> $solo */
        $solo = [];
        foreach ($builds as $build) {
            $character = $this->characterFactory->of($build);
            $outcome = $this->simulator->simulate($character, $tier, MonsterRank::Common);

            // Le rendement solo se lit en **part de la rencontre par tour** :
            // un nombre de tours ne se compare pas entre deux adversaires, une
            // part de barre si.
            // Un build qui ne conclut pas rend **zero** : il n'a pas un
            // mauvais rendement, il n'en a aucun. Lui preter la part qu'il a
            // entamee avant de tomber ferait passer une defaite pour une
            // lenteur.
            $solo[$build->role->value][] = $outcome->victory ? 100.0 / max(1, $outcome->turns) : 0.0;
        }

        $group = $this->groupContributionByRole();

        $rows = [];
        foreach ($solo as $role => $values) {
            $rows[] = [
                $role,
                sprintf('%.1f', array_sum($values) / \count($values)),
                isset($group[$role]) ? sprintf('%.1f', $group[$role]) : '—',
            ];
        }

        $io->table(['Fonction', 'Rendement solo (part/tour)', 'Rendement en groupe (part/tour)'], $rows);
        $io->newLine();
    }

    /**
     * Ce que chaque fonction retire par tour dans une rencontre de groupe.
     *
     * @return array<string, float>
     */
    private function groupContributionByRole(): array
    {
        $hpPerMember = $this->dungeon->getHpPerMember();

        $byRole = [];
        foreach ($this->buildFactory->all() as $build) {
            $character = $this->characterFactory->of($build);
            $perTurn = max($character->expectedDamagePerTurn(), $character->expectedFallbackDamagePerTurn());

            $byRole[$build->role->value][] = $perTurn * 100.0 / max(1, $hpPerMember * CompositionFactory::GROUP_SIZE);
        }

        $averaged = [];
        foreach ($byRole as $role => $values) {
            $averaged[$role] = array_sum($values) / \count($values);
        }

        return $averaged;
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
