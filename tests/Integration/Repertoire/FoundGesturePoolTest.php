<?php

namespace App\Tests\Integration\Repertoire;

use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\GameEngine\Repertoire\RepertoireCatalog;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * La regle laterale, verifiee contre le catalogue reel (REP-02).
 *
 * GAME_WORLD § 12.3 b, regle 2 : *« la regle laterale s'applique integralement :
 * les chemins divergent en **options**, jamais en **puissance** »*.
 *
 * Le chargeur tient la moitie de cette loi par la forme du fichier — il n'y a
 * aucun champ ou ecrire une statistique. L'autre moitie ne se lit qu'en base :
 * la materia nommee doit **exister au catalogue standard**, et un geste
 * retrouve ne doit donc rien inventer.
 */
class FoundGesturePoolTest extends AbstractIntegrationTestCase
{
    /**
     * **Un geste retrouve produit une materia du catalogue standard.**.
     *
     * C'est la formulation exacte de la regle laterale, et elle est verifiable
     * ici plutot que dans deux jalons parce que l'entree nomme la **materia** et
     * non une recette d'eveil : le plan demandait un slug de recette, mais
     * aucune n'existe — l'Autel est une promesse sans route jusqu'a REP-04, et
     * un bassin de references pendantes n'aurait rien pu verifier du tout.
     */
    public function testEveryGestureAwakensAMateriaOfTheStandardCatalogue(): void
    {
        $missing = [];

        foreach ($this->catalog()->foundGestures() as $key => $gesture) {
            $materia = $this->em->getRepository(Item::class)->findOneBy(['slug' => $gesture['awakens']]);

            if ($materia === null) {
                $missing[] = sprintf('%s → %s', $key, $gesture['awakens']);
                continue;
            }

            self::assertSame(
                Item::TYPE_MATERIA,
                $materia->getType(),
                sprintf('« %s » eveille « %s », qui n\'est pas une materia.', $key, $gesture['awakens']),
            );
        }

        self::assertSame([], $missing, sprintf(
            "Ces gestes eveillent une materia qui n'existe pas : %s.\n"
            . 'Un geste retrouve donne une option, jamais un objet inedit.',
            implode(', ', $missing),
        ));
    }

    /**
     * L'element declare est **celui de la materia**.
     *
     * Sans quoi la dominante mentirait : un serveur qui a lu du feu toute
     * l'annee retrouverait un geste tague feu qui eveille une materia d'eau, et
     * *ce qu'il a vecu ne serait plus ce dont il se souvient*.
     */
    public function testTheDeclaredElementIsTheMateriasOwn(): void
    {
        $mismatched = [];

        foreach ($this->catalog()->foundGestures() as $key => $gesture) {
            $materia = $this->em->getRepository(Item::class)->findOneBy(['slug' => $gesture['awakens']]);
            self::assertNotNull($materia);

            if (!\in_array($materia->getElement()->value, $gesture['elements'], true)) {
                $mismatched[] = sprintf('%s : tague %s, materia %s', $key, implode('/', $gesture['elements']), $materia->getElement()->value);
            }
        }

        self::assertSame([], $mismatched, "Un geste ne porte pas l'element de ce qu'il eveille :\n" . implode("\n", $mismatched));
    }

    /**
     * Les zones taguees existent.
     *
     * Une provenance ou un lieu mal orthographie ne casserait rien : il rendrait
     * simplement son geste **intirable par cet axe**, en silence. C'est le meme
     * defaut qu'une condition inconnue, et il merite le meme refus.
     */
    public function testEveryTaggedZoneExists(): void
    {
        $unknown = [];

        foreach ($this->catalog()->foundGestures() as $key => $gesture) {
            foreach (['provenances', 'places'] as $axis) {
                foreach ($gesture[$axis] as $slug) {
                    if ($this->em->getRepository(Zone::class)->findOneBy(['slug' => $slug]) === null) {
                        $unknown[] = sprintf('%s.%s : %s', $key, $axis, $slug);
                    }
                }
            }
        }

        self::assertSame([], $unknown, "Ces tags designent une zone inexistante :\n" . implode("\n", $unknown));
    }

    /**
     * **Ce que le geste ajoute est un chemin, pas un objet.**.
     *
     * Toute materia du catalogue a deja un canal (MAT-08 : aucune n'est
     * orpheline). Retrouver un geste n'ouvre donc pas l'acces a quelque chose
     * d'inaccessible — il ouvre l'**eveil** de quelque chose qu'on attendait
     * jusque-la d'un coffre ou d'un donjon. C'est ce qui fait que le serveur qui
     * n'a pas retrouve le geste n'est pas *derriere*, seulement moins souple.
     *
     * Le test le verifie par la negative, la seule qui soit tenable ici : aucune
     * materia du bassin n'est introuvable ailleurs.
     */
    public function testNothingInThePoolIsTheOnlyWayToGetItsMateria(): void
    {
        $exclusive = [];

        foreach ($this->catalog()->foundGestures() as $key => $gesture) {
            $materia = $this->em->getRepository(Item::class)->findOneBy(['slug' => $gesture['awakens']]);
            self::assertNotNull($materia);

            // Une materia sans prix ni recette ni table de butin serait
            // exclusive au bassin. Le prix suffit a le dire : le catalogue
            // derive un prix pour chaque materia (MAT-03), et une materia sans
            // canal aurait ete attrapee par MateriaObtainabilityTest bien avant.
            if ($materia->getPrice() <= 0) {
                $exclusive[] = sprintf('%s → %s', $key, $gesture['awakens']);
            }
        }

        self::assertSame([], $exclusive, sprintf(
            "Ces gestes sont la seule source de leur materia : %s.\n"
            . 'Le serveur qui ne les retrouve pas serait alors mecaniquement derriere.',
            implode(', ', $exclusive),
        ));
    }

    private function catalog(): RepertoireCatalog
    {
        return new RepertoireCatalog(\dirname(__DIR__, 3));
    }
}
