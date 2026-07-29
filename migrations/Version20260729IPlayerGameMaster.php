<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le drapeau « maitre du jeu », porte par le personnage.
 *
 * Le staff est deja distingue au niveau du compte (`User::roles`), mais un role
 * de compte ouvre des ecrans d'administration : ce n'est pas ce qu'on cherche.
 * Animer une soiree demande un personnage qui se deplace sans attendre, ne
 * compte pas son energie et se reconnait dans une liste. Le drapeau est donc
 * sur `player`, et un meme compte peut n'en marquer qu'un de ses personnages.
 */
final class Version20260729IPlayerGameMaster extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Game master flag on player: instant regen, instant travel, visible seal in game';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS is_game_master BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS is_game_master');
    }
}
