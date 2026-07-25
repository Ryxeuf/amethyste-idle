<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Supprime la table `tileset` : le rendu carte PixiJS et l'editeur de carte
 * admin ont ete retires (ZON-21). Aucune FK entrante (Map/Area ne referencent
 * pas Tileset).
 */
final class Version20260725DropTileset extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop tileset table (map rendering + admin map editor removed, ZON-21c)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS tileset');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS tileset (
                id SERIAL NOT NULL,
                name VARCHAR(100) NOT NULL,
                image_path VARCHAR(500) NOT NULL,
                columns_count INT NOT NULL,
                tile_count INT NOT NULL,
                tile_width INT DEFAULT 32 NOT NULL,
                tile_height INT DEFAULT 32 NOT NULL,
                first_gid INT NOT NULL,
                is_builtin BOOLEAN DEFAULT false NOT NULL,
                is_editable BOOLEAN DEFAULT true NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS tileset_name_uniq ON tileset (name)');
    }
}
