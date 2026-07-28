<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Commande de guilde (RET-03).
 *
 * `ON DELETE SET NULL` plutot que `CASCADE` : la dissolution d'une guilde ne
 * doit pas effacer une commande en cours et l'escrow qui va avec. La commande
 * redevient ordinaire, les materiaux restent ou ils sont, et le commanditaire
 * garde ses droits — perdre des Gils et des objets parce qu'une guilde a ferme
 * serait la pire facon d'apprendre qu'elle a ferme.
 */
final class Version20260728FCraftOrderGuild extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Guild-scoped craft orders: a weekly rendezvous, visible to members only';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE craft_order ADD COLUMN IF NOT EXISTS guild_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_craft_order_guild ON craft_order (guild_id)');

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_craft_order_guild') THEN
                    ALTER TABLE craft_order
                        ADD CONSTRAINT fk_craft_order_guild
                        FOREIGN KEY (guild_id) REFERENCES guild (id) ON DELETE SET NULL;
                END IF;
            END $$;
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE craft_order DROP COLUMN IF EXISTS guild_id');
    }
}
