<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Enum\CombatLever;
use App\Enum\CombatRegister;
use App\GameEngine\Fight\Calculator\DamageCalculator;
use App\GameEngine\Fight\CombatLeverEffects;
use App\GameEngine\Progression\CombatLeverDefinitionException;
use App\GameEngine\Progression\CombatLeverDefinitionLoader;
use App\GameEngine\Progression\CombatLeverScale;
use PHPUnit\Framework\TestCase;

/**
 * Les leviers entrent dans la formule, chacun a sa place (ARC-03b).
 *
 * ARC-03a a livre le vocabulaire et le convertisseur, et son contrat verrouille
 * que **deux leviers n'occupent jamais la meme place**. Ce fichier tient l'autre
 * bout : que chaque levier soit lu **dans son unite** et applique la ou le canon
 * le range.
 *
 * L'invariant qui compte pour ce jalon precis : **un porteur vide ne change
 * rien**. Aucun nœud livre ne porte de levier (la conversion du contenu est
 * ARC-07 et ARC-08), donc le moteur doit calculer exactement comme avant — un
 * refactor qui deplacerait une valeur de jeu au passage serait invisible
 * autrement.
 */
class CombatLeverEffectsTest extends TestCase
{
    private function scale(): CombatLeverScale
    {
        return new CombatLeverScale(new CombatLeverDefinitionLoader(\dirname(__DIR__, 4)));
    }

    /**
     * @param array<string, int> $points
     */
    private function effects(array $points, ?CombatRegister $register = null): CombatLeverEffects
    {
        return CombatLeverEffects::of($points, $this->scale(), $register);
    }

    /**
     * Le cas de tout le contenu livre : rien n'est achete, rien ne change.
     */
    public function testAnEmptyCarrierChangesNothing(): void
    {
        $levers = CombatLeverEffects::none();
        $scale = $this->scale();
        $calculator = new DamageCalculator();

        self::assertTrue($levers->isEmpty());
        self::assertSame(1.0, $levers->multiplierFor(CombatLever::Power, $scale));
        self::assertSame(0.0, $levers->pointsFor(CombatLever::Hit, $scale));
        self::assertSame(17, $calculator->applyPower(17, $levers, $scale));
        self::assertSame(17, $calculator->applyMending(17, $levers, $scale));
        self::assertSame(17, $calculator->applyGuard(17, $levers, $scale));
        self::assertFalse($calculator->isDodged($levers, $scale));
    }

    /**
     * Un levier se lit dans son unite, et le refuser est le point.
     *
     * Lire un taux de critique comme un multiplicateur donnerait un chiffre
     * plausible et faux — la sorte d'erreur qu'aucun ecran ne montre. Le
     * porteur la refuse au lieu de la calculer.
     */
    public function testALeverCannotBeReadInTheWrongUnit(): void
    {
        $levers = $this->effects(['critical' => 4]);

        $this->expectException(CombatLeverDefinitionException::class);
        $levers->multiplierFor(CombatLever::Critical, $this->scale());
    }

    public function testAPercentLeverCannotBeReadAsPoints(): void
    {
        $levers = $this->effects(['power' => 4]);

        $this->expectException(CombatLeverDefinitionException::class);
        $levers->pointsFor(CombatLever::Power, $this->scale());
    }

    /**
     * Un levier absent du sac vaut le neutre de son unite, pas une erreur.
     *
     * Un arbre n'achete que quelques leviers sur quinze : demander les autres
     * est le cas normal, pas une anomalie.
     */
    public function testALeverNotBoughtIsNeutralRatherThanMissing(): void
    {
        $levers = $this->effects(['power' => 10]);
        $scale = $this->scale();

        self::assertSame(1.0, $levers->multiplierFor(CombatLever::Mending, $scale));
        self::assertSame(0.0, $levers->pointsFor(CombatLever::Dodge, $scale));
    }

    /**
     * `power` multiplie, il n'ajoute pas — c'est tout l'objet d'ARC-03.
     *
     * Le meme investissement vaut la meme chose sur un geste faible et sur un
     * geste fort, la ou `damage: +1` valait +50 % sur l'un et +8 % sur l'autre.
     */
    public function testPowerIsWorthTheSameShareOnASmallAndOnALargeGesture(): void
    {
        $scale = $this->scale();
        $calculator = new DamageCalculator();
        $levers = $this->effects(['power' => 10]);

        $small = $calculator->applyPower(20, $levers, $scale);
        $large = $calculator->applyPower(200, $levers, $scale);

        self::assertEqualsWithDelta($small / 20, $large / 200, 1e-3);
        self::assertGreaterThan(20, $small);
    }

