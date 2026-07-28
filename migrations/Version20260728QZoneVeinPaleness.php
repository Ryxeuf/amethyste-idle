<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La Paleur d'un filon (FOY-11).
 *
 * L'extraction laisse une trace. Deux colonnes, et leur separation est le
 * jalon : `paleness` est l'**etat** — graduel, borne, reversible —,
 * `extracted_since_tick` le **rythme** de la journee en cours, qui se remet a
 * zero a chaque tick.
 *
 * Un cumul historique aurait fait payer eternellement une ruee d'un soir ; ce
 * qu'on mesure est la vitesse a laquelle on prend, comparee a celle a laquelle
 * le filon rend.
 */
final class Version20260728QZoneVeinPaleness extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Vein paleness: concentrated extraction leaves a bounded, reversible mark';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone_vein ADD COLUMN IF NOT EXISTS paleness DOUBLE PRECISION DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE zone_vein ADD COLUMN IF NOT EXISTS extracted_since_tick INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone_vein DROP COLUMN IF EXISTS paleness');
        $this->addSql('ALTER TABLE zone_vein DROP COLUMN IF EXISTS extracted_since_tick');
    }
}
