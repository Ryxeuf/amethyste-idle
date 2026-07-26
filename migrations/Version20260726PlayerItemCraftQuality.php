<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-20 : qualite de fabrication conservee sur l'objet.
 *
 * Nullable : tout ce qui ne sort pas d'un etabli n'a pas de qualite de craft,
 * et les objets deja en circulation n'en ont jamais eu — leur en inventer une
 * retroactivement fausserait la seule donnee que ce champ doit porter.
 */
final class Version20260726PlayerItemCraftQuality extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player_item.craft_quality (ECO-20)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS craft_quality VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_item DROP COLUMN IF EXISTS craft_quality');
    }
}
