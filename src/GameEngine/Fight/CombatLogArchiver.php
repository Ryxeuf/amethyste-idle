<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use Psr\Log\LoggerInterface;

class CombatLogArchiver
{
    public function __construct(
        private readonly CombatLogger $combatLogger,
        private readonly LoggerInterface $logger,
        private readonly string $archiveDir,
    ) {
    }

    public function archive(Fight $fight): ?string
    {
        $logs = $this->combatLogger->getLogsForFight($fight);

        if (empty($logs)) {
            return null;
        }

        $playerNames = [];
        foreach ($fight->getPlayers() as $player) {
            $playerNames[] = $player->getName();
        }

        $mobNames = [];
        foreach ($fight->getMobs() as $mob) {
            $mobNames[] = $mob->getName();
        }

        $outcome = $fight->isVictory() ? 'victory' : ($fight->isDefeat() ? 'defeat' : 'unknown');

        $archive = [
            'fight_id' => $fight->getId(),
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'outcome' => $outcome,
            'total_turns' => $fight->getStep(),
            'players' => $playerNames,
            'mobs' => $mobNames,
            'logs' => [],
        ];

        foreach ($logs as $log) {
            $archive['logs'][] = [
                'turn' => $log->getTurn(),
                'actor_type' => $log->getActorType(),
                'actor_name' => $log->getActorName(),
                'type' => $log->getType(),
                'message' => $log->getMessage(),
                'metadata' => $log->getMetadata(),
                'created_at' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        $dir = $this->archiveDir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $this->logger->error('Impossible de creer le dossier d\'archivage des combats', ['dir' => $dir]);

            return null;
        }

        $filename = sprintf(
            '%s/%s_fight_%d_%s.json',
            $dir,
            (new \DateTime())->format('Y-m-d_His'),
            $fight->getId(),
            mb_substr(implode('-', $playerNames), 0, 50)
        );

        $written = file_put_contents(
            $filename,
            json_encode($archive, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE)
        );

        if ($written === false) {
            $this->logger->error('Impossible d\'ecrire l\'archive du combat', ['filename' => $filename]);

            return null;
        }

        return $filename;
    }
}
