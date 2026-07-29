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
 *    du build et n'accepte rien. Tant que le genre n'existe pas, aucune piece
 *    ne doit le declarer.
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
     * Aucune piece ne declare un emplacement de technique.
     *
     * Aucune materia de technique n'existe : un tel emplacement n'accepterait
     * rien. Il occuperait une case du build en refusant tout ce qu'on peut lui
     * presenter — un mur sans porte, et un mur qu'aucun message n'expliquerait.
     */
    public function testNoPieceDeclaresASocketNothingCanFill(): void
    {
        $offenders = array_keys(array_filter($this->slotTypes(), static fn (string $type): bool => $type === 'technique'));

        self::assertSame(
            [],
            $offenders,
            'Ces pieces declarent un emplacement de technique alors qu\'aucune materia de technique n\'existe : '
            . 'elles portent un emplacement que rien ne peut remplir.',
        );
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
