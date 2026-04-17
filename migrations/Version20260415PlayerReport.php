<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415PlayerReport extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player_report table (task 121 - basic report system with renown malus)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_report (
                id SERIAL PRIMARY KEY,
                reporter_id INTEGER NOT NULL,
                reported_player_id INTEGER NOT NULL,
                reviewed_by_id INTEGER DEFAULT NULL,
                reason VARCHAR(32) NOT NULL,
                description TEXT NOT NULL,
                status VARCHAR(16) NOT NULL DEFAULT 'pending',
                renown_malus_applied INTEGER NOT NULL DEFAULT 0,
                reviewed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                CONSTRAINT fk_player_report_reporter FOREIGN KEY (reporter_id) REFERENCES player(id) ON DELETE CASCADE,
                CONSTRAINT fk_player_report_reported FOREIGN KEY (reported_player_id) REFERENCES player(id) ON DELETE CASCADE,
                CONSTRAINT fk_player_report_reviewer FOREIGN KEY (reviewed_by_id) REFERENCES users(id) ON DELETE SET NULL
            )
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_report_reported_created ON player_report (reported_player_id, created_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_report_pair_created ON player_report (reporter_id, reported_player_id, created_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_report_status_created ON player_report (status, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_report');
    }
}
