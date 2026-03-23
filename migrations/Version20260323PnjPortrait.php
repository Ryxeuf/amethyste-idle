<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323PnjPortrait extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add portrait column to pnj table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pnj ADD COLUMN IF NOT EXISTS portrait VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pnj DROP COLUMN IF EXISTS portrait');
    }
}
