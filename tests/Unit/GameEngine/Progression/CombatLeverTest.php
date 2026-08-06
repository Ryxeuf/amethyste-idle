<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\Game\Skill;
use App\Enum\CombatLever;
use App\Enum\CombatRegister;
use App\GameEngine\Progression\CombatLeverDefinitionException;
use App\GameEngine\Progression\CombatLeverDefinitionLoader;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\GameEngine\Progression\SkillLeverReader;
use PHPUnit\Framework\TestCase;

/**
 * Le contrat du vocabulaire des leviers (ARC-03a).
 *
 * GAME_ARCHETYPES § 4. Ce que ce test verrouille tient en deux phrases, et ce
 * sont les deux moities de la meme regle :
 *
 * 1. **Un levier occupe une place, et une seule, dans la formule.** C'est le
 *    critere d'admission du canon, et c'est ce qui interdit les empilements
 *    silencieux qui font exploser un equilibrage six mois plus tard.
 * 2. **Le taux de change vit dans un seul convertisseur.** Sinon deux endroits
 *    du moteur finiront par ne plus donner le meme chiffre pour le meme nœud.
 *
 * Les **valeurs** (taux, plafonds) ne sont pas verrouillees ici : § 0.2 previent
 * qu'aucun nombre du canon n'est definitif, et ARC-17 les rejouera. Ce qui est
 * verrouille, ce sont les **rapports** que le canon dit devoir survivre.
 */
class CombatLeverTest extends TestCase
{
    private function loader(): CombatLeverDefinitionLoader
    {
        return new CombatLeverDefinitionLoader(\dirname(__DIR__, 4));
    }

    private function scale(): CombatLeverScale
    {
        return new CombatLeverScale($this->loader());
    }

    /**
     * Le vocabulaire est ferme, et la configuration le couvre exactement.
     *
     * Ni plus (une entree hors enum serait un levier que le moteur ne sait pas
     * appliquer), ni moins (un levier sans definition serait achetable sans que
     * personne sache ce qu'il achete).
     */
    public function testTheVocabularyIsClosedAndFullyDefined(): void
    {
        $definitions = $this->loader()->load();

        self::assertCount(15, CombatLever::cases(), 'Le canon en compte quinze : en ajouter un est une decision de moteur, pas de contenu.');
        self::assertSame(
            array_map(static fn (CombatLever $lever): string => $lever->value, CombatLever::cases()),
            array_keys($definitions),
        );
    }

    /**
     * Le critere d'admission : une place par levier, et pas deux leviers par place.
     */
    public function testEachLeverOccupiesASlotNoOtherOccupies(): void
    {
        $places = array_map(fn (CombatLever $lever): string => $this->scale()->placeOf($lever), CombatLever::cases());

        self::assertSame(
            \count($places),
            \count(array_unique($places)),
            'Deux leviers a la meme place dans la formule sont un seul levier sous deux noms.',
        );
    }

    /**
     * `dodge` et `guard` ne sont pas deux dosages de la meme chose.
     *
     * C'est l'exemple que le canon donne de son propre critere : l'un evite
     * entierement **avant** tout calcul, l'autre reduit **apres** resistance.
     * C'est ce qui distingue le cuir de la plaque autrement que par un chiffre —
     * si les deux tombaient au meme endroit, la distinction disparaitrait sans
     * qu'aucun test de contenu ne s'en apercoive.
     */
    public function testAvoidingAndReducingAreTwoDistinctPlaces(): void
    {
        $scale = $this->scale();

        self::assertNotSame($scale->placeOf(CombatLever::Dodge), $scale->placeOf(CombatLever::Guard));
    }

