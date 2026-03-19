<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319ChatMessage extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create chat_message table for in-game chat system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS chat_message (
            id SERIAL PRIMARY KEY,
            channel VARCHAR(20) NOT NULL,
            content TEXT NOT NULL,
            sender_id INTEGER NOT NULL,
            recipient_id INTEGER DEFAULT NULL,
            map_id INTEGER DEFAULT NULL,
            is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
            deleted_by VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
            CONSTRAINT fk_chat_sender FOREIGN KEY (sender_id) REFERENCES player (id),
            CONSTRAINT fk_chat_recipient FOREIGN KEY (recipient_id) REFERENCES player (id),
            CONSTRAINT fk_chat_map FOREIGN KEY (map_id) REFERENCES map (id)
        )');

        $this->addSql('CREATE INDEX idx_chat_channel ON chat_message (channel)');
        $this->addSql('CREATE INDEX idx_chat_created_at ON chat_message (created_at)');
        $this->addSql('CREATE INDEX idx_chat_sender ON chat_message (sender_id)');
        $this->addSql('CREATE INDEX idx_chat_recipient ON chat_message (recipient_id)');
        $this->addSql('CREATE INDEX idx_chat_map ON chat_message (map_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS chat_message');
    }
}
