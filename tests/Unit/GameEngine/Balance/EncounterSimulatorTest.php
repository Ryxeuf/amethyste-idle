<?php

namespace App\Tests\Unit\GameEngine\Balance;

use App\Enum\CombatRegister;
use App\Enum\DomainRole;
use App\Enum\MonsterRank;
use App\GameEngine\Balance\EncounterSimulator;
use App\GameEngine\Balance\ReferenceCharacter;
use App\GameEngine\Bestiary\MonsterStatTemplate;
use PHPUnit\Framework\TestCase;

/**
 * Le simulateur de rencontre, sur des fiches posees a la main (ARC-17c-b).
 *
 * **Ici les chiffres sont ecrits, et c'est le seul endroit ou ils ont le droit
 * de l'etre.** Le simulateur joue les vraies donnees ; ce test verifie qu'il les
 * joue *bien*, et pour cela il lui faut des entrees dont on connait la reponse a
 * l'avance. Un test qui lirait les fixtures ne saurait pas ce qu'il attend — il
 * ne pourrait que constater ce qui sort.
 */
class EncounterSimulatorTest extends TestCase
{
    /**
     * Un personnage qui frappe assez fort vient a bout de son adversaire, et le
     * nombre de tours est celui que l'arithmetique donne.
     *
     * C'est l'aller-retour minimal : sans lui, toute mesure produite ensuite
     * serait invalidable.
     */
    public function testTheDurationIsTheOneArithmeticGives(): void
    {
        // Un commun de palier 1 porte 30 PV. A 10 degats par tour, sans jets,
        // il tombe au troisieme.
        $character = $this->character(gestureDamage: 10, maxLife: 200);

        $outcome = (new EncounterSimulator())->simulate($character, 1, MonsterRank::Common);

        self::assertTrue($outcome->victory, 'Le personnage devait l\'emporter.');
        self::assertSame(3, $outcome->turns);
        self::assertTrue($outcome->isWithinBand(), 'Trois tours tiennent la bande 3-5 d\'un commun.');
    }

    /**
     * **La bande ne se lit que sur une victoire.**.
     *
     * Un personnage qui tombe en trois tours face a un commun n'a pas « tenu la
     * bande des 3-5 tours » : il est mort dedans. Compter cette duree
     * reviendrait a lire une regle de duree de combat sur un combat perdu — le
     * genre de chiffre qui rend une table verte sans rien mesurer.
     */
    public function testALostEncounterNeverHoldsItsBand(): void
    {
        // Il ne frappe pas, l'adversaire si : il tombe en quelques tours.
        $character = $this->character(gestureDamage: 0, maxLife: 12);

        $outcome = (new EncounterSimulator())->simulate($character, 1, MonsterRank::Common);

        self::assertFalse($outcome->victory);
        self::assertTrue($outcome->resolved, 'Quelqu\'un est tombe : la rencontre est resolue.');
        self::assertFalse($outcome->isWithinBand());
    }

    /**
     * Une rencontre que personne ne peut conclure est **nommee**, pas comptee.
     *
     * Deux adversaires qui ne peuvent pas se tuer ne produisent pas une defaite
     * : ils produisent une mesure absente. Les confondre ferait croire a un
     * equilibrage la ou il n'y a qu'un plafond d'instrument.
     */
    public function testAnEndlessEncounterIsNamedRatherThanCounted(): void
    {
        // Il ne frappe pas ; il ne peut pas non plus etre touche.
        $character = $this->character(gestureDamage: 0, maxLife: 100, dodgeRate: 100.0);

        $outcome = (new EncounterSimulator())->simulate($character, 1, MonsterRank::Common);

        self::assertFalse($outcome->resolved);
        self::assertFalse($outcome->victory);
        self::assertSame(EncounterSimulator::MAX_TURNS, $outcome->turns);
        self::assertFalse($outcome->isWithinBand());
    }

