<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314ShopAndQuests extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gils to player, shop_items to PNJ for commerce system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS gils INTEGER DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE pnj ADD COLUMN IF NOT EXISTS shop_items JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS gils');
        $this->addSql('ALTER TABLE pnj DROP COLUMN IF EXISTS shop_items');
    }
}
