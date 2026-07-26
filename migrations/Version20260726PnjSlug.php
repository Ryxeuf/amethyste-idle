<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ZON-26b-b : identite declarative des PNJ.
 *
 * Un `Pnj` n'atteignait sa zone que par une carte (`WorldEntityZoneListener`
 * derive `Pnj.zone` de `Pnj.map`). Une zone sans carte d'origine — toute zone
 * nouvelle depuis ZON-21 — ne pouvait donc avoir aucun habitant.
 *
 * Le slug est **nullable** : les PNJ historiques vivent dans sept fichiers de
 * fixtures et n'en portent pas. Il n'est requis que pour un PNJ declare dans
 * `config/game/zones/*.yaml`, ou il sert de cle d'idempotence a l'import.
 */
final class Version20260726PnjSlug extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pnj.slug for declarative zone population (ZON-26b-b)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pnj ADD COLUMN IF NOT EXISTS slug VARCHAR(80) DEFAULT NULL');
        // Index partiel : plusieurs centaines de PNJ historiques ont un slug
        // nul, et un unique classique les compterait comme des doublons sur
        // certains moteurs. PostgreSQL ignore les nuls, mais le WHERE rend
        // l'intention explicite et allege l'index.
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_pnj_slug ON pnj (slug) WHERE slug IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_pnj_slug');
        $this->addSql('ALTER TABLE pnj DROP COLUMN IF EXISTS slug');
    }
}
