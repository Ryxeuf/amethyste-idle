<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-18d — un membre de donjon peut encaisser a la place des siens.
 *
 * La forme **transfert** (GAME_ARCHETYPES § 13.1, n° 3), qui repare le defaut
 * le plus structurel des huit : *notre modele ne peut pas avoir d'aggro*.
 *
 * Les deux colonnes naissent a zero — personne ne protege tant que personne
 * n'a joue le geste. Elles vivent sur le **membre** et non sur un
 * `StatusEffect` parce que le donjon de groupe a son propre modele de combat
 * (DON-02) : pas de `Fight`, donc pas de `FightStatusEffect` a deposer.
 */
final class Version20260818CArcDungeonTransfer extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-18d : un membre de donjon porte la part et la duree de son transfert.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE group_dungeon_member ADD COLUMN IF NOT EXISTS transfer_share DOUBLE PRECISION DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE group_dungeon_member ADD COLUMN IF NOT EXISTS transfer_turns INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE group_dungeon_member DROP COLUMN IF EXISTS transfer_share');
        $this->addSql('ALTER TABLE group_dungeon_member DROP COLUMN IF EXISTS transfer_turns');
    }
}
