<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-28 — les commandes de service : travailler un objet lie.
 *
 * La recette devient nullable (un service ne produit pas d'objet), la
 * commande gagne sa nature de service et l'objet-cible du client — distinct
 * des materiaux : eux se consomment, lui revient toujours.
 */
final class Version20260803CServiceOrder extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ECO-28: service craft orders (nullable recipe, service kind, escrowed target item)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE craft_order ALTER COLUMN recipe_id DROP NOT NULL');
        $this->addSql('ALTER TABLE craft_order ADD COLUMN IF NOT EXISTS service_kind VARCHAR(20) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_name = 'craft_order' AND column_name = 'target_item_id'
                ) THEN
                    ALTER TABLE craft_order ADD COLUMN target_item_id INT DEFAULT NULL
                        REFERENCES player_item (id) ON DELETE SET NULL;
                END IF;
            END $$
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_craft_order_service ON craft_order (service_kind, status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_craft_order_service');
        $this->addSql('ALTER TABLE craft_order DROP COLUMN IF EXISTS target_item_id');
        $this->addSql('ALTER TABLE craft_order DROP COLUMN IF EXISTS service_kind');
        // recipe_id reste nullable : le remettre NOT NULL echouerait sur les
        // lignes de service existantes.
    }
}
