<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La commission hebdomadaire personnelle (RET-02).
 *
 * L'unicite sur (joueur, semaine) est la regle anti-reroll, tenue par la base
 * plutot que par le code : une contrainte applicative se contourne par une
 * requete concurrente, une contrainte d'unicite non.
 *
 * `delivery_zone_id` est `SET NULL` : retirer une zone du monde ne doit pas
 * effacer la commission d'un joueur, seulement lui retirer sa destination.
 */
final class Version20260728GPlayerWeeklyCommission extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Personal weekly commission: one per character per week, delivered to a settlement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_weekly_commission (
                id SERIAL NOT NULL,
                player_id INT NOT NULL,
                delivery_zone_id INT DEFAULT NULL,
                week_key VARCHAR(10) NOT NULL,
                template_slug VARCHAR(64) NOT NULL,
                activity VARCHAR(20) NOT NULL,
                target INT NOT NULL,
                progress INT DEFAULT 0 NOT NULL,
                status VARCHAR(20) DEFAULT 'open' NOT NULL,
                delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_player_weekly_commission ON player_weekly_commission (player_id, week_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_weekly_commission_status ON player_weekly_commission (status, week_key)');

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_weekly_commission_player') THEN
                    ALTER TABLE player_weekly_commission
                        ADD CONSTRAINT fk_player_weekly_commission_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_weekly_commission_zone') THEN
                    ALTER TABLE player_weekly_commission
                        ADD CONSTRAINT fk_player_weekly_commission_zone
                        FOREIGN KEY (delivery_zone_id) REFERENCES zone (id) ON DELETE SET NULL;
                END IF;
            END $$;
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_weekly_commission');
    }
}
