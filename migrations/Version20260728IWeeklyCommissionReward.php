<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La recompense choisie a la livraison (RET-02b).
 *
 * Le choix est garde parce qu'il **se lit** : une commission livree doit pouvoir
 * dire ce qu'elle a rendu. Sans ce champ, le joueur qui revient une semaine plus
 * tard ne saurait plus s'il a pris la bourse ou paye le tribut — et le tribut,
 * qui donne au foyer ce que le joueur aurait pris, est precisement celui dont il
 * faut se souvenir.
 *
 * Nullable : une commission ouverte n'a pas encore choisi, et une commission
 * expiree ne choisira jamais.
 */
final class Version20260728IWeeklyCommissionReward extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Weekly commission: remember which of the three rewards was taken at delivery';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_weekly_commission ADD COLUMN IF NOT EXISTS reward VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_weekly_commission DROP COLUMN IF EXISTS reward');
    }
}
