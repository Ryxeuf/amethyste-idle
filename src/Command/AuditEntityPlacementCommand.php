<?php

namespace App\Command;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Pnj;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:audit:entity-placement',
    description: 'Audit and fix entities placed on blocked cells (mobs, harvest spots, PNJs)',
)]
class AuditEntityPlacementCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Fix entities by moving them to nearest free cell')
            ->addOption('map-id', null, InputOption::VALUE_REQUIRED, 'Only audit a specific map');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fix = $input->getOption('fix');
        $mapId = $input->getOption('map-id');

        $maps = $mapId
            ? [$this->entityManager->getRepository(Map::class)->find((int) $mapId)]
            : $this->entityManager->getRepository(Map::class)->findAll();

        $maps = array_filter($maps);
        if (empty($maps)) {
            $io->error('Aucune carte trouvee.');

            return Command::FAILURE;
        }

        $totalIssues = 0;
        $totalFixed = 0;

        foreach ($maps as $map) {
            $io->section(sprintf('Carte #%d — %s', $map->getId(), $map->getName()));

            // Build cell movement lookup from area data
            $cellMovements = $this->buildCellMovements($map);
            $io->comment(sprintf('%d cellules chargees', count($cellMovements)));

            // Audit mobs
            $mobs = $this->entityManager->getRepository(Mob::class)->findBy(['map' => $map]);
            foreach ($mobs as $mob) {
                $coords = $mob->getCoordinates();
                $movement = $cellMovements[$coords] ?? null;

                if ($movement === null || $movement === CellHelper::MOVE_UNREACHABLE) {
                    $label = $movement === null ? 'hors carte' : 'bloquee';
                    $io->warning(sprintf('Mob #%d "%s" en %s — case %s (m=%s)',
                        $mob->getId(), $mob->getName(), $coords, $label, $movement ?? 'null'));
                    ++$totalIssues;

                    if ($fix) {
                        $newCoords = $this->findNearestFreeCell($coords, $cellMovements);
                        if ($newCoords) {
                            $mob->setCoordinates($newCoords);
                            $io->info(sprintf('  -> deplace en %s', $newCoords));
                            ++$totalFixed;
                        } else {
                            $io->error(sprintf('  -> aucune case libre trouvee a proximite'));
                        }
                    }
                }
            }

            // Audit harvest spots
            $harvestSpots = $this->entityManager->getRepository(ObjectLayer::class)
                ->findBy(['map' => $map, 'type' => ObjectLayer::TYPE_HARVEST_SPOT]);
            foreach ($harvestSpots as $spot) {
                $coords = $spot->getCoordinates();
                $movement = $cellMovements[$coords] ?? null;

                if ($movement === null || $movement === CellHelper::MOVE_UNREACHABLE) {
                    $label = $movement === null ? 'hors carte' : 'bloquee';
                    $io->warning(sprintf('Spot recolte #%d "%s" en %s — case %s (m=%s)',
                        $spot->getId(), $spot->getName(), $coords, $label, $movement ?? 'null'));
                    ++$totalIssues;

                    if ($fix) {
                        $newCoords = $this->findNearestFreeCell($coords, $cellMovements);
                        if ($newCoords) {
                            $spot->setCoordinates($newCoords);
                            $io->info(sprintf('  -> deplace en %s', $newCoords));
                            ++$totalFixed;
                        } else {
                            $io->error(sprintf('  -> aucune case libre trouvee a proximite'));
                        }
                    }
                }
            }

            // Audit PNJs
            $pnjs = $this->entityManager->getRepository(Pnj::class)->findBy(['map' => $map]);
            foreach ($pnjs as $pnj) {
                $coords = $pnj->getCoordinates();
                $movement = $cellMovements[$coords] ?? null;

                if ($movement === null || $movement === CellHelper::MOVE_UNREACHABLE) {
                    $label = $movement === null ? 'hors carte' : 'bloquee';
                    $io->warning(sprintf('PNJ #%d "%s" en %s — case %s (m=%s)',
                        $pnj->getId(), $pnj->getName(), $coords, $label, $movement ?? 'null'));
                    ++$totalIssues;

                    if ($fix) {
                        $newCoords = $this->findNearestFreeCell($coords, $cellMovements);
                        if ($newCoords) {
                            $pnj->setCoordinates($newCoords);
                            $io->info(sprintf('  -> deplace en %s', $newCoords));
                            ++$totalFixed;
                        } else {
                            $io->error(sprintf('  -> aucune case libre trouvee a proximite'));
                        }
                    }
                }
            }

            // Audit portals
            $portals = $this->entityManager->getRepository(ObjectLayer::class)
                ->findBy(['map' => $map, 'type' => ObjectLayer::TYPE_PORTAL]);
            foreach ($portals as $portal) {
                $coords = $portal->getCoordinates();
                $movement = $cellMovements[$coords] ?? null;

                if ($movement === null || $movement === CellHelper::MOVE_UNREACHABLE) {
                    $label = $movement === null ? 'hors carte' : 'bloquee';
                    $io->warning(sprintf('Portail #%d "%s" en %s — case %s (m=%s)',
                        $portal->getId(), $portal->getName(), $coords, $label, $movement ?? 'null'));
                    ++$totalIssues;

                    if ($fix) {
                        $newCoords = $this->findNearestFreeCell($coords, $cellMovements);
                        if ($newCoords) {
                            $portal->setCoordinates($newCoords);
                            $io->info(sprintf('  -> deplace en %s', $newCoords));
                            ++$totalFixed;
                        } else {
                            $io->error(sprintf('  -> aucune case libre trouvee a proximite'));
                        }
                    }
                }
            }
        }

        if ($fix && $totalFixed > 0) {
            $this->entityManager->flush();
        }

        $io->newLine();
        if ($totalIssues === 0) {
            $io->success('Aucun probleme de placement detecte.');
        } else {
            $io->warning(sprintf('%d probleme(s) detecte(s), %d corrige(s).', $totalIssues, $totalFixed));
            if (!$fix) {
                $io->comment('Relancer avec --fix pour corriger automatiquement.');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Build a lookup of "x.y" => movement value for all cells on a map.
     *
     * @return array<string, int>
     */
    private function buildCellMovements(Map $map): array
    {
        $areaWidth = $map->getAreaWidth();
        $areaHeight = $map->getAreaHeight();
        $movements = [];

        foreach ($map->getAreas() as $area) {
            $areaCoords = explode('.', $area->getCoordinates());
            $areaX = (int) $areaCoords[0];
            $areaY = (int) ($areaCoords[1] ?? 0);
            $areaMinGlobalX = $areaX * $areaWidth;
            $areaMinGlobalY = $areaY * $areaHeight;

            $areaData = $area->getFullDataArray();
            $cellsData = $areaData['cells'] ?? [];

            foreach ($cellsData as $lx => $column) {
                foreach ($column as $ly => $cellData) {
                    if ($cellData === null) {
                        continue;
                    }
                    $globalX = $areaMinGlobalX + ($cellData['x'] ?? $lx);
                    $globalY = $areaMinGlobalY + ($cellData['y'] ?? $ly);
                    $key = $globalX . '.' . $globalY;
                    $movements[$key] = $cellData['mouvement'] ?? 0;
                }
            }
        }

        return $movements;
    }

    /**
     * Find the nearest free cell (movement >= 0) using BFS spiral.
     */
    private function findNearestFreeCell(string $coords, array $cellMovements): ?string
    {
        [$x, $y] = explode('.', $coords);
        $x = (int) $x;
        $y = (int) $y;

        for ($radius = 1; $radius <= 10; ++$radius) {
            for ($dx = -$radius; $dx <= $radius; ++$dx) {
                for ($dy = -$radius; $dy <= $radius; ++$dy) {
                    if (abs($dx) !== $radius && abs($dy) !== $radius) {
                        continue; // only check perimeter of current radius
                    }
                    $candidate = ($x + $dx) . '.' . ($y + $dy);
                    if (isset($cellMovements[$candidate]) && $cellMovements[$candidate] !== CellHelper::MOVE_UNREACHABLE) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }
}
