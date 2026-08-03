<?php

namespace App\Tests\Unit\DataFixtures;

use App\Enum\CombatRegister;
use PHPUnit\Framework\TestCase;

/**
 * Ce que le registre du geste doit au registre de l'arbre (ARC-02b).
 *
 * GAME_ARCHETYPES §3 enonce l'invariant 7 : **tout arbre ouvre au moins un
 * geste de son registre**. C'est la condition d'existence de deux archetypes
 * sur quatre — un arbre de melee dont tous les accords ouvrent des sorts a des
 * passifs bornes a la melee qui ne s'appliquent a aucune action, et le joueur
 * qui l'a monte joue un mage avec une epee.
 *
 * Les 254 gestes livres etaient tous des sorts (ARC-02a : `spell` par defaut).
 * ARC-02b reclasse ceux des deux arbres patrons du §9 — le Soldat et l'Archer.
 * Les douze autres arbres d'arme attendent ARC-08, qui est exactement ce
 * chantier : « conversion mecanique des 20 autres arbres ». La liste d'attente
 * ci-dessous est donc datee et **ne peut que retrecir** : un test verifie
 * qu'aucune entree n'y survit a sa conversion.
 */
class CombatRegisterCoverageTest extends TestCase
{
    /**
     * Les arbres d'arme dont aucun accord ne declare encore son registre.
     *
     * Elle vaut aveu, pas permission : chaque entree est un arbre dont les
     * passifs ne s'appliquent aujourd'hui a aucune action. ARC-08 la vide.
     *
     * Le Chevalier n'y figure pas, et c'est instructif : il n'a rien ete
     * converti pour lui, mais il **partage** des accords avec le Soldat. La
     * conversion d'un arbre patron sert donc ses voisins de registre — ce qui
     * est la meme observation qu'ARC-08 fera a l'echelle des vingt autres.
     *
     * @var list<string>
     */
    private const AWAITING_ARC_08 = [
        'artificer',
        'assassin',
        'berserker',
        'defender',
        'engineer',
        'guardian',
        'hunter',
        'inquisitor',
        'paladin',
        'tamer',
        'wanderer',
    ];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * Registre declare par chaque domaine de combat.
     *
     * @return array<string, string>
     */
    private function domainRegisters(): array
    {
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/DomainFixtures.php');

        $registers = [];
        preg_match_all(
            "/'([a-z_]+)' => \['title' => '[^']*', 'element' => '[a-z]+', 'register' => CombatRegister::(\w+)/",
            $source,
            $matches,
            \PREG_SET_ORDER,
        );
        foreach ($matches as $match) {
            $registers[$match[1]] = strtolower($match[2]);
        }

        self::assertNotEmpty($registers, 'L\'extraction des domaines a echoue : rien n\'est verifie.');

        return $registers;
    }

    /**
     * Registre declare par chaque geste, `spell` par defaut (ARC-02a).
     *
     * @return array<string, string>
     */
    private function spellRegisters(): array
    {
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/SpellFixtures.php');

        $registers = [];
        preg_match_all("/'slug' => '([a-z0-9-]+)',/", $source, $slugs, \PREG_OFFSET_CAPTURE);
        foreach ($slugs[1] as $i => [$slug, $offset]) {
            $end = isset($slugs[1][$i + 1]) ? $slugs[1][$i + 1][1] : \strlen($source);
            $block = substr($source, $offset, $end - $offset);
            $registers[$slug] = preg_match("/'register' => CombatRegister::(\w+)/", $block, $match) === 1
                ? strtolower($match[1])
                : 'spell';
        }

        self::assertNotEmpty($registers, 'L\'extraction des gestes a echoue : rien n\'est verifie.');

        return $registers;
    }

    /**
     * Gestes ouverts par chaque arbre, lus sur les nœuds `materia.unlock`.
     *
     * @return array<string, list<string>>
     */
    private function unlocksByDomain(): array
    {
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/Game/SkillFixtures.php');

        $unlocks = [];
        $parts = preg_split("/\\\$d = '([a-z_]+)';/", $source, -1, \PREG_SPLIT_DELIM_CAPTURE);
        $parts = $parts === false ? [] : $parts;
        for ($i = 1; $i < \count($parts); $i += 2) {
            $domain = $parts[$i];
            preg_match_all("/'unlock' => '([a-z0-9-]+)'/", $parts[$i + 1], $matches);
            $unlocks[$domain] = array_merge($unlocks[$domain] ?? [], $matches[1]);
        }

        self::assertNotEmpty($unlocks, 'L\'extraction des accords a echoue : rien n\'est verifie.');

        return $unlocks;
    }

