<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La bande de purete d'un lot (ECO-21).
 *
 * Nullable, et c'est le cas normal : seule la ligne du cristal — amethyste,
 * minerais, gemmes — porte une bande. Tout ce que le monde livre contient
 * aujourd'hui reste donc a `NULL`, y compris les minerais deja en sac : leur
 * bande sera tiree a la recolte (ECO-22), et retro-attribuer une purete a des
 * lots qui n'en avaient pas reviendrait a inventer un passe au joueur.
 */
final class Version20260728JPlayerItemPurity extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Purity band on player items — the crystal line only, null everywhere else';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS purity VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_item DROP COLUMN IF EXISTS purity');
    }
}
