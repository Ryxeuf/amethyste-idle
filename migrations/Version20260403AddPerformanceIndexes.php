<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403AddPerformanceIndexes extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing database indexes for N+1 query optimization';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_inventory_player ON inventory (player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_domain_experience_player ON domain_experience (player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_domain_experience_domain ON domain_experience (domain_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_mob_fight ON mob (fight_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_mob_monster ON mob (monster_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_pnj_map ON pnj (map_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_object_layer_map_type ON object_layer (map_id, type)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_game_event_status ON game_event (status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_game_event_type ON game_event (type)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_fight ON player (fight_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_user ON player (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_inventory_player');
        $this->addSql('DROP INDEX IF EXISTS idx_domain_experience_player');
        $this->addSql('DROP INDEX IF EXISTS idx_domain_experience_domain');
        $this->addSql('DROP INDEX IF EXISTS idx_mob_fight');
        $this->addSql('DROP INDEX IF EXISTS idx_mob_monster');
        $this->addSql('DROP INDEX IF EXISTS idx_pnj_map');
        $this->addSql('DROP INDEX IF EXISTS idx_object_layer_map_type');
        $this->addSql('DROP INDEX IF EXISTS idx_game_event_status');
        $this->addSql('DROP INDEX IF EXISTS idx_game_event_type');
        $this->addSql('DROP INDEX IF EXISTS idx_player_fight');
        $this->addSql('DROP INDEX IF EXISTS idx_player_user');
    }
}
