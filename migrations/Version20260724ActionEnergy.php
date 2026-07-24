<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724ActionEnergy extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player action energy fields (pivot PBBG, ZON-07 — PBBG action resource, distinct from combat energy)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS action_energy INT NOT NULL DEFAULT 100');
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS max_action_energy INT NOT NULL DEFAULT 100');
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS action_energy_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS action_energy_updated_at');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS max_action_energy');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS action_energy');
    }
}
