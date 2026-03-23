<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323DayNightGameplay extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add day/night gameplay fields: nocturnal on mob, night_only on object_layer, opens_at/closes_at on pnj';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mob ADD COLUMN IF NOT EXISTS nocturnal BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE object_layer ADD COLUMN IF NOT EXISTS night_only BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE pnj ADD COLUMN IF NOT EXISTS opens_at INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE pnj ADD COLUMN IF NOT EXISTS closes_at INTEGER DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mob DROP COLUMN IF EXISTS nocturnal');
        $this->addSql('ALTER TABLE object_layer DROP COLUMN IF EXISTS night_only');
        $this->addSql('ALTER TABLE pnj DROP COLUMN IF EXISTS opens_at');
        $this->addSql('ALTER TABLE pnj DROP COLUMN IF EXISTS closes_at');
    }
}
