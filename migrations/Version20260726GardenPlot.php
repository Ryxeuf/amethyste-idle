<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tache 129 (HOU-02) : parcelles du jardin.
 *
 * L'unicite porte sur (`house_id`, `position`) : deux parcelles a la meme place
 * dedoubleraient silencieusement le rendement du jardin.
 */
final class Version20260726GardenPlot extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create garden_plot (tache 129, HOU-02)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS garden_plot (
                id SERIAL PRIMARY KEY,
                house_id INT NOT NULL,
                crop_id INT DEFAULT NULL,
                position INT NOT NULL DEFAULT 0,
                ready_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_garden_plot_position ON garden_plot (house_id, position)');

        // PostgreSQL ne connait pas ADD CONSTRAINT IF NOT EXISTS.
        foreach ([
            ['fk_garden_plot_house', 'house_id', 'player_house (id)', ' ON DELETE CASCADE'],
            ['fk_garden_plot_crop', 'crop_id', 'game_items (id)', ' ON DELETE SET NULL'],
        ] as [$name, $column, $target, $onDelete]) {
            $this->addSql(sprintf(
                'DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'%1$s\') THEN '
                . 'ALTER TABLE garden_plot ADD CONSTRAINT %1$s FOREIGN KEY (%2$s) REFERENCES %3$s%4$s; END IF; END $$;',
                $name,
                $column,
                $target,
                $onDelete
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS garden_plot');
    }
}
