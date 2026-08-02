<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * FAC-02 — le plafond journalier des gestes, materialise sur la ligne de
 * reputation.
 *
 * La reputation n'a pas de journal (contrairement a l'influence de guilde et
 * son `influence_log`) : un couple (cle de jour, cumul) sur `player_factions`
 * suffit — le compteur repart de zero des que la cle change, sans cron.
 */
final class Version20260802LFactionDailyCap extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FAC-02: daily gesture-reputation cap counters on player_factions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_factions ADD COLUMN IF NOT EXISTS daily_gesture_gained INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE player_factions ADD COLUMN IF NOT EXISTS daily_gesture_key VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_factions DROP COLUMN IF EXISTS daily_gesture_gained');
        $this->addSql('ALTER TABLE player_factions DROP COLUMN IF EXISTS daily_gesture_key');
    }
}
