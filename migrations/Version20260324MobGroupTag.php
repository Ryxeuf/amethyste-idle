<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324MobGroupTag extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add group_tag column to mob table for multi-mob encounter groups';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mob ADD COLUMN IF NOT EXISTS group_tag VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_mob_group_tag ON mob (group_tag)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_mob_group_tag');
        $this->addSql('ALTER TABLE mob DROP COLUMN IF EXISTS group_tag');
    }
}
