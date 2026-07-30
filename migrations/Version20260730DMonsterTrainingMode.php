<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-11 — les mannequins d'entrainement.
 *
 * `null` designe **un vrai monstre**, et c'est la valeur de tout ce qui vit
 * dans le monde : la colonne naît donc nulle partout, et aucune donnee
 * existante n'a besoin d'etre touchee. Les deux mannequins arrivent par les
 * fixtures, pas par une migration — ce sont du contenu, pas de la structure.
 *
 * Le suffixe `D` la trie apres les trois migrations du meme jour (cf. la
 * section « Pieges courants » de CLAUDE.md).
 */
final class Version20260730DMonsterTrainingMode extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ONB-11: game_monsters.training_mode, null meaning a real monster';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS training_mode VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN IF EXISTS training_mode');
    }
}
