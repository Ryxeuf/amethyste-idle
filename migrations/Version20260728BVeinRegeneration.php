<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La repousse d'un filon devient un debit continu (ZON-37).
 *
 * `zone_vein.regenerated_at` porte l'ancre de conversion temps -> unites. Elle
 * remplace `depleted_at` dans ce role : ce dernier ne sert plus qu'a
 * l'affichage (« le filon est a sec »).
 *
 * Amorcage : les filons existants prennent `depleted_at` s'il existe, sinon
 * `updated_at`. Sans ancre, un filon serait repute a jour et ne devrait rien —
 * ce qui gelerait au passage tous les filons partiellement entames du serveur.
 */
final class Version20260728BVeinRegeneration extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Vein regeneration becomes a continuous rate instead of an all-or-nothing respawn phase';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone_vein ADD COLUMN IF NOT EXISTS regenerated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE zone_vein SET regenerated_at = COALESCE(depleted_at, updated_at) WHERE regenerated_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone_vein DROP COLUMN IF EXISTS regenerated_at');
    }
}
