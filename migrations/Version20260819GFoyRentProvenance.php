<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * FOY-19 — la provenance « loyers » dans le tresor de guilde.
 *
 * Un **cumul**, jamais un solde : les gils vivent dans `gils_treasury`, et cette
 * colonne ne fait que se souvenir de leur provenance.
 */
final class Version20260819GFoyRentProvenance extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FOY-19 : ce que les habitants rapportent, visible au tresor';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guild ADD COLUMN IF NOT EXISTS gils_from_rents INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guild DROP COLUMN IF EXISTS gils_from_rents');
    }
}
