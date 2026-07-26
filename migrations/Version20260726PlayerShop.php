<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-10 : echoppes joueur — entites et gating.
 *
 * Vitrine persistante d'un artisan, rattachee a la zone de sa demeure. Elle
 * differe de l'hotel des ventes, carnet d'ordres anonyme ou seul le prix
 * distingue les vendeurs. La vente, la caisse et le loyer suivent en ECO-11 ;
 * ce jalon pose les tables.
 */
final class Version20260726PlayerShop extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create player_shop, shop_listing and shop_sale_log (ECO-10)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_shop (
                id SERIAL NOT NULL,
                owner_id INT NOT NULL,
                zone_id INT NOT NULL,
                name VARCHAR(60) NOT NULL,
                sign VARCHAR(140) DEFAULT NULL,
                status VARCHAR(20) NOT NULL,
                slot_count INT DEFAULT 6 NOT NULL,
                vault_gils INT DEFAULT 0 NOT NULL,
                opened_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                rent_due_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        // Une echoppe par artisan : c'est une enseigne, pas une chaine.
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_player_shop_owner ON player_shop (owner_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_shop_zone_status ON player_shop (zone_id, status)');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS shop_listing (
                id SERIAL NOT NULL,
                shop_id INT NOT NULL,
                player_item_id INT NOT NULL,
                quantity INT NOT NULL,
                unit_price INT NOT NULL,
                listed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_shop_listing_shop ON shop_listing (shop_id)');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS shop_sale_log (
                id SERIAL NOT NULL,
                shop_id INT NOT NULL,
                buyer_id INT DEFAULT NULL,
                buyer_name VARCHAR(100) NOT NULL,
                item_name VARCHAR(150) NOT NULL,
                quantity INT NOT NULL,
                unit_price INT NOT NULL,
                tax_gils INT DEFAULT 0 NOT NULL,
                net_gils INT NOT NULL,
                sold_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_shop_sale_log_shop ON shop_sale_log (shop_id, sold_at)');

        // PostgreSQL ne connait pas ADD CONSTRAINT IF NOT EXISTS.
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_shop_owner') THEN
                    ALTER TABLE player_shop ADD CONSTRAINT fk_player_shop_owner
                        FOREIGN KEY (owner_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_shop_zone') THEN
                    ALTER TABLE player_shop ADD CONSTRAINT fk_player_shop_zone
                        FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_shop_listing_shop') THEN
                    ALTER TABLE shop_listing ADD CONSTRAINT fk_shop_listing_shop
                        FOREIGN KEY (shop_id) REFERENCES player_shop (id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_shop_listing_player_item') THEN
                    ALTER TABLE shop_listing ADD CONSTRAINT fk_shop_listing_player_item
                        FOREIGN KEY (player_item_id) REFERENCES player_item (id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_shop_sale_log_shop') THEN
                    ALTER TABLE shop_sale_log ADD CONSTRAINT fk_shop_sale_log_shop
                        FOREIGN KEY (shop_id) REFERENCES player_shop (id) ON DELETE CASCADE;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_shop_sale_log_buyer') THEN
                    ALTER TABLE shop_sale_log ADD CONSTRAINT fk_shop_sale_log_buyer
                        FOREIGN KEY (buyer_id) REFERENCES player (id) ON DELETE SET NULL;
                END IF;
            END $$
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS shop_sale_log');
        $this->addSql('DROP TABLE IF EXISTS shop_listing');
        $this->addSql('DROP TABLE IF EXISTS player_shop');
    }
}
