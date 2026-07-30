<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-13 — le foyer d'attache, et de quoi le constater.
 *
 * `home_zone_id` naît nul partout, et c'est la bonne valeur : les joueurs
 * existants ont traverse un acte I qui ne mesurait rien, et leur inventer
 * retroactivement un foyer serait leur attribuer un travail qu'ils n'ont pas
 * fait. Ils en gagneront un a la cloture, ou resteront sans — le Fanal etant le
 * defaut de fait partout ou le foyer est lu.
 *
 * Le suffixe `E` la trie apres les quatre migrations du meme jour (cf. la
 * section « Pieges courants » de CLAUDE.md).
 */
final class Version20260730EOnbHomeSettlement extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ONB-13: player.home_zone_id + player_zone_activity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS home_zone_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS home_zone_claimed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_home_zone ON player (home_zone_id)');

        // PostgreSQL ne connait pas `ADD CONSTRAINT IF NOT EXISTS`.
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_home_zone') THEN
                    ALTER TABLE player ADD CONSTRAINT fk_player_home_zone
                        FOREIGN KEY (home_zone_id) REFERENCES zone (id) ON DELETE SET NULL;
                END IF;
            END $$;
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_zone_activity (
                id SERIAL PRIMARY KEY,
                player_id INT NOT NULL,
                zone_id INT NOT NULL,
                acts INT NOT NULL DEFAULT 0,
                last_act_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS player_zone_activity_unique ON player_zone_activity (player_id, zone_id)');

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_zone_activity_player') THEN
                    ALTER TABLE player_zone_activity ADD CONSTRAINT fk_player_zone_activity_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_zone_activity_zone') THEN
                    ALTER TABLE player_zone_activity ADD CONSTRAINT fk_player_zone_activity_zone
                        FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE;
                END IF;
            END $$;
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_zone_activity');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS home_zone_claimed_at');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS home_zone_id');
    }
}
