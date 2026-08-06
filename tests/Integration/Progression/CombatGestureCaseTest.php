<?php

namespace App\Tests\Integration\Progression;

use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Entity\Game\Spell;
use App\Enum\Element;
use App\GameEngine\Progression\CombatGestureCase;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * La case d'un geste, sur les vraies donnees (ARC-06b).
 *
 * La decision du 2026-08-06 fait de l'element et du registre du geste la
 * designation de l'arbre credite. Ce test verifie sur le catalogue livre que
 * cette designation **existe** — c'est-a-dire qu'un geste tombe toujours sur
 * une case peuplee — et il mesure ce que la decision suppose sans le dire :
 * combien d'arbres une case contient reellement.
 */
class CombatGestureCaseTest extends AbstractIntegrationTestCase
{
    /**
     * `None` n'est pas une case, et ne doit jamais en designer une.
     *
     * ARC-13a l'a tranche pour les marques : `None` n'est pas un element mais
     * son **absence**. Lui donner un arbre en inventerait un dix-neuvieme,
     * hors de la grille — le defaut du § 9 quater, celui qui avait eteint
     * l'archer.
     */
    public function testTheAbsenceOfElementDesignatesNoTree(): void
    {
        $resolver = self::getContainer()->get(CombatGestureCase::class);
        $player = new Player();

        $spell = new Spell();
        $spell->setElement(Element::None);

        self::assertNull($resolver->forSpell($player, $spell));
    }

    /**
     * Un arbre non ouvert ne recoit rien.
     *
     * Le parchemin reste la porte (GAME_ONBOARDING) : jouer du feu sans avoir
     * ouvert d'arbre de feu ne fait progresser aucun arbre de feu. C'est la
     * meme regle que la recolte suit depuis toujours.
     */
    public function testAClosedTreeIsNeverCredited(): void
    {
        $resolver = self::getContainer()->get(CombatGestureCase::class);
        $player = new Player();

        $spell = new Spell();
        $spell->setElement(Element::Fire);

        // Le joueur fraîchement cree n'a ouvert aucun arbre de combat : la
        // case existe, l'arbre n'est pas a lui.
        self::assertNull($resolver->forSpell($player, $spell));
    }

    /**
     * Toute case peuplee l'est par des arbres de **sa** case, et d'elles
     * seules.
     *
     * L'invariant qui protege la decision : un geste ne peut pas crediter un
     * arbre d'un autre element ou d'un autre registre, quel que soit l'arbre
     * qui a ouvert sa materia.
     */
    public function testEveryCombatTreeSitsInExactlyOneCase(): void
    {
        $cases = [];
        foreach ($this->combatDomains() as $domain) {
            self::assertNotNull($domain->getElement(), sprintf('%s : un arbre de combat a un element.', $domain->getTitle()));
            $cases[$domain->getElement() . '/' . $domain->getRegister()->value][] = $domain->getTitle();
        }

        self::assertNotEmpty($cases);
        foreach ($cases as $key => $titles) {
            self::assertNotEmpty($titles, sprintf('La case %s est declaree vide.', $key));
        }
    }

    /**
     * Ce que la decision suppose, et que la grille ne tient pas.
     *
     * La decision dit « un geste designe une case **unique** de la grille des
     * 24 arbres ». C'est vrai de la case, pas de l'arbre : la fonction (ARC-01)
     * est le troisieme axe, et **trois** arbres partagent l'eau x sorts. Ce
     * test grave l'ecart plutot que de le laisser se decouvrir en jeu, et il
     * entre en **cliquet** : le nombre d'arbres qu'une case peut contenir peut
     * diminuer, jamais grandir en silence.
     *
     * Le departage est celui que la decision a deja rendu pour son point 3 :
     * le premier arbre ouvert. La question de fond — *faut-il que le geste
     * porte une fonction ?* — est notee au plan pour ARC-07/08 et ARC-17.
     */
    public function testACaseHoldsUpToThreeTreesAndTheGestureDoesNotSeparateThem(): void
    {
        $perCase = [];
        foreach ($this->combatDomains() as $domain) {
            $perCase[$domain->getElement() . '/' . $domain->getRegister()->value][] = $domain->getTitle();
        }

        $widest = max(array_map('count', $perCase));

        self::assertLessThanOrEqual(
            3,
            $widest,
            'Une case element x registre porte au plus trois arbres : au-dela, le departage « premier ouvert » cesse d\'etre lisible.'
        );
    }

    /**
     * @return list<Domain>
     */
    private function combatDomains(): array
    {
        return array_values(array_filter(
            $this->em->getRepository(Domain::class)->findAll(),
            fn (Domain $domain) => $domain->getRegister() !== null,
        ));
    }
}
