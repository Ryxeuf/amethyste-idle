<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * DON-04 — l'apercu de butin cesse d'etre un texte libre.
 *
 * `loot_preview` promettait de l'equipement, des composants et des lingots
 * que le run n'a jamais distribues. L'apercu se derive desormais de la table
 * reelle (`MateriaLootTable::dungeonPaliers`, la meme lecture que le tirage) :
 * la colonne n'a plus de raison d'exister.
 */
final class Version20260802JDropDungeonLootPreview extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DON-04: drop game_dungeons.loot_preview — the loot preview derives from the real table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_dungeons DROP COLUMN IF EXISTS loot_preview');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_dungeons ADD COLUMN IF NOT EXISTS loot_preview JSON DEFAULT NULL');
    }
}
