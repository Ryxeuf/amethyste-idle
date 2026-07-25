<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725GroupDungeon extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add group_dungeon_run + group_dungeon_member tables (pivot PBBG, ZON-19 — semi-synchronous group dungeon: model & formation)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS group_dungeon_run (
                id SERIAL NOT NULL,
                dungeon_id INT NOT NULL,
                zone_id INT DEFAULT NULL,
                leader_id INT NOT NULL,
                status VARCHAR(20) DEFAULT 'in_progress' NOT NULL,
                current_step INT DEFAULT 0 NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_group_dungeon_run_leader ON group_dungeon_run (leader_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_group_dungeon_run_status ON group_dungeon_run (status)');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS group_dungeon_member (
                id SERIAL NOT NULL,
                run_id INT NOT NULL,
                player_id INT NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_group_dungeon_member ON group_dungeon_member (run_id, player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_group_dungeon_member_player ON group_dungeon_member (player_id)');

        $this->addConstraintIfMissing('fk_group_dungeon_run_dungeon', 'group_dungeon_run', 'dungeon_id', 'game_dungeons', 'CASCADE');
        $this->addConstraintIfMissing('fk_group_dungeon_run_zone', 'group_dungeon_run', 'zone_id', 'zone', 'SET NULL');
        $this->addConstraintIfMissing('fk_group_dungeon_run_leader', 'group_dungeon_run', 'leader_id', 'player', 'CASCADE');
        $this->addConstraintIfMissing('fk_group_dungeon_member_run', 'group_dungeon_member', 'run_id', 'group_dungeon_run', 'CASCADE');
        $this->addConstraintIfMissing('fk_group_dungeon_member_player', 'group_dungeon_member', 'player_id', 'player', 'CASCADE');
    }

    private function addConstraintIfMissing(string $name, string $table, string $column, string $refTable, string $onDelete): void
    {
        $this->addSql(<<<SQL
            DO \$\$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = '{$name}') THEN
                    ALTER TABLE {$table}
                        ADD CONSTRAINT {$name}
                        FOREIGN KEY ({$column}) REFERENCES {$refTable} (id) ON DELETE {$onDelete};
                END IF;
            END \$\$;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS group_dungeon_member');
        $this->addSql('DROP TABLE IF EXISTS group_dungeon_run');
    }
}
