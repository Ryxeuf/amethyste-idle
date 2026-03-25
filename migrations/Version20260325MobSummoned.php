<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325MobSummoned extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add summoned boolean column to mob table for summoner monsters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mob ADD COLUMN IF NOT EXISTS summoned BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mob DROP COLUMN IF EXISTS summoned');
    }
}
