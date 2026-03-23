<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323Weather extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add current_weather and weather_changed_at columns to map table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE map ADD COLUMN IF NOT EXISTS current_weather VARCHAR(20) NOT NULL DEFAULT 'sunny'");
        $this->addSql('ALTER TABLE map ADD COLUMN IF NOT EXISTS weather_changed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map DROP COLUMN IF EXISTS weather_changed_at');
        $this->addSql('ALTER TABLE map DROP COLUMN IF EXISTS current_weather');
    }
}
