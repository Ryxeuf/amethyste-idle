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
        $this->addSql('CREATE TABLE skill_domain (
            skill_id INTEGER NOT NULL,
            domain_id INTEGER NOT NULL,
            PRIMARY KEY (skill_id, domain_id)
        )');
        $this->addSql('CREATE INDEX IDX_skill_domain_skill ON skill_domain (skill_id)');
        $this->addSql('CREATE INDEX IDX_skill_domain_domain ON skill_domain (domain_id)');
        $this->addSql('ALTER TABLE skill_domain ADD CONSTRAINT FK_skill_domain_skill FOREIGN KEY (skill_id) REFERENCES game_skills (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill_domain ADD CONSTRAINT FK_skill_domain_domain FOREIGN KEY (domain_id) REFERENCES game_domains (id) ON DELETE CASCADE');

        // 2. Migrate existing data
        $this->addSql('INSERT INTO skill_domain (skill_id, domain_id) SELECT id, domain_id FROM game_skills WHERE domain_id IS NOT NULL');

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
