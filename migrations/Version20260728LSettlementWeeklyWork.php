<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le chantier de la semaine d'un foyer, et qui l'a rempli (RET-05).
 *
 * **Les deux tables dans une seule migration**, volontairement : la table des
 * contributions reference celle des chantiers, et Doctrine trie les migrations
 * par ordre **alphabetique** du nom de version, pas par heure de creation. Deux
 * fichiers du meme jour auraient pu s'executer dans le mauvais ordre — le defaut
 * vu en production le 2026-07-27 (`GardenPlot` avant `PlayerHouse`). Les tenir
 * ensemble rend l'ordre impossible a casser.
 *
 * Les besoins vivent en JSON sur le chantier : ils sont lus et ecrits ensemble,
 * ne se referencent nulle part, et meurent avec la semaine. Les contributions
 * ont leur table parce qu'elles sont **nominatives** — un chantier rempli doit
 * pouvoir dire qui l'a rempli.
 */
final class Version20260728LSettlementWeeklyWork extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Weekly settlement work: what the settlement asks for this week, and who answered';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS settlement_weekly_work (
            id SERIAL PRIMARY KEY,
            settlement_id INT NOT NULL,
            week_key VARCHAR(10) NOT NULL,
            needs JSON NOT NULL,
            completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_settlement_weekly_work ON settlement_weekly_work (settlement_id, week_key)');

        $this->addSql('CREATE TABLE IF NOT EXISTS settlement_weekly_work_contribution (
            id SERIAL PRIMARY KEY,
            work_id INT NOT NULL,
            player_id INT NOT NULL,
            units INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_settlement_weekly_work_contribution ON settlement_weekly_work_contribution (work_id, player_id)');

        // `ADD CONSTRAINT IF NOT EXISTS` n'existe pas en PostgreSQL : le bloc
        // conditionnel est la seule facon d'ecrire une contrainte idempotente.
        $this->addSql("DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_settlement_weekly_work_settlement') THEN
                ALTER TABLE settlement_weekly_work ADD CONSTRAINT fk_settlement_weekly_work_settlement
                    FOREIGN KEY (settlement_id) REFERENCES settlement (id) ON DELETE CASCADE;
            END IF;
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_settlement_weekly_work_contribution_work') THEN
                ALTER TABLE settlement_weekly_work_contribution ADD CONSTRAINT fk_settlement_weekly_work_contribution_work
                    FOREIGN KEY (work_id) REFERENCES settlement_weekly_work (id) ON DELETE CASCADE;
            END IF;
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_settlement_weekly_work_contribution_player') THEN
                ALTER TABLE settlement_weekly_work_contribution ADD CONSTRAINT fk_settlement_weekly_work_contribution_player
                    FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
            END IF;
        END $$");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS settlement_weekly_work_contribution');
        $this->addSql('DROP TABLE IF EXISTS settlement_weekly_work');
    }
}
