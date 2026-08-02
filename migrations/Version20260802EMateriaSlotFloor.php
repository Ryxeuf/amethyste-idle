<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * OBJ-04 — les emplacements de materia progressent avec le palier.
 *
 * GAME_WORLD §2.1 promet que « l'equipement de haut niveau offre plus
 * d'emplacements » ; le jeu livre ne le tenait pas (t1=1, t2=1, t3=1-2).
 * Le plancher par palier devient 1 / 2 / 3 (bandes de niveau 1-4 / 5-12 /
 * 13+) — un plancher, jamais un ecretage : les pieces uniques au-dessus
 * gardent leur avance.
 *
 * Et les armes de lanceur au-dessus du palier d'entree portent des
 * emplacements de sort (GAME_ITEMS §3.4) — le versant Technique de la
 * derivation attend les materia de technique (ARC), sans quoi l'emplacement
 * serait un mur que rien ne remplit.
 */
final class Version20260802EMateriaSlotFloor extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'OBJ-04: materia slots floor per tier band on game_items gear; launcher weapons above entry tier carry spell sockets';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE game_items SET materia_slots = GREATEST(materia_slots, 1) WHERE type = 'gear' AND level <= 4");
        $this->addSql("UPDATE game_items SET materia_slots = GREATEST(materia_slots, 2) WHERE type = 'gear' AND level BETWEEN 5 AND 12");
        $this->addSql("UPDATE game_items SET materia_slots = GREATEST(materia_slots, 3) WHERE type = 'gear' AND level >= 13");
        $this->addSql("UPDATE game_items SET materia_slot_type = 'spell' WHERE slug IN ('t2-staff', 't3-staff', 'guardian-thorn-staff') AND materia_slot_type IS NULL");
    }

    public function down(Schema $schema): void
    {
        // Le plancher n'est pas reversible piece par piece — no-op assume.
    }
}
