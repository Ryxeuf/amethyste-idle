<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-20 : travail d'atelier en cours a l'etabli.
 *
 * L'unicite porte sur `player_id` : un etabli est un etabli, et c'est la base
 * de donnees qui doit le garantir — deux requetes concurrentes ne doivent pas
 * pouvoir ouvrir deux travaux, ce qu'un controle applicatif seul ne garantit
 * pas.
 */
final class Version20260726CraftJob extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create craft_job (ECO-20 workbench crafting time)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS craft_job (
                id SERIAL PRIMARY KEY,
                player_id INT NOT NULL,
                recipe_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                ready_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_craft_job_player ON craft_job (player_id)');

        // PostgreSQL ne connait pas ADD CONSTRAINT IF NOT EXISTS.
        foreach ([
            ['fk_craft_job_player', 'player_id', 'player (id)', ' ON DELETE CASCADE'],
            ['fk_craft_job_recipe', 'recipe_id', 'game_recipes (id)', ''],
        ] as [$name, $column, $target, $onDelete]) {
            $this->addSql(sprintf(
                'DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'%1$s\') THEN '
                . 'ALTER TABLE craft_job ADD CONSTRAINT %1$s FOREIGN KEY (%2$s) REFERENCES %3$s%4$s; END IF; END $$;',
                $name,
                $column,
                $target,
                $onDelete
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS craft_job');
    }
}
