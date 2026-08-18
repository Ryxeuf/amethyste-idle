<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-18b — une posture deplace des leviers, jamais une statistique plate.
 *
 * `stat_modifier` existait et aurait ete le rangement evident ; son vocabulaire
 * est **ouvert** (les quinze statuts livres y ecrivent `damage`, `speed`,
 * `defense`, `shield_absorb`, `max_life`, et aussi quatre noms de leviers), et
 * *un systeme qui compte 50 points de budget et laisse a cote un champ ou l'on
 * ecrit n'importe quel chiffre ne compte rien* (la lecon d'ARC-16a).
 *
 * La colonne nait **vide sur les quinze statuts livres** : aucune valeur de jeu
 * ne bouge, la place est faite avant qu'il y ait quelque chose a relire.
 */
final class Version20260818AArcStanceLevers extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-18b : StatusEffect porte les leviers de budget qu\'une posture deplace.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_status_effects ADD COLUMN IF NOT EXISTS levers JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_status_effects DROP COLUMN IF EXISTS levers');
    }
}
