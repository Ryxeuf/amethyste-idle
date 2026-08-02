<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * BES-02 — la derivation est un defaut, pas une prison.
 *
 * Les stats d'un monstre viennent du gabarit tier × rang ; une valeur
 * declaree dans la fixture est un **ecart explicite**, et il doit etre
 * commente et connu. Ce test tient la liste fermee : un ecart qui apparait
 * sans y avoir ete inscrit est une regression vers l'ecriture a la main.
 */
class MonsterStatDerivationTest extends TestCase
{
    /**
     * Les seuls monstres autorises a declarer `life`/`hit` :
     * les deux mannequins (valeurs pedagogiques, ONB-11), le boss de zone de
     * la Foret (affronte en groupe par l'assaut) et l'ultime rencontre.
     *
     * @var list<string>
     */
    private const EXPLICIT_STAT_EXCEPTIONS = [
        'training_dummy_still',
        'training_dummy_sparring',
        'forest_guardian',
        'the_first_silence',
    ];

    /**
     * @return array<string, string> slug => bloc de fixture
     */
    private function monsterBlocks(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/MonsterFixtures.php');
        preg_match_all("/\n            '([a-z_0-9]+)' => \[/", $source, $matches, PREG_OFFSET_CAPTURE);
        $entries = $matches[1];

        $blocks = [];
        foreach ($entries as $i => [$slug, $offset]) {
            $end = isset($entries[$i + 1]) ? $entries[$i + 1][1] : \strlen($source);
            $blocks[$slug] = substr($source, $offset, $end - $offset);
        }

        return $blocks;
    }

    public function testLifeAndHitAreDerivedUnlessExplicitlyExcepted(): void
    {
        $blocks = $this->monsterBlocks();
        $this->assertNotEmpty($blocks, 'Le test ne verifie rien si l\'extraction echoue.');

        foreach ($blocks as $slug => $block) {
            $declaresLife = (bool) preg_match("/'life' => \d+,/", $block);
            $declaresHit = (bool) preg_match("/'hit' => \d+,/", $block);

            if (\in_array($slug, self::EXPLICIT_STAT_EXCEPTIONS, true)) {
                $this->assertTrue(
                    $declaresLife,
                    sprintf('%s est un ecart explicite : il doit declarer sa vie (ou sortir de la liste).', $slug),
                );
                continue;
            }

            $this->assertFalse(
                $declaresLife,
                sprintf('%s declare sa vie a la main : les stats se derivent du gabarit tier × rang (BES-02). Un ecart voulu s\'inscrit dans EXPLICIT_STAT_EXCEPTIONS, avec un commentaire dans la fixture.', $slug),
            );
            $this->assertFalse(
                $declaresHit,
                sprintf('%s declare sa precision a la main : elle se derive du gabarit (BES-02).', $slug),
            );
        }
    }

    /**
     * Un ecart explicite reste un ecart **commente** : le bloc porte la
     * raison, pas seulement la valeur.
     */
    public function testExplicitExceptionsAreCommented(): void
    {
        $blocks = $this->monsterBlocks();

        foreach (['forest_guardian', 'the_first_silence'] as $slug) {
            $this->assertMatchesRegularExpression(
                '/Ecart au gabarit/',
                $blocks[$slug],
                sprintf('%s s\'ecarte du gabarit sans dire pourquoi.', $slug),
            );
        }
    }
}
