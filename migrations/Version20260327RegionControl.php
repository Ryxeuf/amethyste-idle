<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327RegionControl extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'GCC-10: Create region_control table for guild city control attribution';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS region_control (
                id SERIAL PRIMARY KEY,
                region_id INT NOT NULL,
                guild_id INT DEFAULT NULL,
                season_id INT NOT NULL,
                started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                ends_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                CONSTRAINT fk_region_control_region FOREIGN KEY (region_id) REFERENCES region(id) ON DELETE CASCADE,
                CONSTRAINT fk_region_control_guild FOREIGN KEY (guild_id) REFERENCES guild(id) ON DELETE SET NULL,
                CONSTRAINT fk_region_control_season FOREIGN KEY (season_id) REFERENCES influence_season(id) ON DELETE CASCADE
            )
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_region_control_active ON region_control (region_id, ends_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_region_control_season ON region_control (season_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_region_control_guild ON region_control (guild_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS region_control');
    }
}
