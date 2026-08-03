<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-03 — les leviers d'un nœud passif.
 *
 * GAME_ARCHETYPES § 4 : les cinq statistiques plates cedent la place a quinze
 * leviers en pourcentage, payes en points de budget. La colonne porte la liste
 * `(levier, points, condition ?)` de chaque nœud.
 *
 * `NULL` par defaut, et les colonnes plates restent en place : la conversion du
 * contenu est un autre chantier (ARC-07 pour les quatre arbres patrons, ARC-08
 * pour les vingt autres). Le vocabulaire se livre donc sans toucher a une seule
 * valeur de jeu.
 */
final class Version20260803KSkillLevers extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-03: lever grants on skill nodes (lever, budget points, condition)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_skills ADD COLUMN IF NOT EXISTS levers JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_skills DROP COLUMN IF EXISTS levers');
    }
}
