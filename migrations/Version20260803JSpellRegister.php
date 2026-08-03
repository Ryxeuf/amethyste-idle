<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-02 — le registre du geste.
 *
 * GAME_ARCHETYPES § 3 : le geste d'arme est une materia, et le geste declare
 * son registre. La materia en herite comme elle herite de l'element — sans
 * quoi un arbre de melee ou de distance ne qualifie aucune action.
 *
 * `spell` par defaut : tous les gestes livres avant ce jalon sont des sorts,
 * et la valeur par defaut le dit sans qu'on ait a les reprendre un par un.
 */
final class Version20260803JSpellRegister extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-02: combat register on spells (spell / melee / ranged)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS combat_register VARCHAR(20) DEFAULT 'spell' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells DROP COLUMN IF EXISTS combat_register');
    }
}
