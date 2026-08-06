<?php

namespace App\Tests\Integration\Fight;

use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\Enum\Element;
use App\GameEngine\Fight\ElementalMark;
use App\GameEngine\Fight\MonsterMarkLaw;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * La marque se porte des deux cotes (ARC-13b-b).
 *
 * GAME_ARCHETYPES § 1.1, correction du § 9 ter. ARC-13b-a a branche le cote
 * joueur ; celui-ci verifie que le cote monstre suit, et surtout **qu'il
 * atteint quelqu'un** : une marque que personne ne recoit laisse `ward` sans
 * objet, exactement comme avant.
 */
class MonsterMarkReachabilityTest extends AbstractIntegrationTestCase
{
    /**
     * Les huit marques existent en base, une par element.
     *
     * ARC-13a les a ecrites ; ce test empeche qu'une disparaisse le jour ou
     * `MonsterMarkLaw` la designerait — le monstre poserait alors un statut
     * introuvable, et le moteur passerait son chemin sans rien dire.
     */
    public function testEveryMarkExistsInTheDatabase(): void
    {
        foreach (ElementalMark::markedElements() as $element) {
            $slug = ElementalMark::forElement($element);
            self::assertNotNull($slug);

            $effect = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => $slug]);
            self::assertNotNull($effect, sprintf('La marque de %s (%s) n\'existe pas en base.', $element->value, (string) $slug));
        }
    }

    /**
     * Un monstre d'un element marque finit par marquer quelqu'un.
     *
     * **La mesure est le point du jalon**, pas la conformite d'une espece en
     * particulier : tant qu'aucun monstre livre n'a d'element marque **et** un
     * geste qui blesse, le cote monstre reste theorique et `ward` n'a toujours
     * rien a quoi resister.
     */
    public function testSomeShippedMonstersActuallyLeaveAMark(): void
    {
        self::assertGreaterThan(
            0,
            \count($this->markingMonsters()),
            'Aucun monstre livre ne laisse de marque : `ward` reste un levier mort.'
        );
    }

    /**
     * Aucun monstre ne pose la Brulure — c'est un DOT, pas une marque pure.
     *
     * **L'ecart que ce jalon a trouve, et qu'il refuse plutot que de le
     * trancher.** ARC-13a a decide que la mark-ness vit dans un catalogue et
     * non dans le type : la Brulure est **les deux**, un DOT et la marque du
     * feu. La poser depuis chaque monstre de feu ne leur donnerait donc pas une
     * marque, cela leur donnerait des degats sur la duree qu'ils n'avaient pas,
     * plus les 25 % retires a leur cible par `applyBurnReduction()`. C'est une
     * decision d'equilibrage, et le § 0.2 interdit de la prendre a la main.
     *
     * Consequence mesuree : **les monstres de feu ne marquent pas**, et c'est le
     * meme ecart qu'ARC-13b-a a laisse ouvert cote joueur — vu depuis l'autre
     * bord. Il se refermera avec lui.
     */
    public function testTheFireMarkIsNeverPosedByAMonster(): void
    {
        $burn = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => 'burn']);
        self::assertNotNull($burn);
        self::assertFalse(MonsterMarkLaw::poses($burn), 'La Brulure est un DOT : la poser serait un choix d\'equilibrage.');

        foreach (ElementalMark::markedElements() as $element) {
            $slug = ElementalMark::forElement($element);
            $effect = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => $slug]);
            self::assertNotNull($effect);

            self::assertSame(
                $element !== Element::Fire,
                MonsterMarkLaw::poses($effect),
                sprintf('%s (%s)', $element->value, (string) $slug)
            );
        }
    }

    /**
     * Les monstres qui laissent une marque, par nom.
     *
     * @return array<string, true>
     */
    private function markingMonsters(): array
    {
        $marking = [];

        foreach ($this->em->getRepository(Monster::class)->findAll() as $monster) {
            foreach ($this->gesturesOf($monster) as $gesture) {
                $mark = MonsterMarkLaw::markFor($monster, $gesture);
                if ($mark === null) {
                    continue;
                }

                $effect = $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => $mark]);
                if ($effect !== null && MonsterMarkLaw::poses($effect)) {
                    $marking[$monster->getName()] = true;

                    break;
                }
            }
        }

        return $marking;
    }

    /**
     * Aucun mannequin ne marque, quelle que soit la porte (ONB-11).
     */
    public function testNoTrainingDummyEverMarks(): void
    {
        foreach ($this->em->getRepository(Monster::class)->findAll() as $monster) {
            if (!$monster->isTrainingDummy()) {
                continue;
            }

            foreach ($this->gesturesOf($monster) as $gesture) {
                self::assertNull(
                    MonsterMarkLaw::markFor($monster, $gesture),
                    sprintf('%s est un mannequin et marque avec %s.', $monster->getName(), (string) $gesture->getSlug())
                );
            }
        }
    }

    /**
     * Un monstre ne pose jamais la marque d'un autre element que le sien.
     *
     * **Le cote monstre est immunise, par construction, au defaut du cote
     * joueur** — ARC-13b-a a trouve trois gestes qui appliquent la Brulure sans
     * etre du feu, et qui allument donc le capstone d'un Pyromancien. Ici la
     * marque *est* l'element du monstre : elle ne peut pas en designer un
     * autre. Ce test le dit plutot que de le supposer, parce que c'est la seule
     * garantie qui rende la lecture « dans la peau » preferable a la lecture
     * « dans le geste ».
     */
    public function testAMonsterNeverLeavesSomeoneElsesMark(): void
    {
        foreach ($this->em->getRepository(Monster::class)->findAll() as $monster) {
            foreach ($this->gesturesOf($monster) as $gesture) {
                $mark = MonsterMarkLaw::markFor($monster, $gesture);
                if ($mark === null) {
                    continue;
                }

                self::assertSame(
                    $monster->getElement(),
                    ElementalMark::elementOf($mark) ?? Element::None,
                    sprintf('%s (%s) laisse %s.', $monster->getName(), $monster->getElement()->value, $mark)
                );
            }
        }
    }

    /**
     * Les gestes qu'un monstre peut jouer : son attaque et ses sorts.
     *
     * @return list<Spell>
     */
    private function gesturesOf(Monster $monster): array
    {
        $gestures = [$monster->getAttack()];

        foreach ($monster->getSpells() as $spell) {
            if ($spell instanceof Spell) {
                $gestures[] = $spell;
            }
        }

        return $gestures;
    }
}
