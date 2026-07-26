<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-07b : commande directe, adressee a un artisan nomme.
 *
 * `ON DELETE SET NULL` plutot que `CASCADE` : si l'artisan cible disparait, la
 * commande **redevient publique** au lieu d'etre detruite avec l'escrow du
 * commanditaire.
 */
final class Version20260726CraftOrderTargetCrafter extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add craft_order.target_crafter_id (ECO-07b direct orders)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE craft_order ADD COLUMN IF NOT EXISTS target_crafter_id INT DEFAULT NULL');
        // PostgreSQL ne connait pas ADD CONSTRAINT IF NOT EXISTS.
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_craft_order_target_crafter') THEN
                    ALTER TABLE craft_order
                        ADD CONSTRAINT fk_craft_order_target_crafter
                        FOREIGN KEY (target_crafter_id) REFERENCES player (id) ON DELETE SET NULL;
                END IF;
            END $$;
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_craft_order_target ON craft_order (target_crafter_id, status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_craft_order_target');
        $this->addSql('ALTER TABLE craft_order DROP CONSTRAINT IF EXISTS fk_craft_order_target_crafter');
        $this->addSql('ALTER TABLE craft_order DROP COLUMN IF EXISTS target_crafter_id');
    }
}
