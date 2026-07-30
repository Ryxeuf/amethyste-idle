<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-08 — l'arbre ouvert pour un personnage.
 *
 * Le modele n'avait pas cette notion : un domaine etait un catalogue de nœuds
 * que quiconque avait les points pouvait prendre. Le parchemin fait de l'entree
 * dans un metier un acte, et cette table en garde la trace.
 *
 * Le suffixe `A` du nom de version n'est pas decoratif : Doctrine trie les
 * migrations par **ordre alphabetique** du nom, pas par heure de creation. Cette
 * migration cree une table que d'autres du meme jour pourraient referencer ;
 * elle doit donc se trier avant elles (cf. la section « Pieges courants » de
 * CLAUDE.md, et la panne de production du 2026-07-27).
 *
 * **Le grand-perisage est la moitie importante de ce fichier.** ONB-08 change
 * un comportement en place : sans l'`INSERT ... SELECT` ci-dessous, un joueur
 * qui minait la veille se reveillerait incapable d'apprendre quoi que ce soit
 * dans l'arbre du mineur, et devrait racheter le droit d'exercer son metier.
 * La regle retenue est la plus large qui reste vraie : **tout arbre dont le
 * personnage porte deja une competence ou une experience de domaine est
 * ouvert**. Elle sur-ouvre volontairement — ouvrir un arbre de trop ne coute
 * qu'un parchemin non vendu, en fermer un de trop bloque un joueur.
 *
 * `PlayerDomainAccessFixtures` rejoue le meme enonce, pour que les tests ne
 * decrivent pas un monde plus permissif que la base.
 */
final class Version20260730AOnbDomainAccess extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ONB-08: player_domain_access + grandfathering of already practised trees';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_domain_access (
                id SERIAL NOT NULL,
                player_id INT NOT NULL,
                domain_id INT NOT NULL,
                opened_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_player_domain_access ON player_domain_access (player_id, domain_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_domain_access_player ON player_domain_access (player_id)');

        // PostgreSQL ne connait pas `ADD CONSTRAINT IF NOT EXISTS` : le bloc
        // anonyme est le seul moyen de rendre la pose idempotente.
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_domain_access_player') THEN
                    ALTER TABLE player_domain_access
                        ADD CONSTRAINT fk_player_domain_access_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
            END $$
        SQL);

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_domain_access_domain') THEN
                    ALTER TABLE player_domain_access
                        ADD CONSTRAINT fk_player_domain_access_domain
                        FOREIGN KEY (domain_id) REFERENCES game_domains (id) ON DELETE CASCADE;
                END IF;
            END $$
        SQL);

        // Grand-perisage 1/2 — les arbres dont le personnage porte une competence.
        $this->addSql(<<<'SQL'
            INSERT INTO player_domain_access (player_id, domain_id, opened_at)
            SELECT DISTINCT ps.player_id, sd.domain_id, NOW()
            FROM player_skill ps
            JOIN skill_domain sd ON sd.skill_id = ps.skill_id
            ON CONFLICT (player_id, domain_id) DO NOTHING
        SQL);

        // Grand-perisage 2/2 — les arbres ou il a de l'experience, meme sans
        // competence prise : avoir mine sans rien depenser reste avoir mine.
        $this->addSql(<<<'SQL'
            INSERT INTO player_domain_access (player_id, domain_id, opened_at)
            SELECT DISTINCT de.player_id, de.domain_id, NOW()
            FROM domain_experience de
            WHERE de.player_id IS NOT NULL AND de.domain_id IS NOT NULL
            ON CONFLICT (player_id, domain_id) DO NOTHING
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_domain_access');
    }
}
