<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * FAC-06 — l'approche et le receleur des Ruelles.
 *
 * Le compteur d'explorations nocturnes (le geste qualifiant de l'entree
 * differee) sur le joueur, et les lots hebdomadaires du marche gris.
 */
final class Version20260803DShadowsMarket extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FAC-06: night exploration counter + weekly fence sale lots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS night_explorations INT DEFAULT 0 NOT NULL');
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_weekly_fence_sale (
                id SERIAL PRIMARY KEY,
                player_id INT NOT NULL REFERENCES player (id) ON DELETE CASCADE,
                week_key VARCHAR(10) NOT NULL,
                lots INT DEFAULT 0 NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_player_weekly_fence ON player_weekly_fence_sale (player_id, week_key)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_weekly_fence_sale');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS night_explorations');
    }
}
