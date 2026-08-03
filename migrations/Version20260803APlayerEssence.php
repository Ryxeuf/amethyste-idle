<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * FAC-04b — l'essence, monnaie de services de la Fonderie.
 *
 * Rendue par la fonte d'une materia, depensable uniquement en services
 * (jamais en objets — l'invariant se tient par l'absence de chemin d'achat,
 * verifie par un test de contrat).
 */
final class Version20260803APlayerEssence extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FAC-04b: essence currency column on player';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS essence INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS essence');
    }
}
