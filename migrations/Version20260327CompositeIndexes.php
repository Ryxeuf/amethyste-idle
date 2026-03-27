<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327CompositeIndexes extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite indexes on critical tables for query performance';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_chat_channel_created ON chat_message (channel, created_at)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_chat_guild_created ON chat_message (guild_id, created_at)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_player_item_inventory_item ON player_item (inventory_id, item_id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_player_status_effect_player_expires ON player_status_effects (player_id, expires_at)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_chat_channel_created');
        $this->addSql('DROP INDEX IF EXISTS idx_chat_guild_created');
        $this->addSql('DROP INDEX IF EXISTS idx_player_item_inventory_item');
        $this->addSql('DROP INDEX IF EXISTS idx_player_status_effect_player_expires');
    }
}
