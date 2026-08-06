<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-16 — une accointance porte une forme, plus une statistique.
 *
 * `bonus_type`/`bonus_value` distribuaient des statistiques plates hors des 50
 * points de budget, hors des plafonds et hors des palettes. La colonne `form`
 * les remplace par la liste fermee du canon (§ 9.7).
 *
 * Les huit lignes livrees basculent toutes en `domain_expression` — la seule
 * forme dont le lecteur existe —, ce que les fixtures declarent aussi : la
 * migration ne fait que rendre une base existante coherente sans rechargement.
 */
final class Version20260806DArcAccointanceForm extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-16 : DomainSynergy porte une forme d\'accointance au lieu d\'un bonus plat.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE game_domain_synergies ADD COLUMN IF NOT EXISTS form VARCHAR(32) NOT NULL DEFAULT 'domain_expression'");
        $this->addSql('ALTER TABLE game_domain_synergies DROP COLUMN IF EXISTS bonus_type');
        $this->addSql('ALTER TABLE game_domain_synergies DROP COLUMN IF EXISTS bonus_value');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE game_domain_synergies ADD COLUMN IF NOT EXISTS bonus_type VARCHAR(32) NOT NULL DEFAULT 'damage'");
        $this->addSql('ALTER TABLE game_domain_synergies ADD COLUMN IF NOT EXISTS bonus_value INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE game_domain_synergies DROP COLUMN IF EXISTS form');
    }
}
