<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * OBJ-01 — la taxonomie alignee sur les 5 constantes du code.
 *
 * Le code porte 5 types (`stuff`/`gear`/`materia`/`resource`/`tool`), les
 * donnees en portaient 12 : l'onglet Materiaux, qui filtre sur `resource`,
 * cachait 57 matieres sur 91. La famille fine reste portee par le prefixe de
 * slug (cle d'`affinities.yaml` et de `purity.yaml`), jamais par un champ.
 *
 * Les objets de quete deviennent des `stuff` lies a l'obtention : la liaison
 * (`bind_type`) porte la distinction, pas un type propre.
 */
final class Version20260802CItemTaxonomy extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'OBJ-01: align game_items.type on the 5 code constants (legacy quest/food/potion/weapon/crafted/plant/ore/herb values)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE game_items SET type = 'resource' WHERE type IN ('crafted', 'plant', 'ore', 'herb')");
        // Trois matieres historiquement `stuff` : le bois et les deux peaux
        // sont des matieres de recolte, l'onglet Materiaux doit les voir.
        $this->addSql("UPDATE game_items SET type = 'resource' WHERE slug IN ('wood-log', 'leather-skin-1', 'leather-skin-2')");
        // Un objet de quete se distingue par sa liaison, pas par son type.
        $this->addSql("UPDATE game_items SET bind_type = 'bind_on_pickup' WHERE type = 'quest' AND bind_type = 'none'");
        $this->addSql("UPDATE game_items SET type = 'stuff' WHERE type IN ('quest', 'food', 'potion')");
        $this->addSql("UPDATE game_items SET type = 'gear' WHERE type = 'weapon'");
    }

    public function down(Schema $schema): void
    {
        // La distinction fine (plant/ore/herb...) n'est pas reconstructible
        // depuis le type seul — le retour arriere est un no-op assume.
    }
}