    /**
     * Les deux leviers de ressource se lisent par registre, et eux seuls.
     *
     * `thrift` et `wind` portent sur **la ressource du registre** (§ 2) : PM,
     * temps de reprise, munitions. Les treize autres touchent une valeur, pas
     * une ressource — leur donner une lecture par registre serait leur inventer
     * une borne que le canon ne leur donne pas.
     */
    public function testOnlyTheResourceLeversReadTheirRegister(): void
    {
        $scale = $this->scale();

        $perRegister = array_values(array_filter(
            CombatLever::cases(),
            static fn (CombatLever $lever): bool => $lever === CombatLever::Thrift || $lever === CombatLever::Wind,
        ));

        foreach (CombatLever::cases() as $lever) {
            self::assertSame(
                \in_array($lever, $perRegister, true),
                $scale->readsItsRegister($lever),
                sprintf('"%s" ne lit pas son registre comme le canon le dit.', $lever->value),
            );
        }
    }

    /**
     * Aucun registre n'est aveugle a un levier.
     *
     * C'est l'ecart n° 13, corrige le 2026-08-01 : la lecture melee de `wind`
     * manquait, et avec elle un levier sur cinq etait inaccessible aux huit
     * arbres de melee — dont le Gardien, arbre d'entretien qui a `wind` dans sa
     * palette. Un trou de ce genre ne se voit pas : l'arbre est simplement plus
     * pauvre que ses voisins, sans qu'aucune regle ne soit violee.
     */
    public function testEveryRegisterCanReadEveryLever(): void
    {
        $scale = $this->scale();

        foreach (CombatLever::cases() as $lever) {
            foreach (CombatRegister::cases() as $register) {
                self::assertNotSame(
                    0.0,
                    $scale->perPointOf($lever, $register),
                    sprintf('"%s" ne vaut rien en registre "%s".', $lever->value, $register->value),
                );
            }
        }
    }

    /**
     * `life` et `recovery` restent hors de la double borne (§ 4.2, DOM-01).
     *
     * Les points de vie ne sont pas un geste : les borner par element x registre
     * ferait varier la barre de vie d'un tour a l'autre selon le geste choisi.
     * Les treize autres restent bornes — c'est la decision de DOM-01, et ce
     * jalon ne la rouvre pas.
     */
    public function testOnlyLifeAndRecoveryEscapeTheDoubleBound(): void
    {
        $scale = $this->scale();

        $unbounded = array_values(array_filter(
            CombatLever::cases(),
            static fn (CombatLever $lever): bool => !$scale->isBounded($lever),
        ));

        self::assertSame([CombatLever::Life, CombatLever::Recovery], $unbounded);
    }

    /**
     * Le convertisseur est le seul a savoir ce qu'un point vaut.
     *
     * L'effet est lineaire dans le nombre de points : c'est ce qui rend le
     * budget lisible pour l'auteur — 6 points valent le double de 3, sur
     * n'importe quel levier.
     */
    public function testTheConverterScalesLinearlyWithBudgetPoints(): void
    {
        $scale = $this->scale();

        foreach (CombatLever::cases() as $lever) {
            $register = $scale->readsItsRegister($lever) ? CombatRegister::Spell : null;
            $unit = $scale->perPointOf($lever, $register);

            self::assertEqualsWithDelta(0.0, $scale->effectOf($lever, 0, $register), 1e-9);
            self::assertEqualsWithDelta(3 * $unit, $scale->effectOf($lever, 3, $register), 1e-9);
            self::assertEqualsWithDelta(6 * $unit, $scale->effectOf($lever, 6, $register), 1e-9);
        }
    }

    /**
     * Le plafond mord au convertisseur, pas seulement a la relecture.
     */
    public function testABudgetAboveTheCapIsRefused(): void
    {
        $scale = $this->scale();
        $cap = $scale->capOf(CombatLever::Power);

        $this->expectException(CombatLeverDefinitionException::class);
        $scale->effectOf(CombatLever::Power, $cap + 1);
    }

    /**
     * Un levier de ressource interroge sans registre echoue, il ne devine pas.
     *
     * Deviner voudrait dire choisir les PM par defaut — c'est-a-dire rendre
     * `thrift` et `wind` faux pour deux registres sur trois, en silence.
     */
    public function testAResourceLeverRefusesToGuessItsRegister(): void
    {
        $this->expectException(CombatLeverDefinitionException::class);
        $this->scale()->effectOf(CombatLever::Thrift, 5);
    }

