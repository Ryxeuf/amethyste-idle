<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-07 : fin du travail d'atelier sur une commande de craft.
 *
 * La colonne est nullable, et l'absence de valeur vaut « pret » : les commandes
 * prises en charge avant ce jalon n'ont pas d'echeance de travail, et les
 * bloquer indefiniment punirait des artisans pour une migration.
 */
final class Version20260726CraftOrderReadyAt extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add craft_order.ready_at (ECO-07 crafting time gate)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE craft_order ADD COLUMN IF NOT EXISTS ready_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        // Le tableau des commandes en cours d'un artisan se lit par (artisan, statut).
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_craft_order_workshop ON craft_order (crafter_id, status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_craft_order_workshop');
        $this->addSql('ALTER TABLE craft_order DROP COLUMN IF EXISTS ready_at');
    }
}
