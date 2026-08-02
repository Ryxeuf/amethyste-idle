<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * DON-01b — le chemin solo des donjons disparait.
 *
 * `DungeonRun` (entree par teleportation sur une `Map`, coordonnees
 * d'origine, difficulte par cooldown) appartenait au modele d'avant le pivot
 * PBBG. Le modele unique (DON-01) passe par `GroupDungeonRun` pour tous les
 * donjons, solo compris — la table n'a plus ni lecteur ni ecrivain.
 */
final class Version20260802FDropDungeonRun extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DON-01b: drop dungeon_run (dead solo path — the single zone-dungeon model goes through group_dungeon_run)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS dungeon_run');
    }

    public function down(Schema $schema): void
    {
        // La table appartenait a un modele supprime — pas de retour arriere.
    }
}
