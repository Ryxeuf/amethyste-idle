<?php

namespace App\Tests\Unit\DataFixtures;

use App\Enum\Element;
use PHPUnit\Framework\TestCase;

/**
 * MAT-01 — l'element des monstres.
 *
 * Prerequis du butin de materia derive et de la capacite raciale de l'Orc :
 * un monstre sans element rendrait les deux muets, silencieusement. Le
 * contrat tient trois choses : la couverture (chaque monstre declare son
 * element), la reserve (`none` n'appartient qu'aux mannequins), et la
 * coherence (un monstre ne declare jamais une faiblesse a son propre
 * element — la resistance positive, elle, est posee par la regle du loader).
 */
class MonsterElementTest extends TestCase
{
    /**
     * @return array<string, array{element: ?string, resistances: array<string, float>}>
     */
    private function monsters(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/MonsterFixtures.php');

        preg_match_all("/\n            '([a-z_0-9]+)' => \[/", $source, $matches, PREG_OFFSET_CAPTURE);
        $blocks = $matches[1];

        $monsters = [];
        foreach ($blocks as $i => [$slug, $offset]) {
            $end = isset($blocks[$i + 1]) ? $blocks[$i + 1][1] : \strlen($source);
            $block = substr($source, $offset, $end - $offset);

            preg_match("/'element' => '([a-z]+)'/", $block, $element);

            $resistances = [];
            if (preg_match("/'elementalResistances' => \[([^\]]*)\]/", $block, $res)) {
                preg_match_all("/'([a-z]+)' => (-?[0-9.]+)/", $res[1], $pairs, PREG_SET_ORDER);
                foreach ($pairs as $pair) {
                    $resistances[$pair[1]] = (float) $pair[2];
                }
            }

            $monsters[$slug] = [
                'element' => $element[1] ?? null,
                'resistances' => $resistances,
            ];
        }

        return $monsters;
    }

    /**
     * Chaque monstre declare un element, et cet element existe.
     */
    public function testEveryMonsterDeclaresAValidElement(): void
    {
        $monsters = $this->monsters();
        $this->assertNotEmpty($monsters, 'Le test ne verifie rien si l\'extraction echoue.');

        foreach ($monsters as $slug => $data) {
            $this->assertNotNull(
                $data['element'],
                sprintf('%s ne declare pas d\'element : le chargement des fixtures echouera (la cle est obligatoire).', $slug),
            );
            $this->assertNotNull(
                Element::tryFrom($data['element']),
                sprintf('%s declare l\'element inconnu "%s".', $slug, $data['element']),
            );
        }
    }

    /**
     * `none` est reserve aux mannequins d'entrainement : tout ce qui vit dans
     * le monde releve d'un flux.
     */
    public function testNoneBelongsOnlyToTrainingDummies(): void
    {
        $dummies = ['training_dummy_still', 'training_dummy_sparring'];

        foreach ($this->monsters() as $slug => $data) {
            if (\in_array($slug, $dummies, true)) {
                $this->assertSame('none', $data['element'], sprintf('Un mannequin ne releve d\'aucun flux (%s).', $slug));
                continue;
            }

            $this->assertNotSame(
                'none',
                $data['element'],
                sprintf('%s est un vrai monstre : il doit relever d\'un flux (MAT-01).', $slug),
            );
        }
    }

    /**
     * Un monstre resiste a son propre element — jamais l'inverse.
     *
     * La resistance positive par defaut est posee par la regle du loader ; ce
     * que la donnee ne doit jamais faire, c'est la contredire par une valeur
     * negative ecrite a la main.
     */
    public function testNoMonsterIsWeakToItsOwnElement(): void
    {
        foreach ($this->monsters() as $slug => $data) {
            if ($data['element'] === null || $data['element'] === 'none') {
                continue;
            }

            $own = $data['resistances'][$data['element']] ?? null;
            if ($own !== null) {
                $this->assertGreaterThan(
                    0,
                    $own,
                    sprintf('%s declare une resistance negative a son propre element (%s) : la regle MAT-01 est contredite.', $slug, $data['element']),
                );
            }
        }
    }
}
