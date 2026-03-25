<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325LinkedMateriaSlots extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add linked_slot_id column to slot table for materia synergy between linked slots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE slot ADD COLUMN IF NOT EXISTS linked_slot_id INTEGER DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_slot_linked_slot') THEN
                    ALTER TABLE slot ADD CONSTRAINT fk_slot_linked_slot FOREIGN KEY (linked_slot_id) REFERENCES slot(id) ON DELETE SET NULL;
                END IF;
            END $$
        SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_slot_linked_slot ON slot (linked_slot_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE slot DROP CONSTRAINT IF EXISTS fk_slot_linked_slot');
        $this->addSql('DROP INDEX IF EXISTS idx_slot_linked_slot');
        $this->addSql('ALTER TABLE slot DROP COLUMN IF EXISTS linked_slot_id');
    }
}
