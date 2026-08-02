<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-20b-b — le rattrapage des echelons 1 d'armure.
 *
 * Meme rattrapage que Version20260730BOnbWeaponPortRungs, rejoue apres
 * l'arrivee des lignes d'armure (tissu, cuir, plaque, bouclier) : tout arbre
 * deja ouvert livre ses nœuds `port-*` gratuits, et qui porte deja un echelon
 * superieur connait l'echelon 1. Idempotent (`ON CONFLICT DO NOTHING`) — les
 * echelons d'arme deja accordes ne bougent pas.
 *
 * Les pieces deja **equipees** ne sont pas touchees : le prerequis se verifie
 * au geste d'equipement, jamais retroactivement — les personnages existants
 * gardent ce qu'ils portent.
 */
final class Version20260802KArmorPortRungs extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ONB-20b-b: grant the free armor port rungs to players whose trees are already open';
    }

    public function up(Schema $schema): void
    {
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
        // On ne sait pas distinguer un echelon 1 accorde par ce rattrapage
        // d'un echelon livre a l'ouverture d'un arbre : ne rien defaire.
    }
}
