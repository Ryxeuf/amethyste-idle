<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319DomainElement extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 6: Add element column to game_domains';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_domains ADD COLUMN IF NOT EXISTS element VARCHAR(25) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_domains DROP COLUMN IF EXISTS element');
    }
}
