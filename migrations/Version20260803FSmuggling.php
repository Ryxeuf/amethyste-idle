<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * FAC-08 — les contrats de contrebande des Ruelles.
 *
 * La cargaison vit dans le contrat, jamais dans l'inventaire : la
 * confiscation aux portes d'un Bastion prend le ballot, jamais le sac.
 */
final class Version20260803FSmuggling extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FAC-08: smuggling contracts of the Alleys (cargo lives in the contract, never the bag)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS smuggling_contract (
                id SERIAL PRIMARY KEY,
                player_id INT NOT NULL REFERENCES player (id) ON DELETE CASCADE,
                week_key VARCHAR(10) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'in_transit',
                cargo_label VARCHAR(120) NOT NULL,
                origin_zone_slug VARCHAR(64) NOT NULL,
                destination_zone_slug VARCHAR(64) NOT NULL,
                reward_gils INT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
            )
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_smuggling_player_week ON smuggling_contract (player_id, week_key)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS smuggling_contract');
    }
}
