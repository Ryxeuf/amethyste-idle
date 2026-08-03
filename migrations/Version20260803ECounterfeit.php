<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * FAC-07 — la contrefacon des Ruelles.
 *
 * Le flag, le compteur cache de la trahison (tire 8-12 a la creation, jamais
 * montre) et l'etat identifie, sur l'instance d'objet — la contrefacon est un
 * accident de l'exemplaire, jamais du catalogue.
 */
final class Version20260803ECounterfeit extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FAC-07: counterfeit flag, hidden betrayal counter and identified state on player_item';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS counterfeit BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS counterfeit_charges INT DEFAULT NULL');
        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS counterfeit_identified BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_item DROP COLUMN IF EXISTS counterfeit');
        $this->addSql('ALTER TABLE player_item DROP COLUMN IF EXISTS counterfeit_charges');
        $this->addSql('ALTER TABLE player_item DROP COLUMN IF EXISTS counterfeit_identified');
    }
}
