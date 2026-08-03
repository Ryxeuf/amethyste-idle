<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * Le typage des emplacements, applique au catalogue livre (DOM-03).
 *
 * Trois garde-fous de GAME_DOMAINS § 3, et chacun ferme une facon de transformer
 * un typage en mur :
 *
 * 1. **Le plancher jour 1** — la premiere materia se sertit toujours, quelle
 *    que soit la tenue. Le palier d'entree reste donc libre.
 * 2. **Aucun emplacement mort** — un emplacement de technique dans un monde sans
 *    materia de technique est pire qu'un emplacement libre : il occupe une case
 *    du build et n'accepte rien. Depuis ARC-02b les techniques existent, donc le
 *    garde-fou change de sens sans changer d'intention : pour chaque genre
 *    d'emplacement livre, il doit exister au moins une materia sertissable.
 * 3. **Le typage sert a quelque chose** — un enum livre sans une seule piece
 *    typee serait un parametre que personne ne lit.
 */
class MateriaSlotTypingTest extends TestCase
{
    /**
     * La ligne tissu au-dessus du palier d'entree (ECO-31).
     *
     * C'est elle que le canon nomme : « la robe porte des emplacements de sort
     * et des bonus de magie ». Le tailleur cesse d'etre « une armure de plus ».
     *
     * @var list<string>
     */
    private const SPELL_TYPED = [
        'fine-linen-hood',
        'fine-linen-robe',
        'fine-linen-gloves',
        'shadowsilk-hood',
        'shadowsilk-robe',
        'archivist-mantle',
        'archivist-robe',
    ];

    /**
     * Le palier 1 du lanceur de sorts, laisse **libre** a dessein.
     *
     * @var list<string>
     */
    private const ENTRY_TIER_CLOTH = ['linen-hood', 'linen-robe', 'linen-gloves'];

    /**
     * OBJ-04 — les armes de lanceur au-dessus du palier d'entree portent des
     * emplacements de sort (GAME_ITEMS §3.4 : « lanceur et tissu → Spell »).
     *
     * @var list<string>
     */
    private const LAUNCHER_TYPED = ['t2-staff', 't3-staff', 'guardian-thorn-staff'];

    /**
     * ARC-02b — le versant Technique de la derivation, ouvert par les
     * premieres materia de technique.
     *
     * GAME_ITEMS §3.4 : « armes de melee et de tir → Technique », « armure de
     * plaque → Technique ». Comme pour les lanceurs, **la famille decide** :
     * la grille neutre porte le typage par famille d'arme, la ligne de plaque
     * par famille d'armure — jamais piece par piece.
     *
     * Le palier d'entree en est exclu par la meme regle que le lin : une piece
     * de niveau <= 4 reste libre, sinon un debutant en cotte de mailles de fer
     * decouvrirait que sa premiere materia ne se sertit pas.
     *
     * @var list<string>
     */
    private const TECHNIQUE_TYPED = [
        // Les six familles d'armes de melee et de tir de la grille neutre.
        't2-axe', 't2-bow', 't2-crossbow', 't2-dagger', 't2-lance', 't2-sword',
        't3-axe', 't3-bow', 't3-crossbow', 't3-dagger', 't3-lance', 't3-sword',
        // La ligne de plaque au-dessus du palier d'entree.
        'iron-chestplate', 'iron-greaves',
        'mithril-helm', 'mithril-cuirass', 'mithril-greaves',
        'steel-chainmail', 'steel-plate', 'heavy-steel-plate',
    ];

