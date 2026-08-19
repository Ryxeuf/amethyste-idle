<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-16b — une accointance nomme ce sur quoi sa forme agit.
 *
 * Deux colonnes de **texte**, jamais de chiffre : `subject` porte une famille de
 * l'echelle de port (`access_discount`) ou une condition de build
 * (`condition_widening`), et `widened_by` la seconde condition de l'elargissement.
 * Les deux formes derivees de la paire (`domain_expression`, `slot_acceptance`)
 * les laissent vides — leur payload EST la paire.
 */
final class Version20260819AArcAccointanceSubject extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-16b : DomainSynergy porte le sujet de sa forme (famille ou condition), jamais un chiffre.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_domain_synergies ADD COLUMN IF NOT EXISTS subject VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE game_domain_synergies ADD COLUMN IF NOT EXISTS widened_by VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_domain_synergies DROP COLUMN IF EXISTS subject');
        $this->addSql('ALTER TABLE game_domain_synergies DROP COLUMN IF EXISTS widened_by');
    }
}
