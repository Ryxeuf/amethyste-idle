<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725ZoneMapPosition extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add zone.map_x / zone.map_y for the illustrated world map (pivot PBBG, ZON-16)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone ADD COLUMN IF NOT EXISTS map_x INT DEFAULT NULL');
        $this->addSql('ALTER TABLE zone ADD COLUMN IF NOT EXISTS map_y INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone DROP COLUMN IF EXISTS map_y');
        $this->addSql('ALTER TABLE zone DROP COLUMN IF EXISTS map_x');
    }
}
