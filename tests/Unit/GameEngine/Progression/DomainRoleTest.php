<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Enum\CombatRegister;
use App\Enum\DomainRole;
use App\GameEngine\Progression\DomainRoleDefinitionException;
use App\GameEngine\Progression\DomainRoleDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * La fonction, troisieme axe du domaine (ARC-01).
 *
 * GAME_ARCHETYPES § 1 : trois arbres d'eau x sorts occupaient la meme case
 * sans que rien, dans le modele, ne dise en quoi ils different. Ce contrat
 * tient les quatre lois du jalon — le triplet unique, la palette a cinq
 * leviers, le principal exclusif, et le silence de l'axe cote joueur.
 */
class DomainRoleTest extends TestCase
{
    /**
     * La grille du § 10, telle que les fixtures la rangent. Ce tableau **est**
     * le canon : s'il diverge de `DomainFixtures`, l'un des deux ment, et le
     * test suivant le dit.
     *
     * @var array<string, array{string, CombatRegister, DomainRole}>
     */
    private const GRID = [
        'pyromancy' => ['fire', CombatRegister::Spell, DomainRole::Assault],
        'berserker' => ['fire', CombatRegister::Melee, DomainRole::Assault],
        'artificer' => ['fire', CombatRegister::Ranged, DomainRole::Control],
        'hydromancer' => ['water', CombatRegister::Spell, DomainRole::Control],
        'healer' => ['water', CombatRegister::Spell, DomainRole::Upkeep],
        'tidecaller' => ['water', CombatRegister::Spell, DomainRole::Assault],
        'stormcaller' => ['air', CombatRegister::Spell, DomainRole::Assault],
        'archer' => ['air', CombatRegister::Ranged, DomainRole::Assault],
        'wanderer' => ['air', CombatRegister::Melee, DomainRole::Control],
        'geomancer' => ['earth', CombatRegister::Spell, DomainRole::Control],
        'defender' => ['earth', CombatRegister::Melee, DomainRole::Bulwark],
        'guardian' => ['earth', CombatRegister::Melee, DomainRole::Upkeep],
        'soldier' => ['metal', CombatRegister::Melee, DomainRole::Bulwark],
        'knight' => ['metal', CombatRegister::Melee, DomainRole::Assault],
        'engineer' => ['metal', CombatRegister::Ranged, DomainRole::Control],
        'hunter' => ['beast', CombatRegister::Ranged, DomainRole::Assault],
        'tamer' => ['beast', CombatRegister::Melee, DomainRole::Control],
        'druid' => ['beast', CombatRegister::Spell, DomainRole::Upkeep],
        'paladin' => ['light', CombatRegister::Melee, DomainRole::Bulwark],
        'priest' => ['light', CombatRegister::Spell, DomainRole::Upkeep],
        'inquisitor' => ['light', CombatRegister::Melee, DomainRole::Assault],
        'assassin' => ['dark', CombatRegister::Melee, DomainRole::Assault],
        'necromancer' => ['dark', CombatRegister::Spell, DomainRole::Control],
        'warlock' => ['dark', CombatRegister::Spell, DomainRole::Assault],
    ];

    /**
     * **Le test du voisin**, rendu verifiable : aucun triplet (element,
     * registre, fonction) n'existe deux fois. C'est la loi qui justifie le
     * troisieme axe — deux arbres qui partagent leur triplet ne different que
     * par des chiffres, et deux arbres qui ne different que par des chiffres
     * sont un seul arbre mal range.
     */
    public function testNoTripletExistsTwice(): void
    {
        $seen = [];
        foreach (self::GRID as $slug => [$element, $register, $role]) {
            $triplet = sprintf('%s x %s x %s', $element, $register->value, $role->value);

            self::assertArrayNotHasKey($triplet, $seen, sprintf(
                'Les domaines "%s" et "%s" partagent le triplet %s : le troisieme axe cesse de distinguer.',
                $seen[$triplet] ?? '',
                $slug,
                $triplet,
            ));
            $seen[$triplet] = $slug;
        }

        self::assertCount(24, $seen, 'La grille du § 10 compte 24 domaines de combat.');
    }

