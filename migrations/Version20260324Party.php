<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324Party extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create party, party_member and party_invitation tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS party (
            id SERIAL PRIMARY KEY,
            leader_id INT NOT NULL REFERENCES player(id),
            max_size INT NOT NULL DEFAULT 4,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_party_leader ON party (leader_id)');

        $this->addSql('CREATE TABLE IF NOT EXISTS party_member (
            id SERIAL PRIMARY KEY,
            party_id INT NOT NULL REFERENCES party(id) ON DELETE CASCADE,
            player_id INT NOT NULL REFERENCES player(id) ON DELETE CASCADE,
            joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS party_member_player_unique ON party_member (player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_party_member_party ON party_member (party_id)');

        $this->addSql('CREATE TABLE IF NOT EXISTS party_invitation (
            id SERIAL PRIMARY KEY,
            party_id INT NOT NULL REFERENCES party(id) ON DELETE CASCADE,
            player_id INT NOT NULL REFERENCES player(id) ON DELETE CASCADE,
            invited_by_id INT NOT NULL REFERENCES player(id),
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'party_invitation_unique') THEN
                    ALTER TABLE party_invitation ADD CONSTRAINT party_invitation_unique UNIQUE (party_id, player_id);
                END IF;
            END $$
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS party_invitation');
        $this->addSql('DROP TABLE IF EXISTS party_member');
        $this->addSql('DROP TABLE IF EXISTS party');
    }
}
