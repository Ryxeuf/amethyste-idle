<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tache 132 : reference de classement par joueur et par onglet.
 *
 * Les trois classements agregent des compteurs cumulatifs que rien n'horodate
 * (`player_bestiary.kill_count`, quetes achevees, `domain_experience`). Le
 * classement « saisonnier » etait donc, en fait, le palmares de toute
 * l'histoire du serveur. La reference permet de le calculer en delta.
 *
 * Une ligne par (joueur, onglet), reecrite a chaque cloture : la table grossit
 * avec la population, jamais avec le nombre de saisons.
 */
final class Version20260726PlayerRankingBaseline extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create player_ranking_baseline (task 132, seasonal rankings)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_ranking_baseline (
                id SERIAL NOT NULL,
                player_id INT NOT NULL,
                tab VARCHAR(20) NOT NULL,
                baseline_value BIGINT NOT NULL,
                season_number INT NOT NULL,
                captured_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_ranking_baseline_player_tab ON player_ranking_baseline (player_id, tab)');

        // PostgreSQL ne connait pas ADD CONSTRAINT IF NOT EXISTS.
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_ranking_baseline_player') THEN
                    ALTER TABLE player_ranking_baseline
                        ADD CONSTRAINT fk_ranking_baseline_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
            END $$
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_ranking_baseline');
    }
}
