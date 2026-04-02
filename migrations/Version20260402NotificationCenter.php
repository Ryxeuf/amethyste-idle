<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402NotificationCenter extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Upgrade player_notification table: add title, icon, link, read_at columns; change type to varchar';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_notification ADD COLUMN IF NOT EXISTS title VARCHAR(255)');
        $this->addSql('ALTER TABLE player_notification ADD COLUMN IF NOT EXISTS icon VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE player_notification ADD COLUMN IF NOT EXISTS link VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE player_notification ADD COLUMN IF NOT EXISTS read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // Backfill title for existing rows
        $this->addSql("UPDATE player_notification SET title = 'Notification' WHERE title IS NULL");
        $this->addSql('ALTER TABLE player_notification ALTER COLUMN title SET NOT NULL');

        // Change type from integer to varchar
        $this->addSql('ALTER TABLE player_notification ALTER COLUMN type TYPE VARCHAR(50) USING type::VARCHAR(50)');
        $this->addSql("UPDATE player_notification SET type = 'system' WHERE type IS NULL OR type = ''");
        $this->addSql('ALTER TABLE player_notification ALTER COLUMN type SET NOT NULL');

        // Add ON DELETE CASCADE to player FK if not already set
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_notification_player_id') THEN
                    ALTER TABLE player_notification DROP CONSTRAINT fk_player_notification_player_id;
                END IF;
            END $$
        SQL);

        $this->addSql('ALTER TABLE player_notification ALTER COLUMN player_id SET NOT NULL');

        // Index for unread notifications lookup
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_notification_unread ON player_notification (player_id, read_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_player_notification_unread');
        $this->addSql('ALTER TABLE player_notification DROP COLUMN IF EXISTS read_at');
        $this->addSql('ALTER TABLE player_notification DROP COLUMN IF EXISTS link');
        $this->addSql('ALTER TABLE player_notification DROP COLUMN IF EXISTS icon');
        $this->addSql('ALTER TABLE player_notification DROP COLUMN IF EXISTS title');
        $this->addSql('ALTER TABLE player_notification ALTER COLUMN type TYPE INTEGER USING 0');
    }
}