    /**
     * Le cuir, et ce que le modele ne sait pas encore dire.
     *
     * GAME_ITEMS §3.4 range le cuir a part : « 1 `Spell`, le reste
     * `Technique` » — le cuir paie sa polyvalence par un emplacement de moins
     * de chaque cote. Or `materiaSlotType` est porte par la **piece**, donc par
     * tous ses emplacements a la fois : la regle du cuir est la seule des six
     * lignes du canon qui demande un typage **par emplacement**, et `Slot` n'en
     * porte pas.
     *
     * Le cuir reste donc `free`, qui est l'approximation honnete de « mixte »
     * dans le modele actuel : il accepte les deux genres. Ce qui manque est le
     * **plafond** (au plus un emplacement de sort), pas la polyvalence.
     *
     * Ce test existe pour empecher la fausse reparation : typer le cuir
     * `technique` en bloc supprimerait silencieusement la moitie de la regle.
     *
     * @var list<string>
     */
    private const LEATHER = [
        'leather-gloves',
        'leather-belt',
        'leather-shoulders',
        'leather-pants',
        'exotic-leather-vest',
        'leather-boots',
        'leather-hat',
        'leather-helmet',
    ];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * Slug de piece => type d'emplacement declare (`free` par defaut).
     *
     * @return array<string, string>
     */
    private function slotTypes(): array
    {
        $types = [];

        foreach ((array) glob($this->root() . '/fixtures/game/item/*.yaml') as $file) {
            $slug = null;
            foreach (explode("\n", (string) file_get_contents((string) $file)) as $line) {
                if (preg_match("/^\s+slug: '([a-z0-9-]+)'/", $line, $match) === 1) {
                    $slug = $match[1];
                    $types[$slug] = 'free';

                    continue;
                }

                if ($slug !== null && preg_match("/^\s+materia_slot_type: '([a-z]+)'/", $line, $match) === 1) {
                    $types[$slug] = $match[1];
                }
            }
        }

        // OBJ-04 : les pieces PHP comptent aussi — les armes de lanceur de la
        // grille neutre y vivent.
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/ItemFixtures.php');
        preg_match_all("/'slug' => '([a-z0-9-]+)'/", $source, $slugs, \PREG_OFFSET_CAPTURE);
        foreach ($slugs[1] as $i => [$slug, $offset]) {
            $end = isset($slugs[1][$i + 1]) ? $slugs[1][$i + 1][1] : \strlen($source);
            $block = substr($source, $offset, $end - $offset);
            $types[$slug] ??= 'free';
            if (preg_match("/'materiaSlotType' => MateriaSlotType::([A-Za-z]+)/", $block, $match)) {
                $types[$slug] = strtolower($match[1]);
            }
        }

        self::assertNotEmpty($types, 'L\'extraction des pieces a echoue : rien n\'est verifie.');

        return $types;
    }

    /**
     * Le typage existe, et il porte sur la ligne que le canon nomme.
     */
    public function testTheClothLineCarriesSpellSockets(): void
    {
        $types = $this->slotTypes();

        $wrong = [];
        foreach (self::SPELL_TYPED as $slug) {
            self::assertArrayHasKey($slug, $types, sprintf('La piece "%s" a disparu.', $slug));
            if ($types[$slug] !== 'spell') {
                $wrong[] = $slug;
            }
        }

        self::assertSame([], $wrong, 'Ces pieces de tissu ne portent plus d\'emplacement de sort : le tailleur redevient une armure de plus.');
    }

    /**
     * Le plancher jour 1 : la tenue d'entree du lanceur de sorts reste libre.
     *
     * Typer le lin en « sort » ne casserait rien **aujourd'hui** — toutes les
     * materia livrees sont des materia de sort. C'est justement pour cela que le
     * test existe : le jour ou une materia de technique arrivera, un debutant en
     * robe de lin decouvrirait qu'il ne peut pas la sertir, et rien n'aurait
     * signale la regression.
     */
    public function testTheEntryTierStaysFreeForEveryone(): void
    {
        $types = $this->slotTypes();

        $typed = [];
        foreach (self::ENTRY_TIER_CLOTH as $slug) {
            self::assertArrayHasKey($slug, $types, sprintf('La piece "%s" a disparu.', $slug));
            if ($types[$slug] !== 'free') {
                $typed[] = $slug;
            }
        }

        self::assertSame([], $typed, 'Le palier d\'entree a ete type : le plancher jour 1 ne tient plus.');
    }

