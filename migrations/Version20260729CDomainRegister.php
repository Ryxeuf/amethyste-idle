<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le registre de combat d'un domaine (DOM-01).
 *
 * Avec `element`, il fait du domaine une **case** : le pyromancien est
 * `feu x sorts`, le berserker `feu x melee`. C'est le domaine qui porte la
 * borne, jamais le nœud — les 130 passifs livres se typent d'un coup, sans
 * qu'aucune decision ne se prenne competence par competence.
 *
 * **Nullable, et le `null` dit « hors combat »** — pas « registre inconnu ».
 * La recolte et l'artisanat n'ont pas de registre (GAME_DOMAINS § 2) : leurs
 * passifs sont bornes a leur metier, c'est-a-dire au domaine lui-meme. Les
 * lignes vides d'une base existante decrivent donc exactement les domaines qui
 * doivent le rester, et le chargement des fixtures pose les autres.
 */
final class Version20260729CDomainRegister extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Domain combat register: element x register makes a combat domain a cell, and bounds its passives';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_domains ADD COLUMN IF NOT EXISTS combat_register VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_domains DROP COLUMN IF EXISTS combat_register');
    }
}
