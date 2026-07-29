<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La doctrine d'un foyer (FOY-13).
 *
 * **Une colonne, pas deux booleens.** Les ateliers de la Fonderie et des
 * Lecteurs sont exclusifs par construction : aucun chemin de code ne peut les
 * cumuler, parce que le schema ne le permet pas. Le plan disait « la guilde
 * choisit, elle ne cumule pas » — c'est ici que la phrase devient vraie.
 *
 * `doctrine_since` date le choix : le verrou de basculement se lit dessus, et
 * une doctrine qui se retournerait a la semaine ne diviserait plus personne.
 */
final class Version20260729ASettlementDoctrine extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Settlement doctrine: a guild picks Foundry or Readers for a settlement, never both';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settlement ADD COLUMN IF NOT EXISTS doctrine VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE settlement ADD COLUMN IF NOT EXISTS doctrine_since TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settlement DROP COLUMN IF EXISTS doctrine');
        $this->addSql('ALTER TABLE settlement DROP COLUMN IF EXISTS doctrine_since');
    }
}
