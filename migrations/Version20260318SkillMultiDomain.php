<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318SkillMultiDomain extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert Skill.domain ManyToOne to ManyToMany via skill_domain join table';
    }

    public function up(Schema $schema): void
    {
        // 1. Create join table
        $this->addSql('CREATE TABLE IF NOT EXISTS skill_domain (
            skill_id INTEGER NOT NULL,
            domain_id INTEGER NOT NULL,
            PRIMARY KEY (skill_id, domain_id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_skill_domain_skill ON skill_domain (skill_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_skill_domain_domain ON skill_domain (domain_id)');
        $this->addSql('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'fk_skill_domain_skill\') THEN ALTER TABLE skill_domain ADD CONSTRAINT FK_skill_domain_skill FOREIGN KEY (skill_id) REFERENCES game_skills (id) ON DELETE CASCADE; END IF; END $$');
        $this->addSql('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'fk_skill_domain_domain\') THEN ALTER TABLE skill_domain ADD CONSTRAINT FK_skill_domain_domain FOREIGN KEY (domain_id) REFERENCES game_domains (id) ON DELETE CASCADE; END IF; END $$');

        // 2. Migrate existing data (only if old column still exists)
        $this->addSql('DO $$ BEGIN IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = \'game_skills\' AND column_name = \'domain_id\') THEN INSERT INTO skill_domain (skill_id, domain_id) SELECT id, domain_id FROM game_skills WHERE domain_id IS NOT NULL ON CONFLICT DO NOTHING; END IF; END $$');

        // 3. Drop old column
        $this->addSql('ALTER TABLE game_skills DROP CONSTRAINT IF EXISTS fk_game_skills_domain');
        $this->addSql('ALTER TABLE game_skills DROP COLUMN IF EXISTS domain_id');
    }

    public function down(Schema $schema): void
    {
        // 1. Re-add column
        $this->addSql('ALTER TABLE game_skills ADD COLUMN domain_id INTEGER DEFAULT NULL');

        // 2. Migrate data back (first domain only)
        $this->addSql('UPDATE game_skills SET domain_id = sd.domain_id FROM (SELECT DISTINCT ON (skill_id) skill_id, domain_id FROM skill_domain) sd WHERE game_skills.id = sd.skill_id');

        // 3. Re-add FK
        $this->addSql('ALTER TABLE game_skills ADD CONSTRAINT fk_game_skills_domain FOREIGN KEY (domain_id) REFERENCES game_domains (id)');

        // 4. Drop join table
        $this->addSql('DROP TABLE skill_domain');
    }
}
