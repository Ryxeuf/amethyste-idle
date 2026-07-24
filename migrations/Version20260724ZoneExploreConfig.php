<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724ZoneExploreConfig extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add zone.explore_config JSON column (pivot PBBG, ZON-08 — declarative exploration table per zone)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone ADD COLUMN IF NOT EXISTS explore_config JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone DROP COLUMN IF EXISTS explore_config');
    }
}
