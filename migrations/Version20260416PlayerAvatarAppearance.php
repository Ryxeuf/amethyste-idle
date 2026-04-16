<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416PlayerAvatarAppearance extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar columns to player (AVT-13 — avatar appearance, hash, version, updated_at)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS avatar_appearance JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS avatar_hash VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS avatar_version INTEGER NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS avatar_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN player.avatar_updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS avatar_appearance');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS avatar_hash');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS avatar_version');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS avatar_updated_at');
    }
}
