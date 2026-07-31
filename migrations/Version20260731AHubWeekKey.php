<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * RET-09 — la semaine dont le tableau de bord a deja ete vu.
 *
 * Une colonne, pas une table : le lundi se constate en comparant deux clefs de
 * semaine, et l'horizon hebdomadaire compte deja cinq briques qu'aucune tache
 * planifiee ne doit venir sixiemement doubler (contrat RET-07).
 *
 * **Les personnages en place restent a NULL.** Ils verront donc, a leur
 * premiere visite, le bloc compact et rien d'autre : la colonne ne sait pas ce
 * qu'ils ont fait la semaine derniere, et un recap devine serait pire que pas
 * de recap du tout. Le suivant, lui, sera exact.
 */
final class Version20260731AHubWeekKey extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RET-09: player.hub_week_key, null for existing characters (no invented recap)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS hub_week_key VARCHAR(8) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS hub_week_key');
    }
}
