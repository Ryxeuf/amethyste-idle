<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Mesure de la charge du monde (FOY-17).
 *
 * Deux ajouts sur `player` et une table d'instantanes journaliers. Le
 * dimensionnement du monde repose sur « la population active » ; BALANCE § 22.5
 * tranche qu'on la deduit de l'**energie depensee**, pas des connexions — c'est
 * ce qui l'immunise contre le multi-compte.
 *
 * `last_activity_at` remplace le proxy `player.updated_at` d'
 * `InfluenceAntiExploit`, que le code lui-meme signalait comme approximatif :
 * `updated_at` bouge sur des ecritures systeme, si bien qu'une seule connexion
 * valait sept jours d'activite.
 *
 * Le suffixe `A` du nom trie cette migration avant toute autre du 2026-07-28 :
 * Doctrine ordonne par nom de version, pas par heure de creation (cf. CLAUDE.md).
 */
final class Version20260728AWorldLoad extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Measure world load from spent action energy: player activity stamp, cumulative spend, daily snapshots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS last_activity_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS action_energy_spent_total BIGINT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_last_activity ON player (last_activity_at)');

        // Amorcage : on ne connait pas l'activite passee. `updated_at` est le
        // moins mauvais point de depart — c'est le proxy que le code utilisait
        // deja, et il evite de faire passer tout le monde pour inactif au
        // premier tick suivant le deploiement.
        $this->addSql('UPDATE player SET last_activity_at = updated_at WHERE last_activity_at IS NULL');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS world_load_snapshot (
                id SERIAL NOT NULL,
                day DATE NOT NULL,
                cumulative_energy BIGINT DEFAULT 0 NOT NULL,
                daily_energy INT DEFAULT 0 NOT NULL,
                captured_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_world_load_snapshot_day ON world_load_snapshot (day)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS world_load_snapshot');
        $this->addSql('DROP INDEX IF EXISTS idx_player_last_activity');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS action_energy_spent_total');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS last_activity_at');
    }
}
