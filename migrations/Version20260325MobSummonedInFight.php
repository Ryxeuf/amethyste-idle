<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325MobSummonedInFight extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add summoned_in_fight column to mob table for summoner mobs mechanic';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mob ADD COLUMN IF NOT EXISTS summoned_in_fight BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mob DROP COLUMN IF EXISTS summoned_in_fight');
    }
}
