<?php

namespace App\Tests\Unit\DataFixtures;

use App\GameEngine\Progression\FoundTreeCatalog;
use PHPUnit\Framework\TestCase;

/**
 * Chaque domaine de combat occupe une case element x registre (DOM-01).
 *
 * Le typage se fait **par domaine**, jamais par nœud : c'est ce qui permet de
 * borner 130 passifs livres sans prendre 130 decisions. La contrepartie est
 * qu'un domaine oublie borne mal — et silencieusement. Un domaine de combat
 * sans registre verrait ses passifs traites comme globaux (la clause de
 * retro-compatibilite), donc s'appliquer partout : exactement le defaut que le
 * jalon existe pour fermer, mais restreint a un arbre, donc invisible.
 */
class DomainRegisterTest extends TestCase
{
    /**
     * Les 24 domaines de combat, et le registre attendu de chacun.
     *
     * @var array<string, string>
     */
    private const COMBAT_DOMAINS = [
        'pyromancy' => 'Spell',
        'berserker' => 'Melee',
        'artificer' => 'Ranged',
        'hydromancer' => 'Spell',
        'healer' => 'Spell',
        'tidecaller' => 'Spell',
        'stormcaller' => 'Spell',
        'archer' => 'Ranged',
        'wanderer' => 'Melee',
        'geomancer' => 'Spell',
        'defender' => 'Melee',
        'guardian' => 'Melee',
        'soldier' => 'Melee',
        'knight' => 'Melee',
        'engineer' => 'Ranged',
        'hunter' => 'Ranged',
        'tamer' => 'Melee',
        'druid' => 'Spell',
        'paladin' => 'Melee',
        'priest' => 'Spell',
        'inquisitor' => 'Melee',
        'assassin' => 'Melee',
        'necromancer' => 'Spell',
        'warlock' => 'Spell',
    ];

    /**
     * Les domaines hors combat : recolte et artisanat.
     *
     * Ils n'ont **pas** de registre, et c'est la lettre de GAME_DOMAINS § 2 —
     * leurs passifs sont bornes a leur metier, c'est-a-dire au domaine lui-meme.
     * Leur en donner un les ferait entrer dans le combat par la porte de
     * derriere : le rendement du mineur deviendrait un bonus de mêlée.
     *
     * @var list<string>
     */
    private const NON_COMBAT_DOMAINS = [
        'miner', 'herbalist', 'fisherman', 'skinner', 'lumberjack',
        'blacksmith', 'leatherworker', 'alchimist', 'jeweller', 'cook', 'carpenter', 'tailor',
    ];

    /**
     * @return array<string, array{element: string, register: ?string}>
     */
    private function domains(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/DomainFixtures.php');

        preg_match_all(
            "/'([a-z]+)' => \['title' => '[^']*', 'element' => '([a-z]+)'(?:, 'register' => CombatRegister::([A-Za-z]+))?/",
            $source,
            $matches,
            \PREG_SET_ORDER,
        );

        $domains = [];
        foreach ($matches as $match) {
            $domains[$match[1]] = [
                'element' => $match[2],
                'register' => ($match[3] ?? '') !== '' ? $match[3] : null,
            ];
        }

        self::assertNotEmpty($domains, 'L\'extraction des domaines a echoue : rien n\'est verifie.');

        return $domains;
    }

    public function testEveryCombatDomainDeclaresItsRegister(): void
    {
        $domains = $this->domains();

        $untyped = [];
        foreach (array_keys(self::COMBAT_DOMAINS) as $slug) {
            self::assertArrayHasKey($slug, $domains, sprintf('Le domaine "%s" a disparu.', $slug));
            if ($domains[$slug]['register'] === null) {
                $untyped[] = $slug;
            }
        }

        self::assertSame(
            [],
            $untyped,
            'Ces domaines de combat n\'ont pas de registre : leurs passifs redeviennent globaux, et s\'appliquent '
            . 'a toute action — le defaut meme que DOM-01 ferme.',
        );
    }

    public function testTheCellsAreTheOnesTheDoctrineNames(): void
    {
        $domains = $this->domains();

        $actual = [];
        foreach (array_keys(self::COMBAT_DOMAINS) as $slug) {
            $actual[$slug] = $domains[$slug]['register'];
        }

        self::assertSame(self::COMBAT_DOMAINS, $actual);
    }

    /**
     * Aucun metier n'entre en combat par la porte de derriere.
     */
    public function testNoGatheringOrCraftDomainCarriesARegister(): void
    {
        $domains = $this->domains();

        $intruders = [];
        foreach (self::NON_COMBAT_DOMAINS as $slug) {
            self::assertArrayHasKey($slug, $domains, sprintf('Le domaine "%s" a disparu.', $slug));
            if ($domains[$slug]['register'] !== null) {
                $intruders[] = $slug;
            }
        }

        self::assertSame([], $intruders, 'Ces metiers portent un registre de combat : leur rendement deviendrait un bonus d\'attaque.');
    }

    /**
     * Le catalogue est complet : aucun domaine n'echappe a la question.
     *
     * Sans ce test, ajouter un domaine sans le classer le laisserait dans un
     * angle mort — ni verifie comme domaine de combat, ni comme metier.
     */
    public function testEveryDeclaredDomainIsClassified(): void
    {
        $known = array_merge(array_keys(self::COMBAT_DOMAINS), self::NON_COMBAT_DOMAINS, $this->foundDomains());

        $unclassified = array_values(array_diff(array_keys($this->domains()), $known));

        self::assertSame(
            [],
            $unclassified,
            'Ces domaines ne sont declares ni de combat, ni de metier, ni retrouves : personne ne verifie leur borne.',
        );
    }

    /**
     * Un arbre **retrouve** ne porte pas de registre non plus (DOM-10).
     *
     * Il est hors registre par definition — c'est ce que le mot dit. Lui en
     * donner un le ferait entrer dans le combat par une porte que personne ne
     * regarde, puisqu'il n'a ni vendeur ni entree de catalogue : la fuite de
     * DOM-09 rejouee, mais invisible.
     */
    public function testNoFoundTreeCarriesARegister(): void
    {
        $domains = $this->domains();

        $intruders = [];
        foreach ($this->foundDomains() as $slug) {
            self::assertArrayHasKey($slug, $domains, sprintf('L\'arbre retrouve "%s" n\'est pas livre.', $slug));
            if ($domains[$slug]['register'] !== null) {
                $intruders[] = $slug;
            }
        }

        self::assertSame([], $intruders, 'Un arbre retrouve porte un registre de combat : il accorderait de la puissance, ce que sa loi 1 interdit.');
    }

    /**
     * Les arbres retrouves, lus la ou ils se declarent — jamais recopies ici.
     *
     * @return list<string>
     */
    private function foundDomains(): array
    {
        return (new FoundTreeCatalog(\dirname(__DIR__, 3)))->keys();
    }
}
