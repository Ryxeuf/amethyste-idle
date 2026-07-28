<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le foyer d'une zone (FOY-01).
 *
 * Un foyer par zone au plus — d'ou l'unicite sur `zone_id`. Toutes les zones
 * n'en ont pas : Lumiere et les Jardins sont batis sur la Voute, la Cite
 * ensevelie est un donjon. L'absence est une decision documentee dans
 * `config/game/settlements.yaml`, pas un trou de donnees.
 *
 * Quatre colonnes de sediment plutot qu'une : elles decroissent
 * independamment, le rang se lit sur leur somme et le type sur le dominant.
 *
 * Le suffixe `C` trie cette migration apres celles du meme jour (`A` charge du
 * monde, `B` repousse des filons) : Doctrine ordonne par nom de version, pas
 * par heure de creation (cf. CLAUDE.md).
 */
final class Version20260728CSettlement extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Settlement of a zone: rank, type and the four sediment indices';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS settlement (
                id SERIAL NOT NULL,
                zone_id INT NOT NULL,
                rank VARCHAR(20) DEFAULT 'ruin' NOT NULL,
                type VARCHAR(20) DEFAULT NULL,
                sediment_trade INT DEFAULT 0 NOT NULL,
                sediment_war INT DEFAULT 0 NOT NULL,
                sediment_lore INT DEFAULT 0 NOT NULL,
                sediment_rite INT DEFAULT 0 NOT NULL,
                highest_rank VARCHAR(20) DEFAULT 'ruin' NOT NULL,
                ranked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                decayed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                dominant_since TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_settlement_zone ON settlement (zone_id)');

        // PostgreSQL n'a pas d'ADD CONSTRAINT IF NOT EXISTS (cf. CLAUDE.md).
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_settlement_zone') THEN
                    ALTER TABLE settlement
                        ADD CONSTRAINT fk_settlement_zone
                        FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE;
                END IF;
            END $$;
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS settlement');
    }
}