    /**
     * `guard` rallonge la survie, et il le fait dans l'unite du levier.
     *
     * Le levier est un multiplicateur sur les degats subis : a garde egale a la
     * moitie, un personnage encaisse deux fois plus de coups avant de tomber.
     */
    public function testGuardLengthensSurvival(): void
    {
        $simulator = new EncounterSimulator();

        $bare = $simulator->simulate($this->character(gestureDamage: 0, maxLife: 100), 1, MonsterRank::Common);
        $guarded = $simulator->simulate($this->character(gestureDamage: 0, maxLife: 100, guardMultiplier: 0.5), 1, MonsterRank::Common);

        self::assertGreaterThan(
            $bare->turns,
            $guarded->turns,
            'Une garde qui ne rallonge rien n\'occupe pas sa place dans la formule.',
        );
    }

    /**
     * **Un lanceur a sec continue de jouer.**.
     *
     * L'attaque de base est gratuite (regle absolue n° 10) : quand la ressource
     * ne suffit plus, le personnage frappe moins fort, il ne s'arrete pas. Le
     * simulateur doit donc depenser exactement le pool, puis continuer.
     */
    public function testAnEmptyResourceFallsBackOnTheFreeAttack(): void
    {
        // Deux gestes payants a 40 PM sur un pool de 80, puis les mains nues.
        $character = $this->character(
            gestureDamage: 10,
            maxLife: 500,
            maxResource: 80,
            gestureCost: 40,
            fallbackDamage: 1,
        );

        $outcome = (new EncounterSimulator())->simulate($character, 1, MonsterRank::Common);

        self::assertSame(80, $outcome->resourceSpent, 'Le pool se depense entierement, et pas au-dela.');
        self::assertTrue($outcome->victory, 'Les mains nues ont fini le travail : lentement, mais elles l\'ont fini.');
        // 30 PV de commun : 10 + 10 au prix du pool, puis 10 coups a 1.
        self::assertSame(12, $outcome->turns);
    }

    /**
     * Le cout en points de vie se lit en **part de barre**, jamais en points.
     *
     * C'est l'unite du § 9 octies — *une elite tue un joueur seul (102-129 % de
     * sa barre)* —, et la seule dans laquelle deux builds aux barres differentes
     * se comparent.
     */
    public function testTheLifeCostIsReadAsAShareOfTheBar(): void
    {
        $strike = MonsterStatTemplate::attackFor(1, MonsterRank::Common);
        $hit = MonsterStatTemplate::hitFor(1, MonsterRank::Common) / 100.0;

        // Une barre de 100, un seul tour encaisse avant que l'adversaire tombe
        // au second.
        $character = $this->character(gestureDamage: 20, maxLife: 100);

        $outcome = (new EncounterSimulator())->simulate($character, 1, MonsterRank::Common);

        self::assertSame(2, $outcome->turns);
        self::assertEqualsWithDelta($strike * $hit, $outcome->lifeCostShare(), 1.0);
    }

    private function character(
        int $gestureDamage,
        int $maxLife,
        int $maxResource = 0,
        int $gestureCost = 0,
        int $fallbackDamage = 0,
        float $guardMultiplier = 1.0,
        float $armorMultiplier = 1.0,
        float $dodgeRate = 0.0,
    ): ReferenceCharacter {
        return new ReferenceCharacter(
            label: 'Banc d\'essai',
            role: DomainRole::Assault,
            register: CombatRegister::Spell,
            maxLife: $maxLife,
            maxResource: $maxResource,
            gestureSlug: 'banc-essai',
            gestureDamage: $gestureDamage,
            gestureCost: $gestureCost,
            fallbackDamage: $fallbackDamage,
            // Precision parfaite et aucun critique : le jalon mesure la boucle
            // de tours, pas la conversion des jets — elle a son propre test.
            hitRate: 100.0,
            criticalRate: 0.0,
            criticalPower: 1.5,
            guardMultiplier: $guardMultiplier,
            // ARC-19 : ces tests mesurent l'arbre, pas l'armure.
            armorMultiplier: $armorMultiplier,
            dodgeRate: $dodgeRate,
            recoveryPerTurn: 0.0,
            resourcePerTurn: 0.0,
        );
    }
}
