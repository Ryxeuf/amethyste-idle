<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324GuildChat extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add guild_id column to chat_message for guild chat channel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_message ADD COLUMN IF NOT EXISTS guild_id INTEGER DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_chat_message_guild') THEN
                    ALTER TABLE chat_message ADD CONSTRAINT fk_chat_message_guild
                        FOREIGN KEY (guild_id) REFERENCES guild(id) ON DELETE CASCADE;
                END IF;
            END $$
        SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_guild ON chat_message (guild_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_message DROP CONSTRAINT IF EXISTS fk_chat_message_guild');
        $this->addSql('DROP INDEX IF EXISTS idx_chat_guild');
        $this->addSql('ALTER TABLE chat_message DROP COLUMN IF EXISTS guild_id');
    }
}
