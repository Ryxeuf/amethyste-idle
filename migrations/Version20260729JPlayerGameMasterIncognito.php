<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * L'incognito du maitre du jeu.
 *
 * Le sceau « MJ » repond a un besoin — qu'on reconnaisse l'animateur — mais il
 * en cree un autre : un MJ visible modifie ce qu'il observe. Arbitrer un litige
 * ou regarder un joueur bloque demande de pouvoir se retirer des ecrans, et d'y
 * revenir sans passer par un administrateur. Le mode est donc porte par le
 * personnage et se bascule a volonte depuis le jeu.
 */
final class Version20260729JPlayerGameMasterIncognito extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Game master incognito mode: lets a GM step out of other players\' screens at will';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS is_game_master_incognito BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS is_game_master_incognito');
    }
}
