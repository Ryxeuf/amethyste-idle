<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * DON-03 — la rencontre de donjon incarne un monstre.
 *
 * Le run porte desormais la creature de l'etape courante : sa vie dimensionne
 * la barre, son coup nourrit la riposte, son nom s'affiche. `SET NULL` a la
 * suppression du monstre : le run retombe sur les curseurs historiques, il ne
 * casse pas.
 */
final class Version20260802IDungeonEncounterMonster extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DON-03: group dungeon runs carry the monster of the current step';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE group_dungeon_run ADD COLUMN IF NOT EXISTS encounter_monster_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_gdr_encounter_monster') THEN
                    ALTER TABLE group_dungeon_run
                        ADD CONSTRAINT fk_gdr_encounter_monster
                        FOREIGN KEY (encounter_monster_id) REFERENCES game_monsters (id) ON DELETE SET NULL;
                END IF;
            END $$
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_gdr_encounter_monster ON group_dungeon_run (encounter_monster_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE group_dungeon_run DROP CONSTRAINT IF EXISTS fk_gdr_encounter_monster');
        $this->addSql('ALTER TABLE group_dungeon_run DROP COLUMN IF EXISTS encounter_monster_id');
    }
}