    /**
     * Les leviers qui reduisent le font vraiment.
     *
     * `thrift` reduit un cout, `guard` des degats subis : leur taux est negatif.
     * Un signe inverse par megarde produirait un nœud qui aggrave ce qu'il
     * promet d'adoucir — la sorte d'erreur qu'aucun ecran ne montre.
     */
    public function testTheLeversThatReduceCarryANegativeRate(): void
    {
        $scale = $this->scale();

        self::assertLessThan(0.0, $scale->perPointOf(CombatLever::Guard));
        foreach (CombatRegister::cases() as $register) {
            self::assertLessThan(0.0, $scale->perPointOf(CombatLever::Thrift, $register));
        }
    }

    /**
     * `guard` plafonne sous les autres leviers principaux.
     *
     * Le rapport survit a la recalibration meme si les nombres changent : son
     * efficacite est **hyperbolique** sur la survie effective (−10 % de degats
     * subis = +11 % de PV effectifs ; −50 % = +100 %), donc il ne peut pas
     * plafonner comme `power`, `mending` ou `grip`.
     */
    public function testGuardIsCappedBelowTheOtherPrimaries(): void
    {
        $scale = $this->scale();

        foreach ([CombatLever::Power, CombatLever::Mending, CombatLever::Grip] as $primary) {
            self::assertLessThan(
                $scale->capOf($primary),
                $scale->capOf(CombatLever::Guard),
                'Le levier dont l\'efficacite est hyperbolique ne peut pas plafonner comme les autres.',
            );
        }
    }

    /**
     * Aucun plafond de levier ne depasse le budget d'un arbre.
     *
     * Un plafond au-dela de 50 points ne serait pas un plafond : le budget
     * mordrait avant lui, et le levier serait le seul du jeu a n'en avoir aucun.
     */
    public function testNoCapExceedsTheTreeBudget(): void
    {
        $scale = $this->scale();

        foreach (CombatLever::cases() as $lever) {
            self::assertLessThanOrEqual(50, $scale->capOf($lever), sprintf('Le plafond de "%s" depasse le budget d\'un arbre.', $lever->value));
        }
    }

    // --- Le chargeur refuse a la lecture -----------------------------------

    public function testTwoLeversSharingAFormulaSlotAreRefused(): void
    {
        $raw = $this->rawWith(['critical' => ['place' => 'damage.base_multiplier', 'unit' => 'point', 'per_point' => 0.5, 'cap' => 12, 'bounded' => true]]);

        $this->expectException(CombatLeverDefinitionException::class);
        (new CombatLeverDefinitionLoader('/project'))->normalize($raw);
    }

    public function testALeverOutsideTheVocabularyIsRefused(): void
    {
        $raw = $this->rawWith(['lifesteal' => ['place' => 'damage.leech', 'unit' => 'percent', 'per_point' => 1.0, 'cap' => 10, 'bounded' => true]]);

        $this->expectException(CombatLeverDefinitionException::class);
        (new CombatLeverDefinitionLoader('/project'))->normalize($raw);
    }

    public function testALeverWorthNothingPerPointIsRefused(): void
    {
        $raw = $this->rawWith(['tempo' => ['place' => 'speed.initiative', 'unit' => 'percent', 'per_point' => 0, 'cap' => 12, 'bounded' => true]]);

        $this->expectException(CombatLeverDefinitionException::class);
        (new CombatLeverDefinitionLoader('/project'))->normalize($raw);
    }

    /**
     * Un levier lisible dans deux registres sur trois est refuse.
     *
     * C'est l'ecart n° 13 pris a la racine : le trou etait une lecture absente,
     * pas une valeur fausse.
     */
    public function testALeverReadableInOnlyTwoRegistersIsRefused(): void
    {
        $raw = $this->rawWith(['wind' => [
            'place' => 'resource.regen',
            'cap' => 12,
            'bounded' => true,
            'by_register' => [
                'spell' => ['unit' => 'resource_per_turn', 'per_point' => 0.1, 'resource' => 'mana'],
                'ranged' => ['unit' => 'point', 'per_point' => 1.5, 'resource' => 'ammunition'],
            ],
        ]]);

        $this->expectException(CombatLeverDefinitionException::class);
        (new CombatLeverDefinitionLoader('/project'))->normalize($raw);
    }

