<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725GroupDungeonClear extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add group_dungeon_clear table for decreasing rewards & lockouts (pivot PBBG, ZON-20)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS group_dungeon_clear (
                id SERIAL NOT NULL,
                player_id INT NOT NULL,
                dungeon_id INT NOT NULL,
                run_id INT DEFAULT NULL,
                gils_awarded INT DEFAULT 0 NOT NULL,
                cleared_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_group_dungeon_clear_player_dungeon ON group_dungeon_clear (player_id, dungeon_id, cleared_at)');

        $this->addConstraintIfMissing('fk_group_dungeon_clear_player', 'group_dungeon_clear', 'player_id', 'player', 'CASCADE');
        $this->addConstraintIfMissing('fk_group_dungeon_clear_dungeon', 'group_dungeon_clear', 'dungeon_id', 'game_dungeons', 'CASCADE');
        $this->addConstraintIfMissing('fk_group_dungeon_clear_run', 'group_dungeon_clear', 'run_id', 'group_dungeon_run', 'SET NULL');
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
        $this->addSql('DROP TABLE IF EXISTS group_dungeon_clear');
    }
}
