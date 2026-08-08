<?php

namespace App\Tests\Unit\GameEngine\Bestiary;

use App\Enum\MonsterRank;
use App\GameEngine\Bestiary\MonsterStatTemplate;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'un monstre frappe se derive de sa case (ARC-17a).
 *
 * GAME_ARCHETYPES § 9 octies, GAME_BESTIARY § 3. Le gabarit derivait la vie, la
 * precision et la vitesse — et pas ce que le monstre **fait**. Les 65 especes
 * livrees se partagent **17 gestes d'attaque** dont les degats vont de 1 a
 * quelques points, si bien qu'un boss de palier 4 et un commun de palier 1
 * peuvent porter le meme geste et frapper pareil.
 *
 * La vie va de 30 a 2 400 sur la grille — **un facteur 80** — quand les degats
 * recus, eux, ne bougent pas.
 *
 * > **Ce n'est pas un detail d'equilibrage, c'est ce qui empeche de mesurer.**
 * > Quatre des cinq seuils qu'ARC-17 doit tenir en CI portent sur les degats
 * > subis, a commencer par *une elite tue un joueur seul*.
 */
class MonsterAttackDerivationTest extends TestCase
{
    /**
     * La derivation tombe sur les deux nombres que le canon a mesures.
     *
     * C'est le test qui autorise a se fier au reste : le § 9 octies a calcule a
     * la main qu'une elite frappe **26** quand un commun de son palier frappe
     * **9**, et la grille les rend sans qu'on les ait ecrits — ils sortent du
     * huitieme de la vie et du rapport de rang. *Une derivation qui rate sa
     * propre reference ne derive rien.*
     */
    public function testTheDerivationLandsOnTheCanonMeasuredNumbers(): void
    {
        self::assertSame(9, MonsterStatTemplate::attackFor(2, MonsterRank::Common));
        self::assertSame(26, MonsterStatTemplate::attackFor(2, MonsterRank::Elite));
    }

    /**
     * **Une elite n'est pas un commun gonfle** — le rapport, pas le chiffre.
     *
     * GAME_ARCHETYPES § 0.2 range les valeurs parmi les nombres qu'ARC-17
     * recalculera ; ce qui survit a la recalibration, c'est que l'elite frappe
     * pres de trois fois un commun de son palier pour moins de deux fois ses
     * PV. C'est cette asymetrie qui fait qu'elle tue un joueur seul, et une
     * grille qui la perdrait rendrait l'elite inoffensive sans rien dire.
     */
    public function testAnEliteHitsFarHarderThanItsLifeWouldSuggest(): void
    {
        foreach ([1, 2, 3, 4] as $tier) {
            $commonAttack = MonsterStatTemplate::attackFor($tier, MonsterRank::Common);
            $eliteAttack = MonsterStatTemplate::attackFor($tier, MonsterRank::Elite);

            self::assertGreaterThanOrEqual(
                2.5,
                $eliteAttack / $commonAttack,
                sprintf('T%d : une elite qui ne frappe pas pres de trois fois un commun est un commun gonfle.', $tier),
            );
        }
    }

    /**
     * Ce que le monstre frappe suit ce qu'il encaisse, palier apres palier.
     *
     * L'invariant qui manquait : la vie progresse de x80 sur la grille, donc
     * l'attaque doit progresser avec elle. Sans lui, monter de palier rend les
     * combats **plus longs et pas plus dangereux** — ce qui est exactement le
     * defaut que la faille du milieu avait produit sur la vie (BES-01).
     */
    public function testAttackClimbsWithLifeAcrossTheTiers(): void
    {
        foreach (MonsterRank::cases() as $rank) {
            $previous = 0;

            foreach ([1, 2, 3, 4] as $tier) {
                $attack = MonsterStatTemplate::attackFor($tier, $rank);
                self::assertGreaterThan(
                    $previous,
                    $attack,
                    sprintf('%s : le palier %d ne frappe pas plus fort que le precedent.', $rank->value, $tier),
                );
                $previous = $attack;
            }
        }
    }

    /**
     * La formule de combat lit desormais la derivation — **par ses deux
     * chemins** (ARC-17b).
     *
     * Ce test etait l'inverse de lui-meme : ARC-17a, comme `EncounterAnchor` et
     * `DailyAnchor` avant lui, livrait un **instrument de mesure** et verifiait
     * qu'il ne deplacait rien. Il est **retourne et pas supprime**, parce que
     * c'est lui qui documente la transition : la derivation a existe un jalon
     * durant sans que personne ne la lise, et c'etait voulu.
     *
     * **Il visait pourtant le mauvais fichier.** ARC-17a supposait que le point
     * de branchement serait `MobActionHandler` ; il y en a deux, et aucun n'est
     * celui-la. Les degats d'un monstre passent par `SpellApplicator` en combat
     * de zone — donc aussi en invocation, en phase de boss et sur toute action
     * qui resout un geste — et par `GroupDungeonCombatService` en donjon de
     * groupe, qui resout sa riposte tout seul. *Brancher la ou l'action est
     * choisie plutot que la ou le degat est calcule aurait laisse la moitie du
     * jeu en dehors de la loi.*
     *
     * Ce que ce test ne prouve pas, et c'est pour cela qu'il n'est pas seul :
     * une classe **nommee** n'est pas une classe **lue**. Le comportement est
     * tenu par `MonsterDamageLawTest`, qui mesure ce que deux cases font du meme
     * geste ; celui-ci garde la trace des deux chemins, pour qu'un troisieme ne
     * naisse pas en silence.
     */
    public function testTheFightFormulaNowReadsItOnBothPaths(): void
    {
        $root = \dirname(__DIR__, 4);

        foreach ([
            '/src/GameEngine/Fight/SpellApplicator.php' => 'le combat de zone',
            '/src/GameEngine/Dungeon/GroupDungeonCombatService.php' => 'la riposte du donjon',
        ] as $path => $what) {
            $source = file_get_contents($root . $path);
            self::assertIsString($source, $path);

            self::assertStringContainsString(
                'MonsterDamageLaw',
                $source,
                sprintf('%s ne passe pas par la loi : ce qu\'un monstre frappe y resterait celui de son geste.', $what),
            );
        }
    }

    /**
     * La precision n'est plus lue comme des degats.
     *
     * **Le defaut qu'ARC-17b a trouve**, et il ne pouvait apparaitre qu'en
     * cherchant tous les chemins : la riposte du donjon retirait
     * `Monster::hit` points de vie, c'est-a-dire la valeur que le combat de zone
     * passe a `FightCalculator::hasAttackHit()` comme une **probabilite**. Elle
     * va de 75 a 95 sur toute la grille — un facteur 1,27 la ou le canon en
     * demande 2,9 entre deux rangs voisins.
     *
     * Le test est ecrit sur la source parce que le defaut etait **une lecture**,
     * pas une valeur : c'est la relecture de `getHit()` a cet endroit qui doit
     * ne jamais revenir.
     */
    public function testTheDungeonNoLongerSpendsPrecisionAsDamage(): void
    {
        $source = file_get_contents(\dirname(__DIR__, 4) . '/src/GameEngine/Dungeon/GroupDungeonCombatService.php');
        self::assertIsString($source);

        self::assertStringNotContainsString(
            '$monster->getHit()',
            $source,
            'La riposte du donjon relit la precision du monstre comme des degats.',
        );
    }
}
