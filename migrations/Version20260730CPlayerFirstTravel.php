<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-10 — le premier voyage offert.
 *
 * L'acte I demande de rejoindre une zone pour y recolter (etape 7) bien avant
 * d'enseigner que le voyage coute du temps reel (etape 9). Sans faveur, la
 * chaine s'arrete sur une attente de quatre a dix minutes qu'aucune etape n'a
 * preparee, juste avant la premiere recolte.
 *
 * **Les personnages en place ont deja voyage.** La colonne est donc horodatee
 * pour eux : la faveur s'adresse a qui arrive, et l'offrir a un veteran ne
 * repare rien tout en creant un trajet gratuit a garder pour le bon moment.
 */
final class Version20260730CPlayerFirstTravel extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ONB-10: player.first_travel_spent_at, already spent for existing characters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS first_travel_spent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE player SET first_travel_spent_at = NOW() WHERE first_travel_spent_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS first_travel_spent_at');
    }
}
