<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * OBJ-07 — le champignon devient une matiere.
 *
 * Le butin le plus frequent du jeu (une vingtaine de tables) n'avait aucun
 * debouche : OBJ-01 l'avait range en `stuff` avec la nourriture, mais il
 * n'a jamais ete comestible — aucun effet. Il rejoint la ligne du cuisinier
 * (la fricassee) et la base de potion de l'alchimiste, donc l'onglet
 * Materiaux doit le voir : `resource`, comme toute matiere premiere.
 */
final class Version20260802HMushroomResource extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'OBJ-07: mushroom becomes a resource (cook + alchemy entry material)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE game_items SET type = 'resource' WHERE slug = 'mushroom'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE game_items SET type = 'stuff' WHERE slug = 'mushroom'");
    }
}