    /**
     * `guard` reduit les degats subis, et il est lu sur la **cible**.
     */
    public function testGuardReducesTheDamageTaken(): void
    {
        $calculator = new DamageCalculator();

        $reduced = $calculator->applyGuard(100, $this->effects(['guard' => 10]), $this->scale());

        self::assertLessThan(100, $reduced);
        self::assertGreaterThan(0, $reduced);
    }

    /**
     * `dodge` est binaire : investi a fond, il evite parfois ; jamais toujours.
     *
     * On ne verrouille pas un taux — § 0.2 previent qu'aucun chiffre du canon
     * n'est definitif. On verrouille que le levier **se joue** : a zero il
     * n'evite rien, et a son plafond il n'evite pas tout, sans quoi il cesserait
     * d'etre volatil et deviendrait un `guard` deguise.
     */
    public function testDodgeIsVolatileAndNeverCertain(): void
    {
        $scale = $this->scale();
        $calculator = new DamageCalculator();
        $levers = $this->effects(['dodge' => $scale->capOf(CombatLever::Dodge)]);

        $dodged = 0;
        for ($i = 0; $i < 400; ++$i) {
            if ($calculator->isDodged($levers, $scale)) {
                ++$dodged;
            }
        }

        self::assertLessThan(400, $dodged, 'A son plafond, l\'esquive evite tout : ce n\'est plus une esquive.');
        self::assertSame(0, $this->countDodges($calculator, CombatLeverEffects::none(), $scale));
    }

    private function countDodges(DamageCalculator $calculator, CombatLeverEffects $levers, CombatLeverScale $scale): int
    {
        $dodged = 0;
        for ($i = 0; $i < 200; ++$i) {
            if ($calculator->isDodged($levers, $scale)) {
                ++$dodged;
            }
        }

        return $dodged;
    }

    /**
     * Un levier de ressource sans registre est **omis**, jamais devine.
     *
     * Deviner voudrait dire choisir les PM par defaut, donc etre faux pour deux
     * registres sur trois, en silence. Avec un registre, il vaut ce que ce
     * registre dit.
     */
    public function testAResourceLeverIsDroppedWhenTheActionHasNoRegister(): void
    {
        $scale = $this->scale();

        self::assertTrue($this->effects(['thrift' => 8])->isEmpty());
        self::assertFalse($this->effects(['thrift' => 8], CombatRegister::Melee)->isEmpty());
        self::assertLessThan(
            1.0,
            $this->effects(['thrift' => 8], CombatRegister::Melee)->multiplierFor(CombatLever::Thrift, $scale, CombatRegister::Melee),
        );
    }

    /**
     * `wind` ne vaut pas la meme chose dans les trois registres.
     *
     * C'est la raison d'etre de sa lecture par registre : il rend de la
     * ressource, et les trois registres n'en ont pas la meme.
     */
    public function testWindIsWorthWhatItsRegisterSays(): void
    {
        $scale = $this->scale();

        $spell = $this->effects(['wind' => 6], CombatRegister::Spell)->rawFor(CombatLever::Wind);
        $melee = $this->effects(['wind' => 6], CombatRegister::Melee)->rawFor(CombatLever::Wind);
        $ranged = $this->effects(['wind' => 6], CombatRegister::Ranged)->rawFor(CombatLever::Wind);

        self::assertGreaterThan(0.0, $spell);
        self::assertGreaterThan(0.0, $melee);
        self::assertGreaterThan(0.0, $ranged);
        self::assertNotSame($spell, $melee);
        self::assertSame('mana', $scale->resourceOf(CombatLever::Wind, CombatRegister::Spell));
        self::assertSame('ammunition', $scale->resourceOf(CombatLever::Wind, CombatRegister::Ranged));
    }

    /**
     * Un investissement de zero point n'entre pas dans le sac.
     *
     * Sinon un porteur « vide » cesserait de l'etre des qu'un nœud declare un
     * levier a 0, et le chemin neutre — celui de tout le contenu livre —
     * passerait par les conversions au lieu de les court-circuiter.
     */
    public function testAZeroInvestmentDoesNotFillTheCarrier(): void
    {
        self::assertTrue($this->effects(['power' => 0])->isEmpty());
    }
}
