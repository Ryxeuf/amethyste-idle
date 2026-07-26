<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tache 129 (HOU-04) : loyer d'entretien des demeures.
 *
 * Les demeures deja baties recoivent une echeance **a venir** et non passee :
 * introduire un loyer ne doit pas mettre retroactivement en arriere des joueurs
 * qui n'en avaient jamais entendu parler.
 */
final class Version20260726HouseRent extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player_house.rent_due_at (tache 129, HOU-04)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_house ADD COLUMN IF NOT EXISTS rent_due_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("UPDATE player_house SET rent_due_at = NOW() + INTERVAL '7 days' WHERE rent_due_at IS NULL");
        $this->addSql('ALTER TABLE player_house ALTER COLUMN rent_due_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_house DROP COLUMN IF EXISTS rent_due_at');
    }
}
