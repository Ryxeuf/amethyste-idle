<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tache 129 (HOU-05) : ameublement et devise d'une demeure.
 *
 * Les demeures existantes restent `bare` : personne ne se reveille meuble
 * autrement qu'il ne l'a choisi.
 */
final class Version20260726HouseFurnishing extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player_house.style and motto (tache 129, HOU-05)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE player_house ADD COLUMN IF NOT EXISTS style VARCHAR(20) NOT NULL DEFAULT 'bare'");
        $this->addSql('ALTER TABLE player_house ADD COLUMN IF NOT EXISTS motto VARCHAR(140) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_house DROP COLUMN IF EXISTS motto');
        $this->addSql('ALTER TABLE player_house DROP COLUMN IF EXISTS style');
    }
}
