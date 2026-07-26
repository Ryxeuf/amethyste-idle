<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tache 133 : parcours chronometres asynchrones.
 *
 * Reformulation PBBG des « courses entre joueurs » : sans deplacement en
 * tuiles, il n'y a plus rien a courir en direct. Chacun rallie la meme suite
 * de zones quand il veut, et seuls les temps se comparent.
 */
final class Version20260726TimeTrial extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create time_trial and time_trial_run (task 133)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS time_trial (
                id SERIAL NOT NULL,
                start_zone_id INT NOT NULL,
                slug VARCHAR(60) NOT NULL,
                name VARCHAR(120) NOT NULL,
                name_translations JSON DEFAULT NULL,
                description TEXT NOT NULL,
                description_translations JSON DEFAULT NULL,
                checkpoints JSON NOT NULL,
                energy_cost INT DEFAULT 5 NOT NULL,
                time_limit_seconds INT DEFAULT 86400 NOT NULL,
                enabled BOOLEAN DEFAULT true NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_time_trial_slug ON time_trial (slug)');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS time_trial_run (
                id SERIAL NOT NULL,
                player_id INT NOT NULL,
                trial_id INT NOT NULL,
                status VARCHAR(20) NOT NULL,
                reached_index INT DEFAULT 0 NOT NULL,
                started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                elapsed_seconds INT DEFAULT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_time_trial_run_board ON time_trial_run (trial_id, elapsed_seconds)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_time_trial_run_player_status ON time_trial_run (player_id, status)');

        // PostgreSQL ne connait pas ADD CONSTRAINT IF NOT EXISTS.
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_time_trial_start_zone') THEN
                    ALTER TABLE time_trial
                        ADD CONSTRAINT fk_time_trial_start_zone
                        FOREIGN KEY (start_zone_id) REFERENCES zone (id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_time_trial_run_player') THEN
                    ALTER TABLE time_trial_run
                        ADD CONSTRAINT fk_time_trial_run_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_time_trial_run_trial') THEN
                    ALTER TABLE time_trial_run
                        ADD CONSTRAINT fk_time_trial_run_trial
                        FOREIGN KEY (trial_id) REFERENCES time_trial (id) ON DELETE CASCADE;
                END IF;
            END $$
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS time_trial_run');
        $this->addSql('DROP TABLE IF EXISTS time_trial');
    }
}
