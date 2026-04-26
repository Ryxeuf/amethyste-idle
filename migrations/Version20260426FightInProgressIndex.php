<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260426FightInProgressIndex extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add partial index idx_fight_in_progress on fight(in_progress) WHERE in_progress = true (task 134 sous-phase 3d, complete jalon C indexes for /metrics)';
    }

    public function up(Schema $schema): void
    {
        // Accelere COUNT(f.id) FROM fight f WHERE f.inProgress = true
        // (gauge `fights_active` collecte par MetricsController::collectGameGauges
        // via $em->getRepository(Fight::class)->count(['inProgress' => true])).
        // Partial index : in_progress=false represente >99% des lignes (combats
        // historiques), seul in_progress=true est utile a indexer pour la gauge.
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_fight_in_progress ON fight (in_progress) WHERE in_progress = true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_fight_in_progress');
    }
}