    /**
     * Aucun emplacement n'est un mur sans porte.
     *
     * Le test disait l'inverse jusqu'a ARC-02 : il **interdisait** une piece
     * typee `technique`, parce qu'aucune materia de technique n'existait — un
     * tel emplacement aurait occupe une case du build en refusant tout ce
     * qu'on peut lui presenter.
     *
     * Les techniques existent (ARC-02b : les gestes des arbres Soldat et
     * Archer declarent leur registre, et la materia en herite). Le test change
     * donc de sens sans changer d'intention : **pour chaque type
     * d'emplacement livre, il doit exister au moins une materia sertissable.**
     * C'est la meme loi, verifiee dans l'autre sens.
     */
    public function testNoSocketIsAWallWithoutADoor(): void
    {
        $declared = array_unique(array_values($this->slotTypes()));
        $fillable = $this->materiaKindsInFixtures();

        foreach ($declared as $type) {
            if ($type === 'free') {
                // Un emplacement libre accepte tout : il ne peut pas etre un mur.
                continue;
            }

            self::assertContains(
                $type,
                $fillable,
                sprintf(
                    'Des pieces declarent un emplacement « %s » alors qu\'aucune materia de ce genre n\'existe : '
                    . 'elles portent un emplacement que rien ne peut remplir.',
                    $type,
                ),
            );
        }
    }

    /**
     * Les genres de materia que les donnees livrent reellement.
     *
     * Le genre se **derive** du registre du geste porte (ARC-02a) : un geste
     * de melee ou de distance donne une materia de technique, un sort donne
     * une materia de sort. On lit donc les registres declares par les gestes,
     * jamais une colonne de genre — qui pourrait mentir.
     *
     * @return list<string>
     */
    private function materiaKindsInFixtures(): array
    {
        $spells = (string) file_get_contents($this->root() . '/src/DataFixtures/SpellFixtures.php');

        $kinds = [];
        // Tout geste livre est un sort par defaut ; ceux qui declarent un
        // registre d'arme donnent des materia de technique.
        if (str_contains($spells, "'slug' => '")) {
            $kinds[] = 'spell';
        }
        if (preg_match('/CombatRegister::(Melee|Ranged)/', $spells) === 1) {
            $kinds[] = 'technique';
        }

        return $kinds;
    }

    /**
     * OBJ-04 — les armes de lanceur au-dessus du palier d'entree sont typees
     * sort : la famille decide, jamais la piece (GAME_ITEMS §3.4).
     */
    public function testLauncherWeaponsCarrySpellSockets(): void
    {
        $types = $this->slotTypes();

        $wrong = [];
        foreach (self::LAUNCHER_TYPED as $slug) {
            self::assertArrayHasKey($slug, $types, sprintf('La piece "%s" a disparu.', $slug));
            if ('spell' !== $types[$slug]) {
                $wrong[] = $slug;
            }
        }

        self::assertSame([], $wrong, 'Ces armes de lanceur ne portent plus d\'emplacement de sort (OBJ-04).');
    }

    /**
     * ARC-02b — le versant Technique du typage entre en service.
     *
     * Il attendait les materia de technique : un emplacement qui n'accepte
     * qu'un genre inexistant est pire qu'un emplacement libre. Les gestes des
     * arbres Soldat et Archer declarent desormais leur registre, donc la moitie
     * du vestiaire que le canon nomme peut enfin dire ce qu'elle accepte.
     */
    public function testTheWeaponAndPlateLinesCarryTechniqueSockets(): void
    {
        $types = $this->slotTypes();

        $wrong = [];
        foreach (self::TECHNIQUE_TYPED as $slug) {
            self::assertArrayHasKey($slug, $types, sprintf('La piece "%s" a disparu.', $slug));
            if ($types[$slug] !== 'technique') {
                $wrong[] = $slug;
            }
        }

        self::assertSame([], $wrong, 'Ces armes de melee/tir ou ces pieces de plaque ne portent plus d\'emplacement de technique (GAME_ITEMS §3.4).');
    }

