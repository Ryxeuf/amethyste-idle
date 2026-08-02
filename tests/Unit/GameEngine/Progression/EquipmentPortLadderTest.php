<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\DataFixtures\Game\SkillFixtures;
use App\Entity\Game\Domain;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\GameEngine\Progression\EquipmentPortDefinitionException;
use PHPUnit\Framework\TestCase;

/**
 * Les lois de l'echelle de port (ONB-20b).
 *
 * GAME_ONBOARDING § 6.0 bis. Trois d'entre elles sont des **reparations**, pas
 * des ajouts : l'echelle heritee existait deja, mais elle etait posee par
 * domaine, donc elle faisait payer un element pour tenir une arme.
 */
class EquipmentPortLadderTest extends TestCase
{
    private function catalog(): EquipmentPortCatalog
    {
        return new EquipmentPortCatalog(\dirname(__DIR__, 4));
    }

    /**
     * Regle (a) — **l'echelon 1 de toute echelle est gratuit**.
     *
     * C'est ce qui fait du parchemin le cout reel. Un echelon 1 payant
     * remettrait un peage a l'entree de l'arbre, juste apres celui qu'on vient
     * de payer.
     */
    public function testTheFirstRungOfEveryLadderIsFree(): void
    {
        foreach ($this->catalog()->families() as $key => $family) {
            self::assertTrue($family['rung1']['free'], sprintf('L\'echelon 1 de « %s » n\'est pas gratuit.', $key));
        }
    }

    /**
     * Regle (c) — **jamais borne par l'element**.
     *
     * Un domaine porte une borne `element x registre` (DOM-01) : une famille
     * enseignee par un seul arbre impose donc son element par la bande. C'etait
     * litteralement le cas — la hache derriere le berserker (feu), le baton
     * derriere le paladin (lumiere **et melee**, pour une arme de sorts).
     *
     * Le test va plus loin que le loader : il exige que les arbres qui
     * enseignent une famille ne partagent pas tous le meme element.
     */
    public function testNoFamilyIsBoundToASingleElement(): void
    {
        $elements = $this->shippedDomainElements();

        foreach ($this->catalog()->families() as $key => $family) {
            $found = [];
            foreach ($family['taught_by'] as $domainKey) {
                self::assertArrayHasKey($domainKey, $elements, sprintf('« %s » est enseignee par un arbre inexistant : %s.', $key, $domainKey));
                $found[$elements[$domainKey]] = true;
            }

            self::assertGreaterThan(1, \count($found), sprintf(
                'La famille « %s » n\'est enseignee que par des arbres de l\'element « %s » : porter cette arme imposerait un element.',
                $key,
                array_key_first($found),
            ));
        }
    }

    /**
     * Le loader refuse une famille enseignee par un seul arbre.
     */
    public function testTheLoaderRefusesAFamilyTaughtByASingleTree(): void
    {
        $this->expectException(EquipmentPortDefinitionException::class);

        $this->catalog()->normalize(['families' => [
            'axe' => [
                'label' => 'Hache',
                'taught_by' => ['berserker'],
                'rung1' => ['reference' => 'port_axe', 'slug' => 'port-axe', 'title' => 'Port de la hache', 'free' => true],
                'rung2' => 'berserk_weapon_t2',
                'rung3' => 'berserk_weapon_t3',
            ],
        ]]);
    }

    /**
     * Le loader refuse un echelon 1 payant.
     */
    public function testTheLoaderRefusesAPaidFirstRung(): void
    {
        $this->expectException(EquipmentPortDefinitionException::class);

        $this->catalog()->normalize(['families' => [
            'axe' => [
                'label' => 'Hache',
                'taught_by' => ['berserker', 'soldier'],
                'rung1' => ['reference' => 'port_axe', 'slug' => 'port-axe', 'title' => 'Port de la hache', 'free' => false],
                'rung2' => 'berserk_weapon_t2',
                'rung3' => 'berserk_weapon_t3',
            ],
        ]]);
    }

    /**
     * Regle (b) — **un echelon ouvert dans un arbre autorise la piece partout**.
     *
     * Le kit d'un arbre se lit par famille : tout arbre qui enseigne une
     * famille livre le meme nœud, donc en ouvrir un seul suffit.
     */
    public function testARungOpenedInOneTreeIsTheSameNodeEverywhere(): void
    {
        $catalog = $this->catalog();

        foreach ($catalog->families() as $family) {
            $reference = $family['rung1']['reference'];

            foreach ($family['taught_by'] as $domainKey) {
                self::assertContains(
                    $reference,
                    $catalog->rungOneReferencesTaughtBy($domainKey),
                    sprintf('L\'arbre « %s » n\'enseigne pas l\'echelon qu\'il declare enseigner.', $domainKey),
                );
            }
        }
    }

