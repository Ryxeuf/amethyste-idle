<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-18c — un geste peut couter des points de vie.
 *
 * La forme **conversion** (GAME_ARCHETYPES § 13.1, n° 6). La colonne nait a
 * `0` sur les 253 gestes livres : *un geste qui coute de la vie est une
 * decision d'auteur, jamais un defaut*.
 *
 * Ce qu'un point de vie **rend** n'est pas stocke : il se derive des deux
 * curseurs de regeneration (`ConversionLaw`). Un second chiffre en base aurait
 * diverge du premier a la premiere recalibration.
 */
final class Version20260818BArcSpellLifeCost extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-18c : Spell porte le cout en points de vie d\'un geste de conversion.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS life_cost INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells DROP COLUMN IF EXISTS life_cost');
    }
}
