<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * REP-01 — les lectures contextees.
 *
 * Trois choses : le **decompte** par contexte (le souvenir du serveur, qui ne
 * nomme aucun joueur), le **plafond** anti-forcage sur le joueur (qui ne peut
 * pas vivre dans le decompte, justement parce que celui-ci ignore les joueurs),
 * et la **provenance** sur la piece, la ou le monde la donne.
 */
final class Version20260819DRepReadings extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'REP-01 : decompte contexte des lectures, plafond journalier, provenance du butin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS repertoire_reading (
                id SERIAL PRIMARY KEY,
                week_key VARCHAR(16) NOT NULL,
                element VARCHAR(32) NOT NULL,
                provenance_zone_id INT DEFAULT NULL,
                reading_zone_id INT NOT NULL,
                settlement_rank VARCHAR(32) DEFAULT NULL,
                tally INT DEFAULT 0 NOT NULL
            )
            SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_repertoire_reading_week ON repertoire_reading (week_key)');

        // La contrainte porte les cinq colonnes du contexte. En PostgreSQL, une
        // contrainte unique traite deux NULL comme distincts : sans index
        // partiel, une provenance inconnue ouvrirait une ligne neuve a chaque
        // lecture au lieu d'incrementer celle des inconnues. D'ou l'index
        // unique sur `COALESCE`, qui range tous les inconnus ensemble.
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS uq_repertoire_reading_context
            ON repertoire_reading (week_key, element, COALESCE(provenance_zone_id, 0), reading_zone_id, COALESCE(settlement_rank, ''))
            SQL);

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_repertoire_reading_provenance') THEN
                    ALTER TABLE repertoire_reading
                        ADD CONSTRAINT fk_repertoire_reading_provenance
                        FOREIGN KEY (provenance_zone_id) REFERENCES zone (id) ON DELETE SET NULL;
                END IF;
            END $$
            SQL);

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_repertoire_reading_zone') THEN
                    ALTER TABLE repertoire_reading
                        ADD CONSTRAINT fk_repertoire_reading_zone
                        FOREIGN KEY (reading_zone_id) REFERENCES zone (id) ON DELETE CASCADE;
                END IF;
            END $$
            SQL);

        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS daily_repertoire_key VARCHAR(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS daily_repertoire_readings INT DEFAULT 0 NOT NULL');

        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS origin_zone_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_item DROP COLUMN IF EXISTS origin_zone_id');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS daily_repertoire_readings');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS daily_repertoire_key');
        $this->addSql('DROP TABLE IF EXISTS repertoire_reading');
    }
}
