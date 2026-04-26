<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260426MetricsCollectorIndexes extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes used by /metrics collectors: player(updated_at) and mob(map_id) WHERE died_at IS NULL (task 134 sous-phase 3a, jalon C partiel)';
    }

    public function up(Schema $schema): void
    {
        // Accelere COUNT(p.id) FROM player p WHERE p.updatedAt >= NOW() - 15min
        // (gauge `players_online` collecte par MetricsController::collectGameGauges).
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_updated_at ON player (updated_at)');

        // Partial index pour COUNT(m.id) FROM mob m WHERE m.diedAt IS NULL
        // (gauge `mobs_alive` collecte par MetricsController::collectGameGauges).
        // Couvre aussi les requetes de spawn / pathfinding qui filtrent sur les mobs vivants
        // par carte.
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_mob_alive_map ON mob (map_id) WHERE died_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_player_updated_at');
        $this->addSql('DROP INDEX IF EXISTS idx_mob_alive_map');
    }
}
