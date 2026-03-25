<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325GuildVault extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create guild_vault and guild_vault_log tables, add guild_vault_id column to player_item';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS guild_vault (
            id SERIAL PRIMARY KEY,
            guild_id INT NOT NULL REFERENCES guild(id) ON DELETE CASCADE,
            max_slots INT NOT NULL DEFAULT 30,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS guild_vault_guild_unique ON guild_vault (guild_id)');

        $this->addSql('CREATE TABLE IF NOT EXISTS guild_vault_log (
            id SERIAL PRIMARY KEY,
            guild_id INT NOT NULL REFERENCES guild(id) ON DELETE CASCADE,
            player_id INT NOT NULL REFERENCES player(id),
            action VARCHAR(10) NOT NULL,
            item_id INT NOT NULL REFERENCES game_items(id),
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_guild_vault_log_guild ON guild_vault_log (guild_id)');

        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS guild_vault_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_item_guild_vault') THEN
                    ALTER TABLE player_item ADD CONSTRAINT fk_player_item_guild_vault
                        FOREIGN KEY (guild_vault_id) REFERENCES guild_vault(id) ON DELETE SET NULL;
                END IF;
            END $$
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_item DROP COLUMN IF EXISTS guild_vault_id');
        $this->addSql('DROP TABLE IF EXISTS guild_vault_log');
        $this->addSql('DROP TABLE IF EXISTS guild_vault');
    }
}
