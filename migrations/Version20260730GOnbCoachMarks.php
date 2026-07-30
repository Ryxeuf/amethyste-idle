<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-17 — les encarts de coach deja lus.
 *
 * Un tableau JSON sur `player`, et **pas une table** : il n'y a rien a
 * interroger, rien a dater, rien a joindre. Une table pour dix booleens par
 * personnage couterait une jointure a chaque rendu d'ecran sans jamais servir a
 * autre chose.
 *
 * Le tableau naît vide pour tout le monde, joueurs existants compris. Ils
 * verront donc les encarts a leur prochaine visite de chaque ecran — ce qui est
 * la bonne valeur : ils ne les ont effectivement jamais lus.
 *
 * Le suffixe `G` la trie apres les six migrations du meme jour (cf. la section
 * « Pieges courants » de CLAUDE.md).
 */
final class Version20260730GOnbCoachMarks extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ONB-17: player.seen_coach_marks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE player ADD COLUMN IF NOT EXISTS seen_coach_marks JSON NOT NULL DEFAULT '[]'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS seen_coach_marks');
    }
}
