<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * DOM-10 — un arbre peut etre hors du registre public.
 *
 * Il n'a pas d'entree au catalogue et pas de vendeur : il ne s'ouvre que par un
 * parchemin **retrouve**, remis par une rencontre qu'un accomplissement
 * declenche. Le defaut est `false` — tous les arbres livres restent au registre.
 */
final class Version20260819BDomOffRegister extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DOM-10 : Domain porte un drapeau « hors registre ».';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_domains ADD COLUMN IF NOT EXISTS off_register BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_domains DROP COLUMN IF EXISTS off_register');
    }
}
