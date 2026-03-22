<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322OptimizeNPlusOneIndexes extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite indexes to optimize N+1 query patterns on mob and player tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_mob_map ON mob (map_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_map ON player (map_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_mob_map');
        $this->addSql('DROP INDEX IF EXISTS idx_player_map');
    }
}
