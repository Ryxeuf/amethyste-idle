<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-14b — la branche choisie dans un arbre de combat.
 *
 * GAME_ARCHETYPES § 6.1 bis : le palier 3 ecrit deux branches et une seule
 * s'apprend. **Une ligne par arbre**, jamais une par personnage — c'est la
 * lecon de DOM-04, ou une specialisation unique fermait a jamais les autres
 * metiers, c'est-a-dire l'exclusivite *entre* arbres que la doctrine interdit.
 * Mener les vingt-quatre arbres de combat reste permis ; chacun garde sa
 * fourche.
 *
 * L'exclusivite est tenue par le **schema** et non par du code : la contrainte
 * unique `(player_id, domain_id)` rend impossible d'apprendre les deux branches
 * d'un meme arbre, quelle que soit la porte par laquelle on passe.
 *
 * La table naît vide : **aucune valeur de jeu ne bouge**, et un joueur sans
 * ligne est simplement un joueur qui n'a pas encore atteint sa fourche.
 */
final class Version20260806CArcCombatBranch extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-14b: the branch a player chose in each combat tree';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS player_combat_branch (
            id SERIAL NOT NULL,
            player_id INT NOT NULL,
            domain_id INT NOT NULL,
            branch VARCHAR(40) NOT NULL,
            chosen_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_player_combat_branch ON player_combat_branch (player_id, domain_id)');

        // `ADD CONSTRAINT IF NOT EXISTS` n'existe pas en PostgreSQL : le bloc
        // conditionnel est la seule forme idempotente (cf. CLAUDE.md).
        $this->addSql("DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_combat_branch_player') THEN
                ALTER TABLE player_combat_branch ADD CONSTRAINT fk_player_combat_branch_player
                    FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
            END IF;
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_combat_branch_domain') THEN
                ALTER TABLE player_combat_branch ADD CONSTRAINT fk_player_combat_branch_domain
                    FOREIGN KEY (domain_id) REFERENCES game_domains (id) ON DELETE CASCADE;
            END IF;
        END $$");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_combat_branch');
    }
}
