<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * REP-03 — les gestes que le monde a retrouves.
 *
 * Une ligne par geste, et rien d'autre que le fait qu'il soit retrouve. Il n'y
 * a **aucune colonne pour le reprendre** : le savoir n'est jamais borne.
 */
final class Version20260819ERepGestures extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'REP-03 : les gestes retrouves du serveur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS repertoire_gesture (
                id SERIAL PRIMARY KEY,
                gesture_key VARCHAR(64) NOT NULL,
                discovery_rank INT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_repertoire_gesture_key ON repertoire_gesture (gesture_key)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS repertoire_gesture');
    }
}
