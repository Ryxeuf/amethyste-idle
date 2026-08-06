<?php

namespace App\Command;

use App\GameEngine\Zone\WorldEntityZoneBackfiller;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:zone:audit',
    description: 'Audit world entities zone attachment (pivot PBBG, ZON-04) — reports orphans, --fix backfills them',
)]
class ZoneAuditCommand extends Command
{
    /**
     * Libelles d'affichage. Les tables et leurs colonnes vivent dans
     * `WorldEntityZoneBackfiller` : le SQL de rattachement est partage avec
     * `app:zone:import`, et deux copies auraient divergé au premier ajout.
     *
     * @var array<string, string>
     */
    private const LABELS = [
        'player' => 'joueurs',
        'mob' => 'mobs',
        'pnj' => 'pnjs',
        'object_layer' => 'object layers',
    ];

    public function __construct(
        private readonly WorldEntityZoneBackfiller $backfiller,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('fix', null, InputOption::VALUE_NONE, 'Backfill orphans from their current map (zone.source_map_id)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ((bool) $input->getOption('fix')) {
            foreach ($this->backfiller->backfill() as $table => $rows) {
                $io->text(sprintf('Backfill %s : %d entite(s) rattachee(s).', self::LABELS[$table] ?? $table, $rows));
            }
        }

        $rows = [];
        $broken = 0;
        foreach ($this->backfiller->stats() as $table => $stats) {
            $broken += $stats['orphans'] + $stats['misplaced'];
            $rows[] = [
                self::LABELS[$table] ?? $table,
                $stats['total'],
                $stats['with_zone'],
                $stats['orphans'],
                $stats['misplaced'],
                $stats['off_graph'],
            ];
        }

        $io->table(
            ['Entite', 'Total', 'Avec zone', 'Orphelines (carte zonee)', 'Egarees (autre zone de sa carte)', 'Hors graphe (donjon/test)'],
            $rows,
        );

        if ($broken > 0) {
            $io->error(sprintf(
                "%d entite(s) mal rattachee(s).\n"
                . "  Orpheline : sa carte a une zone, elle n'y est pas rattachee.\n"
                . "  Egaree    : elle est dans une autre zone de sa propre carte — le cas d'une carte partagee,\n"
                . "              ou la zone principale n'etait pas designee (voir `source_map_primary`).\n"
                . 'Relancer avec --fix.',
                $broken,
            ));

            return Command::FAILURE;
        }

        $io->success('Aucune entite mal rattachee : toutes les entites sur une carte zonee sont dans la bonne zone.');

        return Command::SUCCESS;
    }
}
