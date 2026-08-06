<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-06b — le reste du gain de points, en quarts.
 *
 * ARC-06a a pose la table (`DomainPointYield`) et l'a laissee sans canal : le
 * combat ne rapportait **aucun** point de domaine. Le brancher exige un
 * endroit ou garder ce qui n'a pas encore fait un point entier, puisque la
 * table descend a 0,25 : sans ce reste, un joueur qui chasse du palier 1
 * gagnerait zero point par rencontre, arrondi apres arrondi, et la regle
 * « on ne monte pas un arbre en tapant des rats » deviendrait « on ne monte
 * pas un arbre en tapant des rats, jamais, meme en y passant sa vie ».
 *
 * La colonne vaut toujours 0, 1, 2 ou 3 : au quatrieme quart elle rend un
 * point a `total_experience` et repart. **Aucune valeur de jeu ne bouge** —
 * elle naît a zero pour tout le monde.
 */
final class Version20260806AArcDomainPointQuarters extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-06b: carry the quarter-point remainder of combat domain gains';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain_experience ADD COLUMN IF NOT EXISTS experience_quarters INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domain_experience DROP COLUMN IF EXISTS experience_quarters');
    }
}
