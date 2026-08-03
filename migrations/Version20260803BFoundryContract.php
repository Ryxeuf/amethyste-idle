<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * FAC-05 — les contrats d'approvisionnement de la Fonderie.
 *
 * Une ligne de contrat par semaine (cle unique), et une ligne d'honneur par
 * couple (contrat, joueur). Les deux tables naissent dans la meme migration :
 * la seconde reference la premiere, l'ordre est garanti par le fichier.
 */
final class Version20260803BFoundryContract extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FAC-05: weekly foundry supply contract + per-player fulfillment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS foundry_contract (
                id SERIAL PRIMARY KEY,
                week_key VARCHAR(10) NOT NULL,
                item_slug VARCHAR(64) NOT NULL,
                volume INT NOT NULL,
                gils_per_unit INT NOT NULL,
                essence INT NOT NULL,
                reference_price INT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_foundry_contract_week ON foundry_contract (week_key)');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS foundry_contract_fulfillment (
                id SERIAL PRIMARY KEY,
                contract_id INT NOT NULL REFERENCES foundry_contract (id) ON DELETE CASCADE,
                player_id INT NOT NULL REFERENCES player (id) ON DELETE CASCADE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_foundry_fulfillment ON foundry_contract_fulfillment (contract_id, player_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS foundry_contract_fulfillment');
        $this->addSql('DROP TABLE IF EXISTS foundry_contract');
    }
}
