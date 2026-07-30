<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-20b — l'echelon 1 du port des armes, retroactif.
 *
 * Aucun changement de schema : l'echelle de port n'invente aucune table, elle
 * se sert de `Item::requirements` et de `player_skill`, qui existaient tous
 * deux. Cette migration ne fait que **rattraper les personnages en place**.
 *
 * Le suffixe `B` la trie apres `Version20260730AOnbDomainAccess`, dont elle lit
 * la table `player_domain_access` (cf. la section « Pieges courants » de
 * CLAUDE.md : Doctrine trie les migrations par ordre alphabetique du nom).
 *
 * **Pourquoi un rattrapage est indispensable.** Les armes de palier 1 n'avaient
 * aucun prerequis : tout le monde pouvait les tenir. Leur en poser un
 * desarmerait, du jour au lendemain, des joueurs qui se battaient la veille.
 * La regle appliquee est celle du jalon lui-meme — *ouvrir un arbre livre son
 * kit de port* — jouee en arriere sur les arbres deja ouverts, plus un filet
 * pour ceux qui portent deja un echelon superieur de la famille.
 *
 * Rien n'est retire a personne : la migration n'ecrit que des `INSERT`.
 */
final class Version20260730BOnbWeaponPortRungs extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ONB-20b: grandfather the free weapon port rungs onto already-opened trees';
    }

    public function up(Schema $schema): void
    {
        // 1/2 — Tout arbre deja ouvert livre son kit de port, comme il le fera
        // desormais a chaque ouverture.
        $this->addSql(<<<'SQL'
            INSERT INTO player_skill (player_id, skill_id)
            SELECT DISTINCT pda.player_id, sd.skill_id
            FROM player_domain_access pda
            JOIN skill_domain sd ON sd.domain_id = pda.domain_id
            JOIN game_skills s ON s.id = sd.skill_id
            WHERE s.slug LIKE 'port-%'
              AND s.required_points = 0
            ON CONFLICT DO NOTHING
        SQL);

        // 2/2 — Le filet : qui porte deja un echelon superieur d'une famille en
        // connait evidemment l'echelon 1. Le cas se produit des qu'un arbre a
        // ete referme ou n'a jamais ete formellement ouvert — sans ce filet,
        // un joueur perdrait l'arme T2 qu'il porte au profit de rien.
        $this->addSql(<<<'SQL'
            INSERT INTO player_skill (player_id, skill_id)
            SELECT DISTINCT ps.player_id, rung1.id
            FROM player_skill ps
            JOIN skill_requirement sr ON sr.requirement_id = ps.skill_id
            JOIN game_skills rung1 ON rung1.id = sr.achievement_id
            WHERE rung1.slug LIKE 'port-%'
              AND rung1.required_points = 0
            ON CONFLICT DO NOTHING
        SQL);
    }

    public function down(Schema $schema): void
    {
        // On retire les echelons 1 accordes, jamais les competences payees.
        $this->addSql(<<<'SQL'
            DELETE FROM player_skill
            WHERE skill_id IN (
                SELECT id FROM game_skills WHERE slug LIKE 'port-%' AND required_points = 0
            )
        SQL);
    }
}
