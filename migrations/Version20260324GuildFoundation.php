<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324GuildFoundation extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create guilds, guild_members and guild_invitations tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS guilds (
            id SERIAL PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            tag VARCHAR(5) NOT NULL,
            description TEXT DEFAULT NULL,
            leader_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_guilds_leader FOREIGN KEY (leader_id) REFERENCES player(id),
            CONSTRAINT uq_guilds_name UNIQUE (name),
            CONSTRAINT uq_guilds_tag UNIQUE (tag)
        )');

        $this->addSql('CREATE TABLE IF NOT EXISTS guild_members (
            id SERIAL PRIMARY KEY,
            guild_id INT NOT NULL,
            player_id INT NOT NULL,
            rank VARCHAR(20) NOT NULL DEFAULT \'recruit\',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_guild_members_guild FOREIGN KEY (guild_id) REFERENCES guilds(id) ON DELETE CASCADE,
            CONSTRAINT fk_guild_members_player FOREIGN KEY (player_id) REFERENCES player(id) ON DELETE CASCADE,
            CONSTRAINT guild_member_unique UNIQUE (player_id)
        )');

        $this->addSql('CREATE TABLE IF NOT EXISTS guild_invitations (
            id SERIAL PRIMARY KEY,
            guild_id INT NOT NULL,
            player_id INT NOT NULL,
            invited_by_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_guild_invitations_guild FOREIGN KEY (guild_id) REFERENCES guilds(id) ON DELETE CASCADE,
            CONSTRAINT fk_guild_invitations_player FOREIGN KEY (player_id) REFERENCES player(id) ON DELETE CASCADE,
            CONSTRAINT fk_guild_invitations_invited_by FOREIGN KEY (invited_by_id) REFERENCES player(id),
            CONSTRAINT guild_invitation_unique UNIQUE (guild_id, player_id)
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_guild_members_guild ON guild_members(guild_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_guild_invitations_player ON guild_invitations(player_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS guild_invitations');
        $this->addSql('DROP TABLE IF EXISTS guild_members');
        $this->addSql('DROP TABLE IF EXISTS guilds');
    }
}
