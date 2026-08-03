<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-04b — les munitions, ressource du registre distance.
 *
 * GAME_MATERIA § 2.3 bis : chaque registre a sa ressource. Les sorts paient en
 * PM, la melee en tours de reprise (ARC-04a), le tir en munitions.
 *
 * Deux colonnes, et la seconde dit pourquoi la premiere ne suffit pas : le
 * geste declare **ce qu'il consomme** (`ammo_cost`), le carquois **ce qu'il
 * porte** (`ammo_capacity`). La capacite ne passe pas par `nb_usages`, qui
 * detruit l'objet a zero : le carquois est une piece durable, il se vide dans
 * la rencontre et se ramasse apres (§ 9 septies — *aucun archetype ne porte un
 * cout recurrent en gils que les autres n'ont pas*).
 */
final class Version20260803MAmmunition extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-04: ammunition cost on gestures, ammunition capacity on quivers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS ammo_cost INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE game_items ADD COLUMN IF NOT EXISTS ammo_capacity INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells DROP COLUMN IF EXISTS ammo_cost');
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS ammo_capacity');
    }
}
