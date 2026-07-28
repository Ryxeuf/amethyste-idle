<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La bande survit a l'attente de l'etabli (ECO-26).
 *
 * L'etabli est temporise depuis ECO-20 : les ingredients sont consommes **au
 * lancement** et l'objet nait a la collecte. Sans ce champ, la bande heritee des
 * intrants mourrait entre les deux, et la propagation ne survivrait que sur le
 * chemin de fabrication immediate — celui qu'aucune route n'expose.
 *
 * `NULL` est l'etat normal : l'immense majorite de l'artisanat ne consomme
 * aucune matiere qui porte une bande.
 */
final class Version20260728PCraftJobPurity extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Purity propagation: a craft job carries the band inherited from its inputs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE craft_job ADD COLUMN IF NOT EXISTS purity VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE craft_job DROP COLUMN IF EXISTS purity');
    }
}
