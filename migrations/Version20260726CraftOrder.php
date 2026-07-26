<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-05 : commandes de craft, avec escrow des deux cotes.
 *
 * `player_item.craft_order_id` materialise l'escrow des materiaux : un objet
 * confie a une commande n'est dans aucun inventaire — il n'appartient plus au
 * commanditaire tant que la commande vit, et pas encore a l'artisan.
 */
final class Version20260726CraftOrder extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create craft_order and player_item.craft_order_id (ECO-05)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS craft_order (
                id SERIAL PRIMARY KEY,
                requester_id INT NOT NULL,
                recipe_id INT NOT NULL,
                crafter_id INT DEFAULT NULL,
                region_id INT DEFAULT NULL,
                commission INT NOT NULL DEFAULT 0,
                min_quality VARCHAR(20) DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'open',
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                claimed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                fulfilled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
            SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_craft_order_status ON craft_order (status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_craft_order_board ON craft_order (region_id, status, expires_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_craft_order_requester ON craft_order (requester_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_craft_order_crafter ON craft_order (crafter_id)');

        foreach ([
            ['fk_craft_order_requester', 'requester_id', 'player (id)', ''],
            ['fk_craft_order_recipe', 'recipe_id', 'game_recipes (id)', ''],
            ['fk_craft_order_crafter', 'crafter_id', 'player (id)', ' ON DELETE SET NULL'],
            ['fk_craft_order_region', 'region_id', 'region (id)', ' ON DELETE SET NULL'],
        ] as [$name, $column, $target, $onDelete]) {
            // PostgreSQL ne connait pas ADD CONSTRAINT IF NOT EXISTS.
            $this->addSql(sprintf(
                'DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'%1$s\') THEN '
                . 'ALTER TABLE craft_order ADD CONSTRAINT %1$s FOREIGN KEY (%2$s) REFERENCES %3$s%4$s; END IF; END $$;',
                $name,
                $column,
                $target,
                $onDelete
            ));
        }

        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS craft_order_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_item_craft_order') THEN
                    ALTER TABLE player_item
                        ADD CONSTRAINT fk_player_item_craft_order
                        FOREIGN KEY (craft_order_id) REFERENCES craft_order (id) ON DELETE SET NULL;
                END IF;
            END $$;
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_item_craft_order ON player_item (craft_order_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_player_item_craft_order');
        $this->addSql('ALTER TABLE player_item DROP CONSTRAINT IF EXISTS fk_player_item_craft_order');
        $this->addSql('ALTER TABLE player_item DROP COLUMN IF EXISTS craft_order_id');
        $this->addSql('DROP TABLE IF EXISTS craft_order');
    }
}