    /**
     * Les fixtures rangent exactement ce que la grille dit — et rien d'autre.
     *
     * Le controle porte sur le texte des fixtures plutot que sur la base : il
     * n'a besoin d'aucun schema, et il tombe des la relecture du fichier.
     */
    public function testTheFixturesPlaceEveryCombatDomainAsTheGridSays(): void
    {
        $fixtures = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/DomainFixtures.php');

        foreach (self::GRID as $slug => [$element, $register, $role]) {
            self::assertMatchesRegularExpression(
                sprintf(
                    "/'%s' => \\[[^\\]]*'element' => '%s'[^\\]]*'register' => CombatRegister::%s,\\s*'role' => DomainRole::%s,/",
                    preg_quote($slug, '/'),
                    preg_quote($element, '/'),
                    ucfirst($register->value),
                    $role->name,
                ),
                $fixtures,
                sprintf('Le domaine "%s" n\'est pas range comme la grille du § 10 le dit.', $slug),
            );
        }

        // Aucun domaine hors combat ne recoit de fonction : `role` suit
        // `register`, et un domaine de recolte n'a ni l'un ni l'autre.
        self::assertSame(
            24,
            substr_count($fixtures, 'DomainRole::'),
            'Une fonction a ete posee sur un domaine hors combat, ou il en manque une.',
        );
    }

    /**
     * La repartition annoncee par le § 10. Le desequilibre est **assume** (un
     * jeu PvE se joue majoritairement en attaquant) : le test le fige pour
     * qu'il reste une decision plutot qu'une derive.
     */
    public function testTheDistributionIsTheOneTheCanonAnnounces(): void
    {
        $counts = array_count_values(array_map(
            static fn (array $case): string => $case[2]->value,
            array_values(self::GRID),
        ));

        self::assertSame(10, $counts[DomainRole::Assault->value]);
        self::assertSame(7, $counts[DomainRole::Control->value]);
        self::assertSame(4, $counts[DomainRole::Upkeep->value]);
        self::assertSame(3, $counts[DomainRole::Bulwark->value]);
    }

    /**
     * Toute fonction a une palette, et une palette c'est cinq leviers : un
     * principal et quatre secondaires.
     */
    public function testEveryFunctionHasAPaletteOfFiveLevers(): void
    {
        $roles = $this->shipped()['roles'];

        self::assertCount(4, $roles);

        foreach (DomainRole::cases() as $role) {
            $palette = $roles[$role->value];

            self::assertCount(4, $palette['secondary']);
            self::assertNotContains($palette['primary'], $palette['secondary']);
            self::assertNotSame('', trim($palette['promise']));
            // § 10.1 : « si le cout est vide, l'archetype n'est pas fini ».
            self::assertNotSame('', trim($palette['structural_cost']));
        }
    }

    /**
     * Le principal est exclusif, et les plafonds sont ceux du canon — `guard`
     * a 15, les trois autres a 20 (§ 4, § 5.0).
     */
    public function testThePrimaryLeversAreExclusiveAndCapped(): void
    {
        $roles = $this->shipped()['roles'];

        $primaries = array_map(static fn (array $p): string => $p['primary'], $roles);
        self::assertSame(['power', 'grip', 'mending', 'guard'], array_values($primaries));
        self::assertSame(\count($primaries), \count(array_unique($primaries)), 'Un levier principal est exclusif.');

        self::assertSame(20, $roles[DomainRole::Assault->value]['primary_cap']);
        self::assertSame(20, $roles[DomainRole::Control->value]['primary_cap']);
        self::assertSame(20, $roles[DomainRole::Upkeep->value]['primary_cap']);
        self::assertSame(15, $roles[DomainRole::Bulwark->value]['primary_cap']);
    }

