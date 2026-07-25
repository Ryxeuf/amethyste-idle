<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-01 : remplace le booleen `game_items.bound_to_player` par une enumeration
 * `bind_type` (none / bind_on_equip / bind_on_pickup).
 *
 * Retro-compatibilite : l'ancien booleen ne savait exprimer que « lie des
 * l'obtention ». Les objets qui le portaient deviennent `bind_on_pickup`, les
 * autres `none` — aucun objet ne change de comportement.
 */
final class Version20260725ItemBindType extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace game_items.bound_to_player (bool) with bind_type enum (ECO-01)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE game_items ADD COLUMN IF NOT EXISTS bind_type VARCHAR(20) NOT NULL DEFAULT 'none'");
        // Backfill avant suppression : la donnee existante porte la liaison.
        $this->addSql("UPDATE game_items SET bind_type = 'bind_on_pickup' WHERE bound_to_player = TRUE");
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS bound_to_player');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items ADD COLUMN IF NOT EXISTS bound_to_player BOOLEAN NOT NULL DEFAULT FALSE');
        // `bind_on_equip` n'a pas d'equivalent dans l'ancien modele : ces objets
        // redeviennent librement echangeables, c'est le seul repli possible.
        $this->addSql("UPDATE game_items SET bound_to_player = TRUE WHERE bind_type = 'bind_on_pickup'");
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS bind_type');
    }
}