    // --- Ce qu'un nœud accorde ---------------------------------------------

    /**
     * Un nœud livre avant ce jalon n'accorde aucun levier.
     *
     * La colonne est `null`, les colonnes plates restent la source, et rien ne
     * change pour personne : c'est ce qui permet de livrer le vocabulaire sans
     * toucher a une valeur de jeu (la conversion est ARC-07 et ARC-08).
     */
    public function testANodeWithoutLeversGrantsNothing(): void
    {
        $reader = new SkillLeverReader($this->scale(), new EquipmentPortCatalog(\dirname(__DIR__, 4)));

        $skill = new Skill();
        $skill->setSlug('soldier-apprenti-1');

        self::assertSame([], $reader->grantsOf($skill));
        self::assertSame(0, $reader->budgetOf($skill));
    }

    public function testANodeGrantsAReadableListOfLevers(): void
    {
        $reader = new SkillLeverReader($this->scale(), new EquipmentPortCatalog(\dirname(__DIR__, 4)));

        $skill = new Skill();
        $skill->setSlug('soldier-rang3-1');
        $skill->setLevers([
            ['lever' => 'guard', 'points' => 6],
            ['lever' => 'life', 'points' => 3, 'condition' => 'shield'],
        ]);

        $grants = $reader->grantsOf($skill);

        self::assertCount(2, $grants);
        self::assertSame(CombatLever::Guard, $grants[0]->lever);
        self::assertFalse($grants[0]->isConditional());
        self::assertSame('shield', $grants[1]->condition);
        self::assertTrue($grants[1]->isConditional());
        self::assertSame(9, $reader->budgetOf($skill));
    }

    /**
     * Un levier accorde deux fois par le meme nœud est refuse.
     *
     * Sinon un arbre depasse un plafond en le payant en deux fois, sans qu'aucune
     * ligne ne le depasse jamais.
     */
    public function testANodeCannotGrantTheSameLeverTwice(): void
    {
        $reader = new SkillLeverReader($this->scale(), new EquipmentPortCatalog(\dirname(__DIR__, 4)));

        $this->expectException(CombatLeverDefinitionException::class);
        $reader->read([
            ['lever' => 'power', 'points' => 12],
            ['lever' => 'power', 'points' => 12],
        ]);
    }

    public function testANodeCannotGrantMoreThanTheCap(): void
    {
        $reader = new SkillLeverReader($this->scale(), new EquipmentPortCatalog(\dirname(__DIR__, 4)));

        $this->expectException(CombatLeverDefinitionException::class);
        $reader->read([['lever' => 'guard', 'points' => 99]]);
    }

    public function testANodeCannotGrantAnUnknownLever(): void
    {
        $reader = new SkillLeverReader($this->scale(), new EquipmentPortCatalog(\dirname(__DIR__, 4)));

        $this->expectException(CombatLeverDefinitionException::class);
        $reader->read([['lever' => 'lifesteal', 'points' => 4]]);
    }

    /**
     * La configuration livree, avec les entrees demandees remplacees.
     *
     * On part du fichier reel plutot que d'un squelette : un test qui construit
     * son propre vocabulaire ne verifierait que lui-meme.
     *
     * @param array<string, array<string, mixed>> $overrides
     *
     * @return array{levers: array<string, mixed>}
     */
    private function rawWith(array $overrides): array
    {
        $levers = $this->loader()->load();

        foreach ($levers as $name => $definition) {
            // On rend au brut la forme qu'il avait dans le fichier : le
            // normalisateur remplit les clefs absentes de `null`, et les lui
            // rendre telles quelles ferait echouer la relecture pour une
            // mauvaise raison.
            $levers[$name] = array_filter($definition, static fn (mixed $value): bool => $value !== null);
        }

        return ['levers' => array_merge($levers, $overrides)];
    }
}
