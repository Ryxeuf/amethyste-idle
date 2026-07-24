<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
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
    private const TABLES = [
        'joueurs' => 'player',
        'mobs' => 'mob',
        'pnjs' => 'pnj',
        'object layers' => 'object_layer',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
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
        $fix = (bool) $input->getOption('fix');
        $connection = $this->entityManager->getConnection();

        $zoneColumn = ['player' => 'current_zone_id'];
        $orphanTotal = 0;
        $rows = [];

        foreach (self::TABLES as $label => $table) {
            $column = $zoneColumn[$table] ?? 'zone_id';

            if ($fix) {
                $fixed = $connection->executeStatement(sprintf(
                    'UPDATE %1$s t SET %2$s = z.id FROM zone z WHERE z.source_map_id = t.map_id AND z.enabled = TRUE AND t.%2$s IS NULL',
                    $table,
                    $column
                ));
                if ($fixed > 0) {
                    $io->text(sprintf('Backfill %s : %d entite(s) rattachee(s).', $label, $fixed));
                }
            }

            /** @var array{total: int, with_zone: int, orphans: int, off_graph: int} $stats */
            $stats = $connection->fetchAssociative(sprintf(
                <<<'SQL'
                SELECT
                    COUNT(*) AS total,
                    COUNT(t.%2$s) AS with_zone,
                    COUNT(*) FILTER (WHERE t.%2$s IS NULL AND z.id IS NOT NULL) AS orphans,
                    COUNT(*) FILTER (WHERE t.%2$s IS NULL AND z.id IS NULL) AS off_graph
                FROM %1$s t
                LEFT JOIN zone z ON z.source_map_id = t.map_id AND z.enabled = TRUE
                SQL,
                $table,
                $column
            ));

            $orphanTotal += (int) $stats['orphans'];
            $rows[] = [$label, $stats['total'], $stats['with_zone'], $stats['orphans'], $stats['off_graph']];
        }

        $io->table(['Entite', 'Total', 'Avec zone', 'Orphelines (carte zonee)', 'Hors graphe (donjon/test)'], $rows);

        if ($orphanTotal > 0) {
            $io->error(sprintf('%d entite(s) orpheline(s) : leur carte a une zone mais elles n\'y sont pas rattachees. Relancer avec --fix.', $orphanTotal));

            return Command::FAILURE;
        }

        $io->success('Aucune entite orpheline : toutes les entites sur une carte zonee sont rattachees a leur zone.');

        return Command::SUCCESS;
    }
}
