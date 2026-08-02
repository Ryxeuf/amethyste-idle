<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * MAT-01 — l'element des monstres.
 *
 * Prerequis du butin de materia derive (un monstre lache des materia de son
 * element) et de la capacite raciale de l'Orc, qui n'avait rien a lire. La
 * colonne naît a `none` partout : les valeurs reelles arrivent par les
 * fixtures — c'est du contenu, pas de la structure.
 */
final class Version20260802AMonsterElement extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'MAT-01: game_monsters.element, the flux the creature belongs to';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS element VARCHAR(20) DEFAULT 'none' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN IF EXISTS element');
    }
}
