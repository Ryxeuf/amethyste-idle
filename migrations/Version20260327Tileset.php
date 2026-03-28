<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327Tileset extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tileset table for custom tileset management in map editor';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS tileset (
                id SERIAL PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                image_path VARCHAR(500) NOT NULL,
                columns_count INT NOT NULL,
                tile_count INT NOT NULL,
                tile_width INT NOT NULL DEFAULT 32,
                tile_height INT NOT NULL DEFAULT 32,
                first_gid INT NOT NULL,
                is_builtin BOOLEAN NOT NULL DEFAULT FALSE,
                is_editable BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS tileset_name_uniq ON tileset (name)
        SQL);

        // Seed built-in tilesets
        $this->addSql(<<<'SQL'
            INSERT INTO tileset (name, image_path, columns_count, tile_count, tile_width, tile_height, first_gid, is_builtin, is_editable)
            VALUES
                ('terrain', 'terrain/terrain.png', 32, 1024, 32, 32, 1, TRUE, TRUE),
                ('forest', 'terrain/forest.png', 16, 3072, 32, 32, 1025, TRUE, TRUE),
                ('BaseChip_pipo', 'terrain/BaseChip_pipo.png', 8, 1064, 32, 32, 4097, TRUE, TRUE),
                ('collisions', 'terrain/collisions.png', 6, 18, 32, 32, 5161, TRUE, FALSE)
            ON CONFLICT (name) DO NOTHING
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS tileset');
    }
}
