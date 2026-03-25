<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326ShopStock extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shop_stock JSON column to pnj table for limited stock management';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pnj ADD COLUMN IF NOT EXISTS shop_stock JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pnj DROP COLUMN IF EXISTS shop_stock');
    }
}
