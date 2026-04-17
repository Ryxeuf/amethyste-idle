<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411AuctionHouse extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create auction_listing and auction_transaction tables for the auction house system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS auction_listing (
                id SERIAL PRIMARY KEY,
                seller_id INT NOT NULL,
                player_item_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                price_per_unit INT NOT NULL,
                listing_fee INT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                region_tax_rate NUMERIC(5, 4) NOT NULL DEFAULT 0.0000,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                CONSTRAINT fk_auction_listing_seller FOREIGN KEY (seller_id) REFERENCES player (id) ON DELETE CASCADE,
                CONSTRAINT fk_auction_listing_item FOREIGN KEY (player_item_id) REFERENCES player_item (id) ON DELETE CASCADE
            )
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_auction_listing_status ON auction_listing (status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_auction_listing_seller ON auction_listing (seller_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_auction_listing_expires ON auction_listing (status, expires_at)');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS auction_transaction (
                id SERIAL PRIMARY KEY,
                listing_id INT NOT NULL,
                buyer_id INT NOT NULL,
                total_price INT NOT NULL,
                region_tax_amount INT NOT NULL DEFAULT 0,
                purchased_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                CONSTRAINT fk_auction_transaction_listing FOREIGN KEY (listing_id) REFERENCES auction_listing (id) ON DELETE CASCADE,
                CONSTRAINT fk_auction_transaction_buyer FOREIGN KEY (buyer_id) REFERENCES player (id) ON DELETE CASCADE
            )
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_auction_transaction_buyer ON auction_transaction (buyer_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_auction_transaction_listing ON auction_transaction (listing_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS auction_transaction');
        $this->addSql('DROP TABLE IF EXISTS auction_listing');
    }
}
