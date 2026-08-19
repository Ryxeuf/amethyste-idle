<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * FOY-20 — le retour au logis et les cheminees.
 *
 * Trois cles de jour sur la demeure, et rien d'autre : *une cle differente = un
 * autre jour*. Aucune tache de remise a zero, rien a purger.
 */
final class Version20260819HFoyHomecoming extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FOY-20 : retour au logis, grain de residence, coffre domestique';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_house ADD COLUMN IF NOT EXISTS homecoming_day_key VARCHAR(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE player_house ADD COLUMN IF NOT EXISTS homecoming_used INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE player_house ADD COLUMN IF NOT EXISTS residence_grain_day_key VARCHAR(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_house DROP COLUMN IF EXISTS residence_grain_day_key');
        $this->addSql('ALTER TABLE player_house DROP COLUMN IF EXISTS homecoming_used');
        $this->addSql('ALTER TABLE player_house DROP COLUMN IF EXISTS homecoming_day_key');
    }
}
