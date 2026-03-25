<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326PnjSchedule extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create pnj_schedule table for PNJ daily routines';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS pnj_schedule (
            id SERIAL PRIMARY KEY,
            pnj_id INT NOT NULL,
            hour INT NOT NULL,
            coordinates VARCHAR(20) NOT NULL,
            map_id INT NOT NULL,
            label VARCHAR(255) DEFAULT NULL,
            CONSTRAINT fk_pnj_schedule_pnj FOREIGN KEY (pnj_id) REFERENCES pnj(id) ON DELETE CASCADE,
            CONSTRAINT fk_pnj_schedule_map FOREIGN KEY (map_id) REFERENCES map(id)
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pnj_schedule_pnj ON pnj_schedule (pnj_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pnj_schedule_hour ON pnj_schedule (hour)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS pnj_schedule');
    }
}
