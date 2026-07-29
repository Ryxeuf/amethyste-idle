<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La specialisation d'artisanat devient une par arbre (DOM-04).
 *
 * **Ce que la migration defait.** `player.craft_specialization` portait un
 * metier unique et irreversible : devenir Forgeron fermait a jamais la maitrise
 * du Tanneur. C'est l'exclusivite *entre* arbres, que la doctrine interdit
 * (GAME_DOMAINS § 1). Elle devient une exclusivite *dans* l'arbre, entre deux
 * branches — et c'est la contrainte unique `(player_id, craft)` qui la tient.
 *
 * **L'exclusivite est dans le schema, pas dans un service.** Un chemin de code
 * qui l'oublierait ne pourrait pas la violer, et une future commande
 * d'administration en heritera sans avoir a la connaitre.
 *
 * **La reprise des joueurs deja specialises impose une branche**, et il n'y
 * avait pas d'alternative : leur ancienne valeur designait un metier, pas une
 * branche. Elle prend la premiere branche declaree du metier — et le respec
 * existe precisement pour que ce choix impose ne soit pas definitif. Le change
 * est un gain net : ils gardent leur metier, gagnent le droit de se specialiser
 * dans les six autres, et celui de revenir sur leur branche.
 *
 * **La colonne heritee reste**, et ce n'est pas de la negligence : retirer une
 * colonne le jour meme ou on la migre ne laisse aucun recours si la reprise
 * s'est trompee. Le jeu ne la lit plus.
 */
final class Version20260729EPlayerCraftSpecialization extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Craft specialization becomes one branch per craft tree, exclusive within the tree only';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_craft_specialization (
                id SERIAL PRIMARY KEY,
                player_id INT NOT NULL,
                craft VARCHAR(20) NOT NULL,
                branch VARCHAR(40) NOT NULL,
                chosen_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
            SQL);

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_pcs_player') THEN
                    ALTER TABLE player_craft_specialization
                        ADD CONSTRAINT fk_pcs_player FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
            END $$
            SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_player_craft ON player_craft_specialization (player_id, craft)');

        // Reprise des joueurs deja specialises. La premiere branche de chaque
        // metier est celle que declare `config/game/craft_branches.yaml` ; la
        // dupliquer ici est le prix a payer pour que la migration ne depende pas
        // du conteneur de services.
        $this->addSql(<<<'SQL'
            INSERT INTO player_craft_specialization (player_id, craft, branch, chosen_at)
            SELECT p.id, p.craft_specialization, CASE p.craft_specialization
                       WHEN 'forgeron' THEN 'weapons'
                       WHEN 'alchimiste' THEN 'remedies'
                       WHEN 'tanneur' THEN 'armour'
                       WHEN 'joaillier' THEN 'focus'
                       WHEN 'cuisinier' THEN 'feast'
                       WHEN 'charpentier' THEN 'ranged'
                       WHEN 'tailleur' THEN 'spellrobes'
                   END, NOW()
            FROM player p
            WHERE p.craft_specialization IS NOT NULL
            ON CONFLICT (player_id, craft) DO NOTHING
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_craft_specialization');
    }
}
