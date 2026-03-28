<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328GuildTreasury extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gils_treasury column to guild table for region tax collection';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE guild ADD COLUMN IF NOT EXISTS gils_treasury INT NOT NULL DEFAULT 0
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guild DROP COLUMN IF EXISTS gils_treasury');
    }
}
