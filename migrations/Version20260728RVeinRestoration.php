<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le chantier de restauration d'un filon pali (FOY-12).
 *
 * La table **est** la trace comptable. Le plan demandait une ligne au
 * `GuildVaultLog`, mais ce registre exige un `item_id` non nul : c'est un
 * journal d'objets, pas de Gils. Chaque ligne porte donc elle-meme la guilde
 * qui a paye, le montant verse, la Paleur au moment de l'ouverture — ce sur
 * quoi le prix a ete indexe — et la date de fin du chantier.
 *
 * `ends_at` est ce qui interdit d'acheter un monde propre : le chantier
 * accelere la guerison pendant sa duree, il ne la remplace pas.
 */
final class Version20260728RVeinRestoration extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Vein restoration: a guild pays its treasury so a dulled vein mends faster';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS vein_restoration (
            id SERIAL PRIMARY KEY,
            zone_id INT NOT NULL,
            vein_slug VARCHAR(64) NOT NULL,
            guild_id INT NOT NULL,
            cost_gils INT NOT NULL,
            paleness_at_start DOUBLE PRECISION NOT NULL,
            ends_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vein_restoration_vein ON vein_restoration (zone_id, vein_slug)');

        // `ADD CONSTRAINT IF NOT EXISTS` n'existe pas en PostgreSQL : le bloc
        // conditionnel est la seule facon d'ecrire une contrainte idempotente.
        $this->addSql("DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_vein_restoration_zone') THEN
                ALTER TABLE vein_restoration ADD CONSTRAINT fk_vein_restoration_zone
                    FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE;
            END IF;
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_vein_restoration_guild') THEN
                ALTER TABLE vein_restoration ADD CONSTRAINT fk_vein_restoration_guild
                    FOREIGN KEY (guild_id) REFERENCES guild (id) ON DELETE CASCADE;
            END IF;
        END $$");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS vein_restoration');
    }
}
