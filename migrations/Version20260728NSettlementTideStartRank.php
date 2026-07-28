<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le repere de maree du foyer (FOY-14).
 *
 * Tout le pilier des foyers se derive — le plafond de Crue se relit sur les
 * quotas, la vassalite sur le voisinage, le rang sur la somme des indices.
 * Une **photographie datee** ne se derive pas : pour dire a la cloture d'une
 * maree « ce lieu a grandi » ou « ce lieu s'est endormi », il faut avoir garde
 * le rang qu'il tenait a l'ouverture.
 *
 * `NULL` tant qu'aucune maree ne s'est achevee depuis la creation du foyer : la
 * premiere cloture pose le repere sans rien inscrire au journal. Le seed du
 * monde livre n'est l'œuvre de personne, et le crediter serait un mensonge.
 */
final class Version20260728NSettlementTideStartRank extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Settlement chronicle: keep the rank held when the current tide opened';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settlement ADD COLUMN IF NOT EXISTS tide_start_rank VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settlement DROP COLUMN IF EXISTS tide_start_rank');
    }
}
