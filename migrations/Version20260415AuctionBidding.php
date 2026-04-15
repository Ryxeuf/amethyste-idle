<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415AuctionBidding extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add auction bidding columns to auction_listing (task 123 - encheres temporaires)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE auction_listing ADD COLUMN IF NOT EXISTS type VARCHAR(20) NOT NULL DEFAULT 'fixed'");
        $this->addSql('ALTER TABLE auction_listing ADD COLUMN IF NOT EXISTS min_increment INT DEFAULT NULL');
        $this->addSql('ALTER TABLE auction_listing ADD COLUMN IF NOT EXISTS current_bid INT DEFAULT NULL');
        $this->addSql('ALTER TABLE auction_listing ADD COLUMN IF NOT EXISTS current_bidder_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_auction_listing_current_bidder') THEN
        ALTER TABLE auction_listing
            ADD CONSTRAINT fk_auction_listing_current_bidder
            FOREIGN KEY (current_bidder_id) REFERENCES player(id) ON DELETE SET NULL;
    END IF;
END $$;
SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_auction_listing_current_bidder ON auction_listing (current_bidder_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_auction_listing_type ON auction_listing (type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auction_listing DROP CONSTRAINT IF EXISTS fk_auction_listing_current_bidder');
        $this->addSql('DROP INDEX IF EXISTS idx_auction_listing_current_bidder');
        $this->addSql('DROP INDEX IF EXISTS idx_auction_listing_type');
        $this->addSql('ALTER TABLE auction_listing DROP COLUMN IF EXISTS current_bidder_id');
        $this->addSql('ALTER TABLE auction_listing DROP COLUMN IF EXISTS current_bid');
        $this->addSql('ALTER TABLE auction_listing DROP COLUMN IF EXISTS min_increment');
        $this->addSql('ALTER TABLE auction_listing DROP COLUMN IF EXISTS type');
    }
}