    /**
     * Deux palettes ne partagent jamais plus de deux leviers secondaires : au
     * dela, les deux fonctions achetent la meme chose.
     */
    public function testTwoPalettesNeverShareMoreThanTwoSecondaries(): void
    {
        $roles = $this->shipped()['roles'];
        $names = array_keys($roles);

        foreach ($names as $i => $left) {
            foreach (\array_slice($names, $i + 1) as $right) {
                $shared = array_intersect($roles[$left]['secondary'], $roles[$right]['secondary']);

                self::assertLessThanOrEqual(2, \count($shared), sprintf(
                    'Les fonctions "%s" et "%s" partagent %d leviers secondaires.',
                    $left,
                    $right,
                    \count($shared),
                ));
            }
        }
    }

    /**
     * La palette **effective** se calcule (§ 5.0) : le capstone consomme 14 pb
     * sur le principal, et ce qui reste achetable ailleurs est le plafond
     * moins 14. A `guard` (15), il reste 1 pb — moins que le nœud le plus
     * modeste, donc rien : un arbre d'encaisse achete `guard` a son sommet ou
     * jamais.
     */
    public function testTheEffectivePaletteIsComputedNotRead(): void
    {
        $loader = new DomainRoleDefinitionLoader(\dirname(__DIR__, 4));

        self::assertSame(6, $loader->remainingOnPrimary(DomainRole::Assault));
        self::assertSame(6, $loader->remainingOnPrimary(DomainRole::Control));
        self::assertSame(6, $loader->remainingOnPrimary(DomainRole::Upkeep));
        self::assertSame(1, $loader->remainingOnPrimary(DomainRole::Bulwark));
    }

    /**
     * La regle des 80/20 **ferme** : ce qui est impose dans la palette plus ce
     * qui est tolere dehors vaut exactement le budget. Sinon l'auteur dispose
     * de points que personne n'a decides.
     */
    public function testTheEightyTwentyRuleCloses(): void
    {
        $budget = $this->shipped()['budget'];

        self::assertSame(50, $budget['total']);
        self::assertSame(40, $budget['min_in_palette']);
        self::assertSame(10, $budget['max_off_palette']);
        self::assertSame(1, $budget['max_off_palette_levers'], 'La teinte est un seul levier etranger.');
        self::assertSame($budget['total'], $budget['min_in_palette'] + $budget['max_off_palette']);
    }

    /**
     * Chaque fonction borne aussi les **intentions** de ses accords (§ 5.1) :
     * une palette de leviers ne dit rien de ce que l'arbre ouvre comme gestes.
     */
    public function testEveryFunctionBoundsTheIntentsOfItsAccords(): void
    {
        $roles = $this->shipped()['roles'];

        self::assertSame(['damage' => 3], $roles[DomainRole::Assault->value]['intents']);
        self::assertSame(['hinder' => 2, 'damage' => 1], $roles[DomainRole::Control->value]['intents']);
        self::assertSame(['heal_or_protect' => 2, 'group_scoped' => 1], $roles[DomainRole::Upkeep->value]['intents']);
        self::assertSame(['protect' => 2, 'ally_or_group_scoped' => 1], $roles[DomainRole::Bulwark->value]['intents']);
    }

    /**
     * **La fonction n'est pas une classe** (§ 1) : elle ne s'affiche nulle
     * part. Aucun gabarit ne la nomme, et aucune cle de traduction ne
     * l'attend — une cle existante finirait par etre appelee.
     */
    public function testTheFunctionIsNeverShownToThePlayer(): void
    {
        $root = \dirname(__DIR__, 4);
        $offenders = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root . '/templates', \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            /* @var \SplFileInfo $file */
            if (!$file->isFile() || $file->getExtension() !== 'twig') {
                continue;
            }
            $content = (string) file_get_contents($file->getPathname());
            if (preg_match('/\.role\b|domain_role|DomainRole/', $content)) {
                $offenders[] = substr($file->getPathname(), \strlen($root) + 1);
            }
        }

