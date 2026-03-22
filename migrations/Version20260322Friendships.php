<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322Friendships extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create friendships table for friend system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS friendships (
            id SERIAL PRIMARY KEY,
            player_id INT NOT NULL,
            friend_id INT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_friendship_player FOREIGN KEY (player_id) REFERENCES player(id) ON DELETE CASCADE,
            CONSTRAINT fk_friendship_friend FOREIGN KEY (friend_id) REFERENCES player(id) ON DELETE CASCADE,
            CONSTRAINT friendship_unique UNIQUE (player_id, friend_id),
            CONSTRAINT friendship_not_self CHECK (player_id != friend_id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_friendship_player ON friendships (player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_friendship_friend ON friendships (friend_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_friendship_status ON friendships (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS friendships');
    }
}
