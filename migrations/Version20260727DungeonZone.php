<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rattache un donjon a une zone du graphe (pivot PBBG, regle #7 : la position
 * d'un joueur est sa zone). Sans ce lien, l'ecran de zone n'avait aucun moyen
 * non ambigu de savoir quels donjons de groupe proposer : `dungeon.map_id` ne
 * pouvait pas servir, plusieurs zones partageant une meme carte source.
 *
 * Colonne nullable : les donjons solo existants restent hors graphe et continuent
 * de s'ouvrir depuis la liste globale `/game/dungeon`.
 */
final class Version20260727DungeonZone extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link dungeons to a zone of the graph so the zone screen can offer group dungeons';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_dungeons ADD COLUMN IF NOT EXISTS zone_id INT DEFAULT NULL');
        // PostgreSQL n'a pas d'ADD CONSTRAINT IF NOT EXISTS (cf. CLAUDE.md).
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_game_dungeons_zone') THEN
                    ALTER TABLE game_dungeons
                        ADD CONSTRAINT fk_game_dungeons_zone
                        FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE SET NULL;
                END IF;
            END $$;
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_game_dungeons_zone ON game_dungeons (zone_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_game_dungeons_zone');
        $this->addSql('ALTER TABLE game_dungeons DROP CONSTRAINT IF EXISTS fk_game_dungeons_zone');
        $this->addSql('ALTER TABLE game_dungeons DROP COLUMN IF EXISTS zone_id');
    }
}
