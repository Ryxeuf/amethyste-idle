<?php

namespace App\Command;

use App\GameEngine\Economy\GilsSupplyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Releve quotidien de la masse monetaire (ECO-15).
 *
 * Une mesure prise une fois ne dit rien. C'est la serie qui permet de repondre a
 * la question d'ECO-15 — les robinets versent-ils plus que les puits n'absorbent
 * — et la serie ne se reconstitue pas apres coup : les Gils du passe ne sont
 * consignes nulle part.
 *
 * D'ou une tache planifiee, et non un calcul a la demande dans le rapport
 * d'equilibrage. Le rapport lit ce que cette commande a ecrit.
 */
#[AsCommand(
    name: 'app:economy:snapshot',
    description: 'Releve la masse monetaire du jeu (bourses, tresors, caisses, escrow)',
)]
class GilsSupplySnapshotCommand extends Command
{
    public function __construct(
        private readonly GilsSupplyService $supplyService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche la mesure sans l\'enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $measure = $this->supplyService->measure();

        $io->definitionList(
            ['Bourses des joueurs' => number_format($measure->playerGils, 0, ',', ' ')],
            ['Tresors de guilde' => number_format($measure->guildGils, 0, ',', ' ')],
            ['Caisses d\'echoppe' => number_format($measure->shopGils, 0, ',', ' ')],
            ['Escrow (mises, commissions)' => number_format($measure->escrowGils, 0, ',', ' ')],
            ['Masse totale' => number_format($measure->total(), 0, ',', ' ')],
            ['Personnages' => (string) $measure->playerCount],
            ['Par personnage' => number_format($measure->perCapita(), 0, ',', ' ')],
        );

        if ($dryRun) {
            $io->note('Mode simulation : aucun releve enregistre.');

            return Command::SUCCESS;
        }

        $this->supplyService->capture();

        $trend = $this->supplyService->perCapitaTrend();
        if (null === $trend) {
            $io->success('Releve enregistre. Il en faut un second pour degager une tendance.');

            return Command::SUCCESS;
        }

        $weekly = $trend->weeklyChangePercent();
        $message = sprintf(
            'Releve enregistre. Masse par tete : %+.1f %% sur %d jour(s), soit %+.1f %% ramenes a la semaine.',
            $trend->perCapitaChangePercent() ?? 0.0,
            $trend->elapsedDays(),
            $weekly ?? 0.0,
        );

        if ($trend->isInflationary()) {
            $io->warning($message . sprintf(
                "\nInflation : les robinets versent plus que les puits n'absorbent (seuil %.0f %%).",
                GilsSupplyService::WEEKLY_ALERT_PERCENT,
            ));

            return Command::SUCCESS;
        }

        if ($trend->isDeflationary()) {
            $io->warning($message . sprintf(
                "\nDeflation : les puits absorbent plus que les robinets ne versent (seuil %.0f %%).",
                GilsSupplyService::WEEKLY_ALERT_PERCENT,
            ));

            return Command::SUCCESS;
        }

        $io->success($message);

        return Command::SUCCESS;
    }
}
