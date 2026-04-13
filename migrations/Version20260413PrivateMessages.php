<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413PrivateMessages extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create private_message table and add blocked_players to player';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS private_message (
            id SERIAL PRIMARY KEY,
            sender_id INT NOT NULL REFERENCES player(id),
            receiver_id INT NOT NULL REFERENCES player(id),
            subject VARCHAR(100) NOT NULL,
            body TEXT NOT NULL,
            read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pm_receiver_created ON private_message (receiver_id, created_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pm_sender_created ON private_message (sender_id, created_at)');

        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS blocked_players JSON DEFAULT \'[]\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS private_message');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS blocked_players');
    }
}