    /**
     * Toute materia a un registre.
     *
     * Elle n'en declare pas : elle l'herite du geste qu'elle porte (ARC-02a,
     * `Item::getMateriaKind()`). L'invariant porte donc sur les gestes — un
     * geste sans registre lisible produirait une materia sans genre, donc une
     * materia qu'aucun emplacement type n'accepte.
     */
    public function testEveryGestureDeclaresAReadableRegister(): void
    {
        $known = array_map(static fn (CombatRegister $r): string => $r->value, CombatRegister::cases());

        $unknown = [];
        foreach ($this->spellRegisters() as $slug => $register) {
            if (!\in_array($register, $known, true)) {
                $unknown[] = sprintf('%s (%s)', $slug, $register);
            }
        }

        self::assertSame([], $unknown, 'Ces gestes declarent un registre hors du vocabulaire ferme de `CombatRegister`.');
    }

    /**
     * Invariant 7 — tout arbre ouvre au moins un geste de son registre.
     *
     * Les arbres de sorts le verifient depuis toujours (le defaut est `spell`).
     * Les arbres d'arme ne le verifient que depuis leur conversion : ceux qui
     * attendent ARC-08 sont nommes, et seulement eux.
     */
    public function testEveryTreeOpensAtLeastOneGestureOfItsOwnRegister(): void
    {
        $registers = $this->domainRegisters();
        $gestures = $this->spellRegisters();
        $unlocks = $this->unlocksByDomain();

        $mute = [];
        foreach ($registers as $domain => $register) {
            $opened = $unlocks[$domain] ?? [];
            $matching = array_filter($opened, static fn (string $slug): bool => ($gestures[$slug] ?? 'spell') === $register);
            if ($matching === []) {
                $mute[] = $domain;
            }
        }

        sort($mute);

        self::assertSame(
            self::AWAITING_ARC_08,
            $mute,
            'La liste d\'attente d\'ARC-08 ne decrit plus la realite : un arbre y est reste apres sa conversion, '
            . 'ou un arbre converti a perdu le seul geste de son registre.',
        );
    }

    /**
     * Les deux arbres patrons du §9 sont bien convertis, et entierement.
     *
     * Un accord isole suffirait a passer l'invariant 7 en laissant l'arbre
     * majoritairement muet. Le Soldat et l'Archer sont les patrons de la melee
     * et du tir : leurs accords non partages sont tous des techniques.
     */
    public function testTheTwoPatternTreesAreFullyConverted(): void
    {
        $gestures = $this->spellRegisters();
        $unlocks = $this->unlocksByDomain();
        $registers = $this->domainRegisters();

        // Un geste ouvert par plusieurs arbres de registres differents ne peut
        // pas etre reclasse sans arbitrer pour les autres : c'est ARC-08.
        $openedBy = [];
        foreach ($unlocks as $domain => $slugs) {
            foreach ($slugs as $slug) {
                $openedBy[$slug][$registers[$domain] ?? 'spell'] = true;
            }
        }

        $missed = [];
        foreach (['soldier' => 'melee', 'archer' => 'ranged'] as $domain => $register) {
            foreach ($unlocks[$domain] as $slug) {
                if (\count($openedBy[$slug]) > 1) {
                    continue;
                }
                if (($gestures[$slug] ?? 'spell') !== $register) {
                    $missed[] = sprintf('%s → %s', $domain, $slug);
                }
            }
        }

        self::assertSame([], $missed, 'Ces accords exclusifs des arbres patrons sont restes des sorts : leurs passifs de registre ne s\'y appliquent pas.');
    }

    /**
     * La regle 9 tient sur le chemin neuf : l'arbre accorde, il ne donne pas.
     *
     * Reclasser un geste en technique ne change rien a la facon dont on y
     * accede — il reste derriere une materia, qu'il faut posseder, avoir
     * accordee, et avoir sertie. Aucun nœud ne doit citer un sort directement.
     */
    public function testNoNodeHandsOutAGestureDirectly(): void
    {
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/Game/SkillFixtures.php');

        self::assertSame(
            0,
            preg_match_all("/'combat' => \[[^\]]*'spell_slug'/", $source),
            'Un nœud d\'arbre donne un sort actif : la regle 9 veut un accord de materia (`actions.materia.unlock`).',
        );
    }
}
