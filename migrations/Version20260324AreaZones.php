<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324AreaZones extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add biome, weather, music, light_level and zone bounds columns to area table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE area ADD COLUMN IF NOT EXISTS biome VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE area ADD COLUMN IF NOT EXISTS weather VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE area ADD COLUMN IF NOT EXISTS music VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE area ADD COLUMN IF NOT EXISTS light_level DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE area ADD COLUMN IF NOT EXISTS zone_x INT DEFAULT NULL');
        $this->addSql('ALTER TABLE area ADD COLUMN IF NOT EXISTS zone_y INT DEFAULT NULL');
        $this->addSql('ALTER TABLE area ADD COLUMN IF NOT EXISTS zone_width INT DEFAULT NULL');
        $this->addSql('ALTER TABLE area ADD COLUMN IF NOT EXISTS zone_height INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE area DROP COLUMN IF EXISTS biome');
        $this->addSql('ALTER TABLE area DROP COLUMN IF EXISTS weather');
        $this->addSql('ALTER TABLE area DROP COLUMN IF EXISTS music');
        $this->addSql('ALTER TABLE area DROP COLUMN IF EXISTS light_level');
        $this->addSql('ALTER TABLE area DROP COLUMN IF EXISTS zone_x');
        $this->addSql('ALTER TABLE area DROP COLUMN IF EXISTS zone_y');
        $this->addSql('ALTER TABLE area DROP COLUMN IF EXISTS zone_width');
        $this->addSql('ALTER TABLE area DROP COLUMN IF EXISTS zone_height');
    }
}
