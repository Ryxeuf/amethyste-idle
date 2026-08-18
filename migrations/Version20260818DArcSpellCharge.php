<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-18e — un geste peut construire ou depenser une charge.
 *
 * La forme **charge** (GAME_ARCHETYPES § 13.1, n° 2) : *une ressource qui se
 * construit dans la rencontre*. Les deux colonnes naissent a `0` sur les 253
 * gestes livres.
 *
 * Le **compteur**, lui, n'a pas de colonne : il vit dans les metadonnees du
 * combat, comme le registre des gestes d'ARC-06b. C'est ce qui tient le
 * garde-fou du canon sans rien avoir a effacer — *la charge meurt avec la
 * rencontre* parce qu'elle n'existe nulle part ailleurs.
 */
final class Version20260818DArcSpellCharge extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-18e : Spell porte ce qu\'un geste construit et depense en charge.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS charge_gain INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS charge_cost INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells DROP COLUMN IF EXISTS charge_gain');
        $this->addSql('ALTER TABLE game_spells DROP COLUMN IF EXISTS charge_cost');
    }
}
