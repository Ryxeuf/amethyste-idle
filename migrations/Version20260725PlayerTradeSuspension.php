<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-16b : suspension d'acces aux canaux d'echange entre joueurs.
 *
 * Sanction proportionnee — le bannissement de compte existe deja mais coupe
 * tout. Un joueur qui truque des prix doit pouvoir continuer a jouer pendant que
 * le marche lui est ferme.
 */
final class Version20260725PlayerTradeSuspension extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player.trade_suspended_until and index auction_transaction.purchased_at (ECO-16b)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS trade_suspended_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_auction_transaction_purchased_at ON auction_transaction (purchased_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_auction_transaction_purchased_at');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS trade_suspended_until');
    }
}
