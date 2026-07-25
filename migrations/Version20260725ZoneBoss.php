<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725ZoneBoss extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add zone_boss table (pivot PBBG, ZON-18 — asynchronous zone boss with a shared HP pool)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS zone_boss (
                id SERIAL NOT NULL,
                game_event_id INT NOT NULL,
                monster_id INT NOT NULL,
                hp_max INT NOT NULL,
                hp_current INT NOT NULL,
                defeated BOOLEAN DEFAULT FALSE NOT NULL,
                defeated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_zone_boss_event ON zone_boss (game_event_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_zone_boss_monster ON zone_boss (monster_id)');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_zone_boss_event') THEN
                    ALTER TABLE zone_boss
                        ADD CONSTRAINT fk_zone_boss_event
                        FOREIGN KEY (game_event_id) REFERENCES game_event (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_zone_boss_monster') THEN
                    ALTER TABLE zone_boss
                        ADD CONSTRAINT fk_zone_boss_monster
                        FOREIGN KEY (monster_id) REFERENCES game_monsters (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS zone_boss');
    }
}
