<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-08b : reputation d'artisan, par metier.
 *
 * `ON DELETE CASCADE` : la reputation n'a aucun sens sans l'artisan qui l'a
 * gagnee, contrairement a une commande dont l'escrow doit survivre.
 */
final class Version20260726CrafterReputation extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create crafter_reputation (ECO-08b)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS crafter_reputation (
                id SERIAL PRIMARY KEY,
                player_id INT NOT NULL,
                craft VARCHAR(40) NOT NULL,
                deliveries INT NOT NULL DEFAULT 0,
                points INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_crafter_reputation ON crafter_reputation (player_id, craft)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_crafter_reputation_ranking ON crafter_reputation (craft, points)');

        // PostgreSQL ne connait pas ADD CONSTRAINT IF NOT EXISTS.
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_crafter_reputation_player') THEN
                    ALTER TABLE crafter_reputation
                        ADD CONSTRAINT fk_crafter_reputation_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
            END $$;
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS crafter_reputation');
    }
}