    /**
     * Le cuir reste libre tant que le modele ne sait pas typer un emplacement.
     *
     * Voir la constante `LEATHER` : « 1 Spell, le reste Technique » est une
     * regle **par emplacement**, et `materiaSlotType` est porte par la piece.
     * Le typer en bloc perdrait la moitie de la regle sans que rien ne le dise.
     */
    public function testLeatherKeepsBothDoorsOpen(): void
    {
        $types = $this->slotTypes();

        $typed = [];
        foreach (self::LEATHER as $slug) {
            self::assertArrayHasKey($slug, $types, sprintf('La piece "%s" a disparu.', $slug));
            if ($types[$slug] !== 'free') {
                $typed[] = $slug;
            }
        }

        self::assertSame([], $typed, 'Le cuir a ete type en bloc : la moitie de la regle du canon (« 1 Spell, le reste Technique ») a disparu en silence.');
    }

    /**
     * OBJ-04 — les emplacements progressent avec le palier : au moins 1 / 2 / 3
     * par bande de niveau (1-4, 5-12, 13+). C'est la promesse de GAME_WORLD
     * §2.1 (« l'equipement de haut niveau offre plus d'emplacements ») — un
     * plancher, jamais un ecretage : les pieces uniques gardent leur avance.
     */
    public function testSocketsProgressWithTheTier(): void
    {
        $offenders = [];

        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/ItemFixtures.php');
        preg_match_all("/'type' => '([a-z_]+)'/", $source, $types, \PREG_OFFSET_CAPTURE);
        foreach ($types[1] as $i => [$type, $offset]) {
            if ('gear' !== $type) {
                continue;
            }
            $end = isset($types[1][$i + 1]) ? $types[1][$i + 1][1] : \strlen($source);
            $block = substr($source, $offset, $end - $offset);
            preg_match("/'slug' => '([a-z0-9-]+)'/", $block, $slug);
            preg_match("/'level' => (\d+)/", $block, $level);
            preg_match("/'materiaSlots' => (\d+)/", $block, $slots);
            $floor = $this->slotFloor((int) ($level[1] ?? 1));
            if ((int) ($slots[1] ?? 0) < $floor) {
                $offenders[] = sprintf('%s (%d < %d)', $slug[1] ?? '?', (int) ($slots[1] ?? 0), $floor);
            }
        }

        foreach ((array) glob($this->root() . '/fixtures/game/item/*.yaml') as $file) {
            preg_match_all('/^  [a-z0-9_]+ \(extends item\):((?:\n    .*)+)/m', (string) file_get_contents((string) $file), $blocks);
            foreach ($blocks[1] as $block) {
                if (!preg_match('/type: [\'"]gear[\'"]/', $block)) {
                    continue;
                }
                preg_match("/slug: '([a-z0-9-]+)'/", $block, $slug);
                preg_match('/level: (\d+)/', $block, $level);
                preg_match('/materia_slots: (\d+)/', $block, $slots);
                $floor = $this->slotFloor((int) ($level[1] ?? 1));
                if ((int) ($slots[1] ?? 0) < $floor) {
                    $offenders[] = sprintf('%s (%d < %d)', $slug[1] ?? '?', (int) ($slots[1] ?? 0), $floor);
                }
            }
        }

        self::assertSame(
            [],
            $offenders,
            sprintf('Des pieces portent moins d\'emplacements que le plancher de leur palier (OBJ-04) : %s.', implode(', ', $offenders)),
        );
    }

    private function slotFloor(int $level): int
    {
        return $level <= 4 ? 1 : ($level <= 12 ? 2 : 3);
    }

    /**
     * Le catalogue reste tres majoritairement libre.
     *
     * Le typage est une exception nommee, pas un regime. S'il devenait la
     * norme, l'auto-limitation cesserait d'etre emergente : le joueur lirait des
     * interdits partout au lieu de constater ce que sa tenue permet.
     */
    public function testTypingStaysTheException(): void
    {
        $types = $this->slotTypes();
        $typed = \count(array_filter($types, static fn (string $type): bool => $type !== 'free'));

        self::assertGreaterThan(0, $typed, 'Aucune piece typee : l\'enum est un parametre que personne ne lit.');
        self::assertLessThan(
            \count($types) / 4,
            $typed,
            'Plus d\'un quart du catalogue est type : l\'auto-limitation cesse d\'etre emergente.',
        );
    }
}
