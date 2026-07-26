<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tache 129 (HOU-01) : demeure d'un personnage.
 *
 * L'unicite porte sur `owner_id` : une demeure par personnage, garanti **en
 * base**. Deux requetes d'achat concurrentes ne doivent pas pouvoir batir deux
 * maisons — et donc prelever deux fois le prix du terrain.
 */
final class Version20260726PlayerHouse extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create player_house (tache 129, HOU-01)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_house (
                id SERIAL PRIMARY KEY,
                owner_id INT NOT NULL,
                zone_id INT NOT NULL,
                name VARCHAR(60) NOT NULL,
                purchased_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_player_house_owner ON player_house (owner_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_house_zone ON player_house (zone_id)');

        // PostgreSQL ne connait pas ADD CONSTRAINT IF NOT EXISTS.
        foreach ([
            ['fk_player_house_owner', 'owner_id', 'player (id)', ' ON DELETE CASCADE'],
            ['fk_player_house_zone', 'zone_id', 'zone (id)', ''],
        ] as [$name, $column, $target, $onDelete]) {
            $this->addSql(sprintf(
                'DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'%1$s\') THEN '
                . 'ALTER TABLE player_house ADD CONSTRAINT %1$s FOREIGN KEY (%2$s) REFERENCES %3$s%4$s; END IF; END $$;',
                $name,
                $column,
                $target,
                $onDelete
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_house');
    }
}
