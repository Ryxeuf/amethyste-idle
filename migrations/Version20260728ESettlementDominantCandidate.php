<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Quel indice tient l'avance qui decidera du type (FOY-03).
 *
 * `dominant_since` existait deja, mais sans sujet : on savait que quelqu'un
 * menait depuis vingt jours, sans savoir qui. Un changement de meneur passait
 * alors pour une continuite, et le type se serait installe au nom du mauvais
 * indice.
 *
 * Le suffixe `E` trie cette migration apres `C` (creation de `settlement`) et
 * `D` : Doctrine ordonne par nom de version (cf. CLAUDE.md).
 */
final class Version20260728ESettlementDominantCandidate extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Which sediment index currently claims the settlement type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settlement ADD COLUMN IF NOT EXISTS dominant_candidate VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settlement DROP COLUMN IF EXISTS dominant_candidate');
    }
}
