<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323WeatherGameplay extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add spawn_weather column on mob and queue_respawn_mob tables, and nocturnal on queue_respawn_mob';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mob ADD COLUMN IF NOT EXISTS spawn_weather VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE queue_respawn_mob ADD COLUMN IF NOT EXISTS spawn_weather VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE queue_respawn_mob ADD COLUMN IF NOT EXISTS nocturnal BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mob DROP COLUMN IF EXISTS spawn_weather');
        $this->addSql('ALTER TABLE queue_respawn_mob DROP COLUMN IF EXISTS spawn_weather');
        $this->addSql('ALTER TABLE queue_respawn_mob DROP COLUMN IF EXISTS nocturnal');
    }
}
