<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le depart du voyage, pour que la barre sache d'ou elle vient.
 *
 * Le voyage ne conservait que son terme (`travel_arrives_at`) : suffisant pour
 * dire « arrivee dans 5 min », insuffisant pour situer le chemin deja parcouru,
 * la liaison empruntee n'etant pas conservee. Cette colonne porte le depart ;
 * la duree totale se lit alors comme la difference des deux.
 *
 * **Voyages deja en cours** : la colonne reste nulle pour eux. L'affichage
 * retombe sur le decompte seul (sans barre) jusqu'a leur arrivee — degrade
 * volontaire plutot qu'un depart invente qui mentirait sur la progression.
 */
final class Version20260729HPlayerTravelStartedAt extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Player travel start timestamp: lets the travel screen show elapsed vs remaining time';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS travel_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("COMMENT ON COLUMN player.travel_started_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS travel_started_at');
    }
}
