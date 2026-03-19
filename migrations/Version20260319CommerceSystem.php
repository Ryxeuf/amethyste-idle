<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319CommerceSystem extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 6: Commerce system - Auction house, player trading, transaction logs, shop stock';
    }

    public function up(Schema $schema): void
    {
        // Auction Listing table
        $this->addSql('CREATE TABLE IF NOT EXISTS auction_listing (
            id SERIAL PRIMARY KEY,
            seller_id INTEGER NOT NULL REFERENCES player(id),
            buyer_id INTEGER DEFAULT NULL REFERENCES player(id),
            player_item_id INTEGER DEFAULT NULL REFERENCES player_item(id) ON DELETE SET NULL,
            item_id INTEGER NOT NULL REFERENCES game_items(id),
            price INTEGER NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'active\',
            duration_hours INTEGER NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            sold_at TIMESTAMP DEFAULT NULL,
            tax_amount INTEGER NOT NULL DEFAULT 0,
            quantity INTEGER NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_auction_status ON auction_listing (status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_auction_expires ON auction_listing (expires_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_auction_seller ON auction_listing (seller_id)');

        // Trade Offer table
        $this->addSql('CREATE TABLE IF NOT EXISTS trade_offer (
            id SERIAL PRIMARY KEY,
            initiator_id INTEGER NOT NULL REFERENCES player(id),
            receiver_id INTEGER NOT NULL REFERENCES player(id),
            initiator_items JSON NOT NULL DEFAULT \'[]\',
            receiver_items JSON NOT NULL DEFAULT \'[]\',
            initiator_gils INTEGER NOT NULL DEFAULT 0,
            receiver_gils INTEGER NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            initiator_confirmed BOOLEAN NOT NULL DEFAULT FALSE,
            receiver_confirmed BOOLEAN NOT NULL DEFAULT FALSE,
            completed_at TIMESTAMP DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_trade_status ON trade_offer (status)');

        // Transaction Log table
        $this->addSql('CREATE TABLE IF NOT EXISTS transaction_log (
            id SERIAL PRIMARY KEY,
            type VARCHAR(30) NOT NULL,
            player_id INTEGER NOT NULL REFERENCES player(id),
            other_player_id INTEGER DEFAULT NULL REFERENCES player(id),
            item_id INTEGER DEFAULT NULL REFERENCES game_items(id),
            quantity INTEGER NOT NULL DEFAULT 1,
            gils_amount INTEGER NOT NULL DEFAULT 0,
            tax_amount INTEGER NOT NULL DEFAULT 0,
            description VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_transaction_type ON transaction_log (type)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_transaction_date ON transaction_log (created_at)');

        // Shop Stock table
        $this->addSql('CREATE TABLE IF NOT EXISTS shop_stock (
            id SERIAL PRIMARY KEY,
            pnj_id INTEGER NOT NULL REFERENCES pnj(id),
            item_id INTEGER NOT NULL REFERENCES game_items(id),
            max_stock INTEGER DEFAULT NULL,
            current_stock INTEGER DEFAULT NULL,
            restock_interval_minutes INTEGER DEFAULT NULL,
            last_restock_at TIMESTAMP DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT uniq_shop_stock_pnj_item UNIQUE (pnj_id, item_id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS shop_stock');
        $this->addSql('DROP TABLE IF EXISTS transaction_log');
        $this->addSql('DROP TABLE IF EXISTS trade_offer');
        $this->addSql('DROP TABLE IF EXISTS auction_listing');
    }
}
