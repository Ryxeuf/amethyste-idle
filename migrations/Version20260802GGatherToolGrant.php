<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * OBJ-05 — la recolte exige un outil ; l'outil de palier 1 est offert avec
 * l'arbre.
 *
 * L'octroi vaut pour toute ouverture **a venir** (DomainAccessManager), mais
 * les personnages existants ont ete grand-perises par ONB-08 : leurs arbres de
 * recolte sont deja ouverts, l'ouverture ne repassera jamais par le code. Sans
 * rattrapage, ils se presenteraient au filon les mains vides — exactement le
 * mur que la garantie de GAME_ITEMS §4.3 interdit.
 *
 * La regle est celle du code : un outil de bronze par arbre de recolte ouvert,
 * seulement si le joueur ne possede aucun outil du type. L'outil arrive au sac
 * (gear = 0) : la recolte accepte un outil non equipe, et poser un bit
 * d'equipement en SQL risquerait d'entrer en conflit avec un outil deja porte.
 */
final class Version20260802GGatherToolGrant extends AbstractMigration
{
    private const GRANTS = [
        ['Mineur', 'pickaxe-bronze', 'pickaxe'],
        ['Herboriste', 'sickle-bronze', 'sickle'],
        ['Pêcheur', 'fishing-rod-bronze', 'fishing_rod'],
        ['Dépeceur', 'skinning-knife-bronze', 'skinning_knife'],
        ['Bûcheron', 'axe-bronze', 'axe'],
    ];

    public function getDescription(): string
    {
        return 'OBJ-05: grant the bronze gathering tool to players whose gathering tree is already open and who own no tool of the type';
    }

    public function up(Schema $schema): void
    {
        foreach (self::GRANTS as [$domainTitle, $itemSlug, $toolType]) {
            $this->addSql(<<<SQL
                INSERT INTO player_item (item_id, inventory_id, gear, nb_usages, experience, created_at, updated_at)
                SELECT gi.id, inv.id, 0, -1, 0, NOW(), NOW()
                FROM player_domain_access pda
                JOIN game_domains d ON d.id = pda.domain_id AND d.title = :domainTitle
                JOIN inventory inv ON inv.player_id = pda.player_id AND inv.type = 1
                JOIN game_items gi ON gi.slug = :itemSlug
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM player_item pi
                    JOIN inventory i2 ON i2.id = pi.inventory_id AND i2.player_id = inv.player_id
                    JOIN game_items g2 ON g2.id = pi.item_id
                    WHERE g2.tool_type = :toolType
                )
                SQL, ['domainTitle' => $domainTitle, 'itemSlug' => $itemSlug, 'toolType' => $toolType]);
        }
    }

    public function down(Schema $schema): void
    {
        // Rattrapage de donnees : rien a defaire — on ne sait pas distinguer
        // un outil offert d'un outil achete, et retirer un outil possiblement
        // en usage casserait des joueurs.
    }
}
