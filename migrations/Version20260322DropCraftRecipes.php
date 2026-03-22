<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322DropCraftRecipes extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the obsolete game_craft_recipes table (replaced by game_recipes)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS game_craft_recipes');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE game_craft_recipes (
                id SERIAL PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                profession VARCHAR(50) NOT NULL,
                required_level INT DEFAULT 1 NOT NULL,
                ingredients JSON NOT NULL,
                result_item_slug VARCHAR(100) NOT NULL,
                result_quantity INT DEFAULT 1 NOT NULL,
                craft_time INT DEFAULT 5 NOT NULL,
                experience_gain INT DEFAULT 10 NOT NULL,
                required_skill_slug VARCHAR(100) DEFAULT NULL,
                is_discoverable BOOLEAN DEFAULT false NOT NULL,
                is_discovered BOOLEAN DEFAULT false NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        SQL);
    }
}
