<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le nœud pose et pas encore ouvert (DOM-07).
 *
 * GAME_DOMAINS § 8 : chaque arbre de combat porte un **accord reserve**,
 * inactif au lancement, qui s'ouvrira quand la fusion des elements ouvrira.
 * « Poser le nœud maintenant coute une ligne de donnees et evite un refactor
 * d'arbre le jour venu. »
 *
 * **Un booleen plutot qu'une date d'ouverture.** Une date supposerait qu'on sait
 * quand la fusion arrive ; on ne le sait pas, et une date passee ouvrirait
 * vingt-quatre accords vers des materia qui n'existent pas.
 */
final class Version20260729FSkillDormant extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Skill dormant flag: a node laid down and not yet open (reserved hybrid accords)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_skills ADD COLUMN IF NOT EXISTS dormant BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_skills DROP COLUMN IF EXISTS dormant');
    }
}
