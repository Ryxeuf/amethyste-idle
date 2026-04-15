<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415RecipeSpecializationExclusive extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add required_specialization column to game_recipes (task 122 sous-phase 2 - recettes exclusives)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_recipes ADD COLUMN IF NOT EXISTS required_specialization VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_recipes DROP COLUMN IF EXISTS required_specialization');
    }
}
