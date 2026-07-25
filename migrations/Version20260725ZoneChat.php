<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725ZoneChat extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add chat_message.zone_id for the zone chat channel (pivot PBBG, ZON-14)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_message ADD COLUMN IF NOT EXISTS zone_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_chat_zone_created ON chat_message (zone_id, created_at)');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_chat_message_zone') THEN
                    ALTER TABLE chat_message
                        ADD CONSTRAINT fk_chat_message_zone
                        FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_message DROP CONSTRAINT IF EXISTS fk_chat_message_zone');
        $this->addSql('DROP INDEX IF EXISTS idx_chat_zone_created');
        $this->addSql('ALTER TABLE chat_message DROP COLUMN IF EXISTS zone_id');
    }
}
