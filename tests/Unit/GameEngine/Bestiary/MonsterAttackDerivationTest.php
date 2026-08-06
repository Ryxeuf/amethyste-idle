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
     * Aucune valeur de jeu ne bouge encore.
     *
     * Comme `EncounterAnchor` (ARC-05a) et `DailyAnchor` (ARC-05b) avant elle,
     * la derivation est d'abord **un instrument de mesure** : elle dit ce que la
     * regle exige sans toucher a la formule de combat, qui continue de lire les
     * degats du geste porte par l'espece. Brancher la derivation deplacera de
     * vraies valeurs, et c'est ARC-17b.
     *
     * Le test le verifie a l'endroit ou ca compte : le gestionnaire d'action des
     * monstres ne connait pas encore le gabarit.
     */
    public function testTheFightFormulaDoesNotReadItYet(): void
    {
        $source = file_get_contents(\dirname(__DIR__, 4) . '/src/GameEngine/Fight/MobActionHandler.php');
        self::assertIsString($source);

        self::assertStringNotContainsString(
            'MonsterStatTemplate',
            $source,
            'La derivation est branchee dans le combat : ce jalon annonce pourtant ne deplacer aucune valeur de jeu.',
        );
    }
}