    /**
     * L'echelle **suit les paliers d'objets deja en place**, et ne renomme rien.
     *
     * Le canon est explicite : « les competences d'arme existantes SONT cette
     * echelle et ne bougent pas ». Ce qui a change est a quels arbres elles
     * appartiennent, pas leur identite — un slug reecrit casserait les objets
     * qui le referencent.
     */
    public function testTheHigherRungsKeepTheirHistoricalReferences(): void
    {
        $shipped = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/Game/SkillFixtures.php');

        foreach ($this->catalog()->families() as $key => $family) {
            // ONB-20b-b : la loi des references historiques ne vaut que pour
            // les armes — les echelons d'armure n'ont aucun nœud historique a
            // conserver, ils sont generes (cf. ArmorPortLadderTest).
            if ('weapon' !== $family['line']) {
                continue;
            }
            foreach (['rung2', 'rung3'] as $rung) {
                self::assertStringContainsString(
                    sprintf("'%s' => [", $family[$rung]),
                    $shipped,
                    sprintf('L\'echelon %s de « %s » ne designe aucune competence livree.', $rung, $key),
                );
            }
        }
    }

    /**
     * Chaque arme de palier 1 exige l'echelon 1 de sa famille.
     *
     * Sans cela, l'echelle serait declaree mais inerte : le cadrage y insiste,
     * « les armes T1 n'ont aucun prerequis aujourd'hui » est precisement le
     * trou que ce jalon bouche.
     */
    public function testEveryTierOneWeaponRequiresItsFirstRung(): void
    {
        $items = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/ItemFixtures.php');

        // ONB-12b : la famille de l'epee n'a pas de `t1-sword` — son arme de
        // palier 1 s'appelle `short-sword`. Elle echappait donc a cette loi, et
        // c'etait la **seule** dont l'echelon de port restait inerte : l'arme
        // que l'acte I met le plus souvent entre les mains.
        $tierOneWeapons = [
            'axe' => 't1-axe',
            'staff' => 't1-staff',
            'bow' => 't1-bow',
            'crossbow' => 't1-crossbow',
            'dagger' => 't1-dagger',
            'lance' => 't1-lance',
            'sword' => 'short-sword',
        ];

        $declared = array_keys($tierOneWeapons);
        // ONB-20b-b : la loi ne vaut que pour la ligne des armes — les lignes
        // d'armure n'ont pas d'« arme de palier 1 », leur palier 1 est
        // volontairement libre (le kit de depart se porte sans rien) et leur
        // echelle mord aux paliers 2-3 (cf. ArmorPortLadderTest).
        $families = array_keys(array_filter(
            $this->catalog()->families(),
            static fn (array $family): bool => 'weapon' === $family['line'],
        ));
        sort($declared);
        sort($families);

        self::assertSame(
            $families,
            $declared,
            'Une famille de l\'echelle n\'a pas d\'arme de palier 1 declaree ici : son echelon de port serait inerte sans que rien ne le dise.',
        );

        foreach ($tierOneWeapons as $family => $slug) {
            self::assertSame(1, preg_match(
                sprintf("/'slug' => '%s',(.*?)\n            \],/s", preg_quote($slug, '/')),
                $items,
                $match,
            ), sprintf('L\'arme %s a disparu.', $slug));

            self::assertStringContainsString(
                sprintf("'requirements' => ['port_%s']", $family),
                $match[1],
                sprintf('L\'arme %s n\'exige pas l\'echelon 1 de sa famille : l\'echelle serait declaree mais inerte.', $slug),
            );
        }
    }

