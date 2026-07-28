<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ce qu'un joueur depose dans un foyer (FOY-02).
 *
 * Une ligne par couple joueur/foyer — le compteur journalier porte sa date et
 * se remet a zero tout seul, sans tache planifiee. Les quatre colonnes de report
 * conservent le reste fractionnaire de la traversee (0,2 grain reparti sur
 * quatre indices), sans lequel une zone de transit n'accumulerait jamais rien.
 *
 * Le suffixe `D` trie cette migration apres `C` (creation de `settlement`),
 * qu'elle reference : Doctrine ordonne par nom de version, pas par heure de
 * creation (cf. CLAUDE.md).
 */
final class Version20260728DSettlementContribution extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Per-player sediment contribution to a settlement, with daily cap and fractional carry';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS settlement_contribution (
                id SERIAL NOT NULL,
                settlement_id INT NOT NULL,
                player_id INT NOT NULL,
                grains INT DEFAULT 0 NOT NULL,
                carry_trade DOUBLE PRECISION DEFAULT 0 NOT NULL,
                carry_war DOUBLE PRECISION DEFAULT 0 NOT NULL,
                carry_lore DOUBLE PRECISION DEFAULT 0 NOT NULL,
                carry_rite DOUBLE PRECISION DEFAULT 0 NOT NULL,
                daily_grains INT DEFAULT 0 NOT NULL,
                daily_date DATE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_settlement_contribution ON settlement_contribution (settlement_id, player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_settlement_contribution_player ON settlement_contribution (player_id)');

        // PostgreSQL n'a pas d'ADD CONSTRAINT IF NOT EXISTS (cf. CLAUDE.md).
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_settlement_contribution_settlement') THEN
                    ALTER TABLE settlement_contribution
                        ADD CONSTRAINT fk_settlement_contribution_settlement
                        FOREIGN KEY (settlement_id) REFERENCES settlement (id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_settlement_contribution_player') THEN
                    ALTER TABLE settlement_contribution
                        ADD CONSTRAINT fk_settlement_contribution_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
            END $$;
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS settlement_contribution');
    }
}
