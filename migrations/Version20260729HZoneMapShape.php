<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le contour d'une zone sur la carte illustree (ZON-16, suite).
 *
 * La carte ne portait que le **centre** de chaque zone (`map_x`/`map_y`) : de
 * quoi poser une pastille, pas de quoi designer un territoire. Le contour ouvre
 * les deux usages qui manquaient — cliquer la zone plutot que son point, et
 * percer le brouillard a la forme du terrain decouvert.
 *
 * **Du texte plutot qu'une geometrie.** La valeur part telle quelle dans
 * l'attribut `points` d'un `<polygon>` SVG ; PostGIS n'apporterait rien ici (on
 * ne fait aucune requete spatiale) et couterait une extension.
 */
final class Version20260729HZoneMapShape extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Zone map shape: the clickable outline of a zone on the illustrated world map';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone ADD COLUMN IF NOT EXISTS map_shape TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone DROP COLUMN IF EXISTS map_shape');
    }
}