        self::assertSame([], $offenders, sprintf(
            "Ces gabarits exposent la fonction du domaine :\n%s\nC'est une contrainte d'auteur, pas un titre de classe.",
            implode("\n", $offenders),
        ));

        foreach (['fr', 'en'] as $locale) {
            $catalog = (string) file_get_contents($root . '/translations/messages.' . $locale . '.json');
            self::assertStringNotContainsString('domain.role', $catalog);
        }
    }

    public function testAPaletteWithoutItsFourSecondariesIsRefused(): void
    {
        $this->expectException(DomainRoleDefinitionException::class);
        $this->expectExceptionMessageMatches('/secondary levers/');

        (new DomainRoleDefinitionLoader('/project'))->normalize($this->rawWith(['secondary' => ['critical']]));
    }

    public function testTwoFunctionsSharingAPrimaryAreRefused(): void
    {
        $this->expectException(DomainRoleDefinitionException::class);
        $this->expectExceptionMessageMatches('/a primary is exclusive/');

        $raw = $this->rawWith([]);
        $raw['roles']['control']['primary'] = 'power';

        (new DomainRoleDefinitionLoader('/project'))->normalize($raw);
    }

    public function testAnEmptyStructuralCostIsRefused(): void
    {
        $this->expectException(DomainRoleDefinitionException::class);
        $this->expectExceptionMessageMatches('/structural_cost/');

        (new DomainRoleDefinitionLoader('/project'))->normalize($this->rawWith(['structural_cost' => '  ']));
    }

    public function testABudgetThatDoesNotCloseIsRefused(): void
    {
        $this->expectException(DomainRoleDefinitionException::class);
        $this->expectExceptionMessageMatches('/80\/20 rule does not close/');

        $raw = $this->rawWith([]);
        $raw['budget']['max_off_palette'] = 5;

        (new DomainRoleDefinitionLoader('/project'))->normalize($raw);
    }

    /**
     * @return array{budget: array<string, int>, capstone_cost: int, roles: array<string, array<string, mixed>>}
     */
    private function shipped(): array
    {
        return (new DomainRoleDefinitionLoader(\dirname(__DIR__, 4)))->load();
    }

    /**
     * @param array<string, mixed> $assaultOverrides
     *
     * @return array<string, mixed>
     */
    private function rawWith(array $assaultOverrides): array
    {
        $roles = [
            'assault' => ['promise' => 'p', 'structural_cost' => 'c', 'primary' => 'power', 'primary_cap' => 20, 'secondary' => ['critical', 'critical_power', 'pierce', 'tempo'], 'intents' => ['damage' => 3]],
            'control' => ['promise' => 'p', 'structural_cost' => 'c', 'primary' => 'grip', 'primary_cap' => 20, 'secondary' => ['hit', 'thrift', 'tempo', 'pierce'], 'intents' => ['hinder' => 2]],
            'upkeep' => ['promise' => 'p', 'structural_cost' => 'c', 'primary' => 'mending', 'primary_cap' => 20, 'secondary' => ['recovery', 'wind', 'thrift', 'ward'], 'intents' => ['heal_or_protect' => 2]],
            'bulwark' => ['promise' => 'p', 'structural_cost' => 'c', 'primary' => 'guard', 'primary_cap' => 15, 'secondary' => ['dodge', 'life', 'ward', 'hit'], 'intents' => ['protect' => 2]],
        ];
        $roles['assault'] = array_merge($roles['assault'], $assaultOverrides);

        return [
            'budget' => ['total' => 50, 'min_in_palette' => 40, 'max_off_palette' => 10, 'max_off_palette_levers' => 1],
            'capstone_cost' => 14,
            'roles' => $roles,
        ];
    }
}
