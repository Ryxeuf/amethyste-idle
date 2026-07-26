<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * Garde-fou du contenu declaratif (tache 128a).
 *
 * `MonsterFixtures` resout ses sorts par `getReference($cle)`. Une cle qui
 * n'existe pas dans `SpellFixtures` fait echouer le chargement — mais un
 * `danger_alert` qui pointe un slug inexistant, lui, **ne casse rien** : le
 * monstre garde son alerte, elle ne declenche simplement jamais le sort promis.
 *
 * C'est le motif que cette campagne a rencontre une dizaine de fois : de la
 * donnee declarative que plus rien ne relie a du comportement. Ici il coute
 * un test.
 */
class MonsterSpellReferenceTest extends TestCase
{
    /**
     * @return array{spellKeys: list<string>, spellSlugs: list<string>}
     */
    private function spellCatalog(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/SpellFixtures.php');

        preg_match_all("/\n            '([a-z_0-9]+)' => \[/", $source, $keys);
        preg_match_all("/'slug' => '([a-z0-9-]+)'/", $source, $slugs);

        return ['spellKeys' => $keys[1], 'spellSlugs' => $slugs[1]];
    }

    private function monsterSource(): string
    {
        return (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/MonsterFixtures.php');
    }

    /**
     * Chaque sort et chaque attaque cites par un monstre existent.
     */
    public function testEveryMonsterSpellReferenceResolves(): void
    {
        $catalog = $this->spellCatalog();
        $source = $this->monsterSource();

        $referenced = [];
        preg_match_all("/'spells' => \[([^\]]*)\]/", $source, $blocks);
        foreach ($blocks[1] as $block) {
            preg_match_all("/'([a-z_0-9]+)'/", $block, $refs);
            $referenced = array_merge($referenced, $refs[1]);
        }
        preg_match_all("/'attack' => '([a-z_0-9]+)'/", $source, $attacks);
        $referenced = array_merge($referenced, $attacks[1]);

        // `none_attack_1` est l'attaque a mains nues : elle vient d'ailleurs.
        $referenced = array_diff(array_unique($referenced), ['none_attack_1']);

        $this->assertNotEmpty($referenced, 'Le test ne verifie rien si l\'extraction echoue.');
        $this->assertSame(
            [],
            array_values(array_diff($referenced, $catalog['spellKeys'])),
            'Un monstre cite un sort absent de SpellFixtures : le chargement des fixtures echouera.',
        );
    }

    /**
     * Chaque `danger_alert.spell` designe un slug de sort reel.
     *
     * Ce cas est le vrai piege : contrairement aux references de sort, une
     * alerte pointant dans le vide **ne fait rien echouer**. Le monstre annonce
     * un coup qu'il ne portera jamais.
     */
    public function testEveryDangerAlertPointsAtARealSpell(): void
    {
        $catalog = $this->spellCatalog();

        preg_match_all("/'spell' => '([a-z0-9-]+)'/", $this->monsterSource(), $alerts);
        $referenced = array_unique($alerts[1]);

        $this->assertNotEmpty($referenced, 'Le test ne verifie rien si l\'extraction echoue.');
        $this->assertSame(
            [],
            array_values(array_diff($referenced, $catalog['spellSlugs'])),
            'Une alerte de danger annonce un sort qui n\'existe pas : elle ne se declenchera jamais.',
        );
    }
}
