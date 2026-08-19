<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * REP-04 — le rite d'eveil.
 *
 * Le rite est un **contrat** : les lots et les gils sont pris au lancement, et
 * ce que la table garde est la promesse de la materia — jamais la materia,
 * qu'on ne pourrait alors ni vendre ni voler puisqu'elle n'existe pas encore.
 */
final class Version20260819FRepAwakeningRite extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'REP-04 : les rites d\'eveil';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS awakening_rite (
                id SERIAL PRIMARY KEY,
                player_id INT NOT NULL,
                zone_id INT NOT NULL,
                materia_id INT NOT NULL,
                gils_paid INT NOT NULL,
                ends_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                claimed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
            SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_awakening_rite_player ON awakening_rite (player_id)');

        foreach ([
            ['fk_awakening_rite_player', 'player_id', 'player'],
            ['fk_awakening_rite_zone', 'zone_id', 'zone'],
            ['fk_awakening_rite_materia', 'materia_id', 'game_items'],
        ] as [$name, $column, $table]) {
            $this->addSql(sprintf(
                'DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'%s\') THEN '
                . 'ALTER TABLE awakening_rite ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (id) ON DELETE CASCADE; '
                . 'END IF; END $$',
                $name,
                $name,
                $column,
                $table,
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS awakening_rite');
    }
}
