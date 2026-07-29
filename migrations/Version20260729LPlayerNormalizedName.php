<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-06 — l'unicite d'un nom de personnage cesse d'etre une question d'octets.
 *
 * `player.name` portait bien une contrainte d'unicite, mais PostgreSQL compare
 * des octets : « Claire » et « claire » etaient deux personnages, et « Clairе »
 * ecrit avec un « е » cyrillique en etait un troisieme, indiscernable a l'œil.
 *
 * La colonne normalisee porte desormais l'index unique reel. Elle est remplie
 * ici par un repli SQL — minuscules, accents ramenes a leur base, tout ce qui
 * n'est ni lettre ni chiffre retire — volontairement plus grossier que
 * `PlayerNameNormalizer` (qui traite en plus les homoglyphes cyrilliques et
 * grecs). L'ecart est assume : le repli doit tenir dans une migration, et il ne
 * peut que **sous**-normaliser, donc creer moins de collisions que le service.
 * Les noms deja poses restent valides ; seules les creations suivantes passent
 * par le service complet.
 *
 * L'index est cree en dernier, apres remplissage : l'ordre inverse echouerait
 * sur la premiere paire de doublons deja en base.
 */
final class Version20260729LPlayerNormalizedName extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Case- and homoglyph-insensitive uniqueness for character names';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS normalized_name VARCHAR(255) DEFAULT NULL');

        // unaccent n'est pas garanti present : on plie les accents a la main.
        $this->addSql(<<<'SQL'
            UPDATE player
            SET normalized_name = regexp_replace(
                translate(
                    lower(name),
                    'àáâãäåçèéêëìíîïñòóôõöùúûüýÿ',
                    'aaaaaaceeeeiiiinooooouuuuyy'
                ),
                '[^a-z0-9]', '', 'g'
            )
            WHERE normalized_name IS NULL
        SQL);

        // Un doublon deja en base ferait echouer l'index : on desambigue par
        // l'identifiant, qui est unique par construction. Personne ne perd son
        // nom d'affichage — seule la forme de comparaison bouge.
        $this->addSql(<<<'SQL'
            UPDATE player p
            SET normalized_name = p.normalized_name || p.id::text
            WHERE EXISTS (
                SELECT 1 FROM player o
                WHERE o.normalized_name = p.normalized_name AND o.id < p.id
            )
        SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_player_normalized_name ON player (normalized_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_player_normalized_name');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS normalized_name');
    }
}