    /**
     * Regle (d) — **un echelon de port ne porte aucune statistique**.
     *
     * GAME_TREE_ANATOMY § 10, ecart n° 5. Un echelon est une **porte** : il vaut
     * zero point de budget (GAME_ARCHETYPES § 6.1). Les douze echelons livres en
     * portaient pourtant une, et la regle (b) — un echelon appartient a tous les
     * arbres qui l'enseignent — a transforme cette vieille approximation en fuite
     * mesurable : `CombatSkillResolver` applique un passif des qu'**un** des
     * domaines du nœud occupe la case de l'action, donc le `critical` de la
     * dague servait les quatre arbres qui l'enseignent, et le `heal` du baton
     * **dix** arbres, dont neuf de sorts.
     *
     * Le test porte sur le tableau **apres recablage**, pas sur le texte des
     * declarations : c'est la passe qui tient la loi, et c'est elle qu'il faut
     * verifier.
     */
    public function testNoPortRungCarriesCombatStats(): void
    {
        $catalog = $this->catalog();
        $fixtures = new SkillFixtures($catalog);

        $method = new \ReflectionMethod($fixtures, 'getSkillsData');
        /** @var array<string, array<string, mixed>> $skills */
        $skills = $method->invoke($fixtures);

        $offenders = [];
        foreach ($catalog->families() as $key => $family) {
            foreach ([$family['rung1']['reference'], $family['rung2'], $family['rung3']] as $reference) {
                self::assertArrayHasKey($reference, $skills, sprintf('L\'echelon « %s » de « %s » n\'existe pas.', $reference, $key));

                foreach (['damage', 'heal', 'hit', 'critical', 'life'] as $stat) {
                    if (isset($skills[$reference][$stat])) {
                        $offenders[] = sprintf('%s.%s = %s', $reference, $stat, $skills[$reference][$stat]);
                    }
                }
            }
        }

        self::assertSame([], $offenders, sprintf(
            "Ces echelons de port portent une statistique : %s.\n"
            . 'Un echelon est une porte, jamais une recompense — et comme il est partage par tous les arbres qui '
            . "l'enseignent, sa statistique fuit dans des cases qui ne l'ont pas payee.",
            implode(', ', $offenders),
        ));
    }

    /**
     * Cle de domaine => element, lu dans les fixtures livrees.
     *
     * @return array<string, string>
     */
    private function shippedDomainElements(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/DomainFixtures.php');

        preg_match_all("/'([a-z]+)' => \['title' => '[^']+', 'element' => '([a-z]+)'/", $source, $matches, PREG_SET_ORDER);
        self::assertNotEmpty($matches, 'Aucun domaine trouve : la loi ne verifierait rien.');

        $elements = [];
        foreach ($matches as [, $key, $element]) {
            $elements[$key] = $element;
        }

        return $elements;
    }

    /**
     * Chaque echelon se retrouve depuis son slug de competence (ONB-12a).
     *
     * `familyOfPortSkill()` repond a « ceci est-il une epee ? » sans table
     * parallele — c'est deja l'echelle qui le sait.
     *
     * L'echelon 1 declare son slug ici meme : rien ne peut deriver. Les
     * echelons 2 et 3, eux, sont declares par **reference de fixture** — le
     * rewiring de `SkillFixtures` en a besoin — et leur slug s'en deduit par la
     * convention du projet (`_` → `-`). C'est la que se situe le risque : si un
     * slug cessait de la suivre, la famille deviendrait introuvable **en
     * silence**, et un objectif de port ne se terminerait jamais.
     */
    public function testEveryRungIsReachableFromItsSkillSlug(): void
    {
        $skills = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/Game/SkillFixtures.php');
        $catalog = $this->catalog();

        $unreachable = [];
        foreach ($catalog->families() as $key => $family) {
            self::assertSame($key, $catalog->familyOfPortSkill($family['rung1']['slug']));

            foreach ([$family['rung2'], $family['rung3']] as $reference) {
                $slug = str_replace('_', '-', $reference);
                // ONB-20b-b : les echelons d'armure sont generes — leur slug
                // derive de la reference par la meme convention que la
                // resolution, il n'apparait donc pas en litteral dans la
                // source. La loi de resolution, elle, vaut pour tous.
                if ('weapon' === $family['line'] && !str_contains($skills, sprintf("'slug' => '%s'", $slug))) {
                    $unreachable[] = $key . '/' . $slug;
                    continue;
                }
                self::assertSame($key, $catalog->familyOfPortSkill($slug));
            }
        }

        self::assertSame([], $unreachable, sprintf(
            "Ces echelons n'ont aucune competence livree portant ce slug : %s.\nLa famille serait introuvable, et l'objectif de port ne se terminerait jamais.",
            implode(', ', $unreachable),
        ));
    }

    /**
     * Une competence etrangere a l'echelle n'appartient a aucune famille.
     */
    public function testASkillOutsideTheLadderBelongsToNoFamily(): void
    {
        self::assertNull($this->catalog()->familyOfPortSkill('berserk-apprenti-1'));
    }

    /**
     * Garde-fou du garde-fou : la derivation d'element doit trouver un domaine.
     */
    public function testTheElementLookupActuallyFindsDomains(): void
    {
        self::assertArrayHasKey('berserker', $this->shippedDomainElements());
        self::assertSame('fire', $this->shippedDomainElements()['berserker']);
        self::assertInstanceOf(Domain::class, new Domain());
    }
}
