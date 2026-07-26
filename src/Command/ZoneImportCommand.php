<?php

namespace App\Command;

use App\GameEngine\Zone\ZoneDefinitionException;
use App\GameEngine\Zone\ZoneDefinitionLoader;
use App\GameEngine\Zone\ZoneImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Import declaratif du graphe de zones (ZON-11).
 *
 * Lit un fichier YAML (`config/game/zones/world_1.yaml` par defaut) et applique
 * un upsert idempotent des zones et de leurs liaisons. Ajouter du contenu =
 * editer la donnee puis relancer cette commande, sans toucher au code.
 */
#[AsCommand(
    name: 'app:zone:import',
    description: 'Importe le graphe de zones depuis un fichier declaratif YAML (ZON-11)',
)]
class ZoneImportCommand extends Command
{
    public function __construct(
        private readonly ZoneDefinitionLoader $loader,
        private readonly ZoneImporter $importer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Chemin du fichier de definition YAML')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Analyse et valide sans ecrire en base');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $file = $input->getOption('file');
        $path = \is_string($file) && '' !== $file ? $file : $this->loader->defaultFile();
        $dryRun = (bool) $input->getOption('dry-run');

        try {
            $definition = $this->loader->loadFile($path);
            $report = $this->importer->import($definition, $dryRun);
        } catch (ZoneDefinitionException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->title(sprintf('Import de zones — %s%s', $path, $dryRun ? ' (dry-run)' : ''));
        $io->definitionList(
            ['Zones creees' => (string) $report->zonesCreated],
            ['Zones mises a jour' => (string) $report->zonesUpdated],
            ['Liaisons creees' => (string) $report->connectionsCreated],
            ['Liaisons mises a jour' => (string) $report->connectionsUpdated],
        );

        foreach ($report->warnings as $warning) {
            $io->warning($warning);
        }

        if ($dryRun) {
            $io->note('Dry-run : aucune ecriture en base.');
        } else {
            $io->success(sprintf(
                '%d zone(s), %d liaison(s) et %d creature(s) synchronisees.',
                $report->zonesTouched(),
                $report->connectionsTouched(),
                $report->mobsTouched(),
            ));
        }

        return Command::SUCCESS;
    }
}
