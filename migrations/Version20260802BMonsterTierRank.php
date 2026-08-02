<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * BES-01 — deux axes : `tier` et `rank`.
 *
 * `level` (1-40), `difficulty` (1-5) et `is_boss` disparaissent au profit de
 * deux axes orthogonaux : le palier (T0-T4, repris de la zone) et le rang
 * (common/elite/boss). Le joueur n'ayant pas de niveau (regle 6), l'echelle
 * 1-40 ne se comparait a rien.
 *
 * Le backfill est un meilleur-effort par bandes de niveau : les valeurs
 * exactes arrivent par les fixtures, qui sont la source de verite du contenu.
 * `zone.tier` naît a 0 et est renseigne par `app:zone:import`.
 */
final class Version20260802BMonsterTierRank extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'BES-01: monster tier/rank replace level/difficulty/is_boss; mob.tier; zone.tier; monster_items.min_rank';
    }

    public function up(Schema $schema): void
    {
        // game_monsters : les deux axes
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS tier INT DEFAULT 1 NOT NULL');
        $this->addSql("ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS rank VARCHAR(20) DEFAULT 'common' NOT NULL");
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'game_monsters' AND column_name = 'level') THEN
                    UPDATE game_monsters SET tier = CASE
                        WHEN training_mode IS NOT NULL THEN 0
                        WHEN level <= 2 THEN 1
                        WHEN level <= 6 THEN 2
                        WHEN level <= 18 THEN 3
                        ELSE 4
                    END;
                END IF;
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'game_monsters' AND column_name = 'is_boss') THEN
                    UPDATE game_monsters SET rank = 'boss' WHERE is_boss = TRUE;
                END IF;
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'game_monsters' AND column_name = 'difficulty') THEN
                    UPDATE game_monsters SET rank = 'elite' WHERE rank = 'common' AND difficulty >= 4;
                END IF;
            END $$
            SQL);
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN IF EXISTS level');
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN IF EXISTS difficulty');
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN IF EXISTS is_boss');

        // game_monster_items : le gate de butin change d'echelle
        $this->addSql('ALTER TABLE game_monster_items ADD COLUMN IF NOT EXISTS min_rank VARCHAR(20) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'game_monster_items' AND column_name = 'min_difficulty') THEN
                    UPDATE game_monster_items SET min_rank = 'elite' WHERE min_difficulty >= 3;
                END IF;
            END $$
            SQL);
        $this->addSql('ALTER TABLE game_monster_items DROP COLUMN IF EXISTS min_difficulty');

        // mob : la recopie suit le monstre
        $this->addSql('ALTER TABLE mob ADD COLUMN IF NOT EXISTS tier INT DEFAULT 1 NOT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'mob' AND column_name = 'level') THEN
                    UPDATE mob SET tier = CASE
                        WHEN level <= 2 THEN 1
                        WHEN level <= 6 THEN 2
                        WHEN level <= 18 THEN 3
                        ELSE 4
                    END;
                END IF;
            END $$
            SQL);
        $this->addSql('ALTER TABLE mob DROP COLUMN IF EXISTS level');

        // zone : le palier declare (GAME_ZONES §2), renseigne par app:zone:import
        $this->addSql('ALTER TABLE zone ADD COLUMN IF NOT EXISTS tier INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS level INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS difficulty INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS is_boss BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql("UPDATE game_monsters SET is_boss = TRUE WHERE rank = 'boss'");
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN IF EXISTS tier');
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN IF EXISTS rank');

        $this->addSql('ALTER TABLE game_monster_items ADD COLUMN IF NOT EXISTS min_difficulty INT DEFAULT NULL');
        $this->addSql('UPDATE game_monster_items SET min_difficulty = 3 WHERE min_rank IS NOT NULL');
        $this->addSql('ALTER TABLE game_monster_items DROP COLUMN IF EXISTS min_rank');

        $this->addSql('ALTER TABLE mob ADD COLUMN IF NOT EXISTS level INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE mob DROP COLUMN IF EXISTS tier');

        $this->addSql('ALTER TABLE zone DROP COLUMN IF EXISTS tier');
    }
}
