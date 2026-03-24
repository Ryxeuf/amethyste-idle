<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324Guilds extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create guild, guild_member and guild_invitation tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS guild (
            id SERIAL PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            tag VARCHAR(5) NOT NULL,
            description TEXT DEFAULT NULL,
            leader_id INT NOT NULL REFERENCES player(id),
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS guild_name_unique ON guild (name)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS guild_tag_unique ON guild (tag)');

        $this->addSql('CREATE TABLE IF NOT EXISTS guild_member (
            id SERIAL PRIMARY KEY,
            guild_id INT NOT NULL REFERENCES guild(id) ON DELETE CASCADE,
            player_id INT NOT NULL REFERENCES player(id) ON DELETE CASCADE,
            rank VARCHAR(20) NOT NULL DEFAULT \'recruit\',
            joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS guild_member_player_unique ON guild_member (player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_guild_member_guild ON guild_member (guild_id)');

        $this->addSql('CREATE TABLE IF NOT EXISTS guild_invitation (
            id SERIAL PRIMARY KEY,
            guild_id INT NOT NULL REFERENCES guild(id) ON DELETE CASCADE,
            player_id INT NOT NULL REFERENCES player(id) ON DELETE CASCADE,
            invited_by_id INT NOT NULL REFERENCES player(id),
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'guild_invitation_unique') THEN
                    ALTER TABLE guild_invitation ADD CONSTRAINT guild_invitation_unique UNIQUE (guild_id, player_id);
                END IF;
            END $$
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS guild_invitation');
        $this->addSql('DROP TABLE IF EXISTS guild_member');
        $this->addSql('DROP TABLE IF EXISTS guild');
    }
}
