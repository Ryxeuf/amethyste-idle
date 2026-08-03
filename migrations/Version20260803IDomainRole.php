<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-01 — la fonction, troisieme axe du domaine.
 *
 * `element` disait la couleur, `combat_register` le geste ; `combat_role` dit
 * le **role** — donc quels leviers l'arbre a le droit d'acheter
 * (GAME_ARCHETYPES § 5). Nullable comme le registre : un domaine de recolte ou
 * d'artisanat n'a pas de fonction, et le `null` dit « hors combat ».
 *
 * La colonne naît vide ; ce sont les fixtures qui rangent les 24 domaines de
 * combat selon la grille du § 10.
 */
final class Version20260803IDomainRole extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-01: combat function (role) on game domains';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_domains ADD COLUMN IF NOT EXISTS combat_role VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_domains DROP COLUMN IF EXISTS combat_role');
    }
}
