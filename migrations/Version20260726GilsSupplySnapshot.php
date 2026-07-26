<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Releve de masse monetaire (ECO-15).
 */
final class Version20260726GilsSupplySnapshot extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ECO-15 : table gils_supply_snapshot (masse monetaire, detection d\'inflation)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS gils_supply_snapshot (
                id SERIAL PRIMARY KEY,
                captured_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                player_gils BIGINT NOT NULL,
                guild_gils BIGINT NOT NULL,
                shop_gils BIGINT NOT NULL,
                escrow_gils BIGINT NOT NULL,
                player_count INT NOT NULL
            )
            SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_gils_supply_captured ON gils_supply_snapshot (captured_at)');

        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN gils_supply_snapshot.escrow_gils IS
            'Gils immobilises : mises d''enchere en cours et commissions de commande vivante. Sortis d''une bourse, pas encore arrives dans une autre.'
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS gils_supply_snapshot');
    }
}
