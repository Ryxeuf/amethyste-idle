<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * L'assiduite hebdomadaire, en paliers et jamais en serie (RET-04).
 *
 * Une ligne par personnage et par semaine ISO : il n'y a **rien a remettre a
 * zero**, une semaine nouvelle est une ligne nouvelle. C'est la forme qui
 * garantit l'interdit du plan — aucune mecanique de serie continue entre
 * semaines — plutot qu'une regle qu'un jalon ulterieur pourrait oublier.
 */
final class Version20260728OPlayerWeeklyAttendance extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Weekly attendance tiers: distinct active days per ISO week, never a streak';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_weekly_attendance (
                id SERIAL NOT NULL,
                player_id INT NOT NULL,
                week_key VARCHAR(10) NOT NULL,
                active_days INT DEFAULT 0 NOT NULL,
                last_active_day VARCHAR(10) DEFAULT NULL,
                granted_tier_days INT DEFAULT 0 NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_weekly_attendance_player ON player_weekly_attendance (player_id)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_player_weekly_attendance ON player_weekly_attendance (player_id, week_key)');

        // `ADD CONSTRAINT IF NOT EXISTS` n'existe pas en PostgreSQL (piege
        // documente dans CLAUDE.md) : le bloc DO rend l'ajout idempotent.
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_weekly_attendance_player') THEN
                    ALTER TABLE player_weekly_attendance
                        ADD CONSTRAINT fk_player_weekly_attendance_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
            END $$
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_weekly_attendance');
    }
}
