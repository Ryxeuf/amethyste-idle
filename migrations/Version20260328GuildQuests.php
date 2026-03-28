<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328GuildQuests extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create guild_quest table for weekly guild quests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS guild_quest (
                id SERIAL PRIMARY KEY,
                guild_id INT NOT NULL REFERENCES guild(id) ON DELETE CASCADE,
                type VARCHAR(20) NOT NULL,
                target VARCHAR(100) NOT NULL,
                target_label VARCHAR(150) NOT NULL,
                progress INT NOT NULL DEFAULT 0,
                goal INT NOT NULL,
                gils_reward INT NOT NULL,
                points_reward INT NOT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_guild_quest_guild ON guild_quest (guild_id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_guild_quest_expires ON guild_quest (expires_at)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS guild_quest');
    }
}
