<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-04 : trace de la ristourne accordee a l'acheteur membre de la guilde
 * controlante.
 *
 * Les transactions anterieures gardent 0 : aucune ristourne n'existait avant ce
 * jalon, la valeur par defaut est donc exacte et non un simple remplissage.
 */
final class Version20260725AuctionMemberRebate extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add auction_transaction.member_rebate_amount (ECO-04)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auction_transaction ADD COLUMN IF NOT EXISTS member_rebate_amount INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auction_transaction DROP COLUMN IF EXISTS member_rebate_amount');
    }
}
