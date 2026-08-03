<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-04a — l'ancre de regeneration des PM.
 *
 * GAME_ARCHETYPES § 9 septies : *les PV paient les coups recus, les PM paient
 * les gestes faits, et les deux se rechargent en temps reel*. Les PV ont leur
 * ancre depuis ZON-12 ; celle des PM manquait, et sans elle les PM ne
 * revenaient qu'en lancant des sorts — c'est-a-dire en depensant ce qu'on
 * cherchait a recuperer.
 *
 * `NULL` par defaut : la premiere lecture pose l'ancre sans rien crediter, ce
 * qui evite qu'un compte dormant depuis des semaines se reveille plein pour
 * une raison qui n'est pas du jeu.
 */
final class Version20260803LManaRegenAnchor extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-04: out-of-combat mana (combat energy) regeneration anchor';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS energy_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS energy_updated_at');
    }
}
