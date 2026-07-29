<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ce qu'un emplacement de materia accepte (DOM-03).
 *
 * **Nullable, et le `null` vaut « libre ».** C'est ce qui rend le typage
 * additif : les 121 pieces livrees continuent de se comporter comme avant tant
 * que personne ne les type, et le plancher jour 1 — « la premiere materia se
 * sertit toujours, quelle que soit la tenue » — tient sans qu'on ait a l'ecrire
 * piece par piece. Un defaut `'free'` en base aurait dit la meme chose et coute
 * une ecriture sur chaque ligne existante.
 *
 * **Un type par piece, pas un par emplacement** : les emplacements n'ont pas
 * d'indice, et panacher aurait fait dependre le sertissage de l'ordre des
 * identifiants.
 */
final class Version20260729DItemMateriaSlotType extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Item materia slot type: a piece declares what its sockets accept (spell / technique / free)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items ADD COLUMN IF NOT EXISTS materia_slot_type VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS materia_slot_type');
    }
}
