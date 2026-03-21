<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321ItemRarityDefaults extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set default rarity to common for all items without a rarity value';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE game_items SET rarity = 'common' WHERE rarity IS NULL");
        $this->addSql("ALTER TABLE game_items ALTER COLUMN rarity SET DEFAULT 'common'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items ALTER COLUMN rarity DROP DEFAULT');
    }
}
