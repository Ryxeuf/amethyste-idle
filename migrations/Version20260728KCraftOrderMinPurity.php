<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La bande de purete exigee par une commande (ECO-23).
 *
 * Repond a la question laissee ouverte par GAME_PRINCIPLES § 6 : un client peut
 * exiger une matiere d'une certaine purete. C'est ce qui donne au prospecteur un
 * **client** et pas seulement un marche — sans cette exigence, la bande n'aurait
 * de valeur qu'a la revente.
 *
 * Nullable : la plupart des commandes ne demandent rien de particulier, et une
 * exigence par defaut fermerait le plancher T1 aux debutants.
 */
final class Version20260728KCraftOrderMinPurity extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Craft orders can demand a minimum purity band from the materials';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE craft_order ADD COLUMN IF NOT EXISTS min_purity VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE craft_order DROP COLUMN IF EXISTS min_purity');
    }
}
