<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Dto\Domain\DomainCatalogCard;
use PHPUnit\Framework\TestCase;

/**
 * Aucun nœud d'un arbre ferme n'est expose (ONB-09).
 *
 * C'etait **le** defaut de l'ecran de domaine : `DomainInfoController` montrait
 * tous les nœuds de n'importe quel domaine a n'importe qui. Le detail technique
 * etait gratuit, donc le parchemin n'achetait rien.
 *
 * La loi se verifie a trois endroits, parce qu'elle peut se perdre a trois
 * endroits : le **type** qui transporte la donnee, les **gabarits** qui
 * l'affichent, et les **assembleurs** qui choisissent quels arbres servir —
 * Twig comme JSON. Un gardien pose sur le seul gabarit laisserait la porte de
 * derriere grande ouverte.
 */
class ClosedTreeExposureTest extends TestCase
{
    /**
     * Le type du catalogue n'a **aucune** propriete pour un nœud.
     *
     * C'est la garantie la plus forte des trois : ce qui n'existe pas ne
     * s'affiche pas par accident.
     *
     * **`elementTrace` s'y ajoute (ARC-13b-b)**, et la liste devait bouger
     * plutot que s'elargir toute seule : c'est exactement le service que ce
     * test rend. La trace dit ce que l'element **laisse** sur ce qu'il frappe —
     * elle ne nomme ni nœud, ni valeur, ni prerequis, et le § 6.2 l'autorise
     * donc. Ce qu'elle **pourrait** laisser fuir, c'est une duree ou un
     * pourcentage glisse dans la phrase : ce risque-la est tenu ailleurs, par
     * `DomainCatalogContractTest::testATraceNeverLeaksAValue()`.
     */
    public function testTheCatalogueCardCarriesNothingBeyondWhatItMaySay(): void
    {
        $allowed = [
            'id', 'title', 'element', 'register',
            'teaches', 'equips', 'elementTrace',
            'parchmentName', 'parchmentPrice', 'opened',
        ];

        $properties = array_map(
            static fn (\ReflectionProperty $p): string => $p->getName(),
            (new \ReflectionClass(DomainCatalogCard::class))->getProperties(),
        );

        sort($allowed);
        sort($properties);

        self::assertSame($allowed, $properties, implode("\n", [
            'La carte de catalogue porte une propriete de plus (ou de moins).',
            'GAME_ONBOARDING § 6.2 : le catalogue ne dit jamais la liste des nœuds,',
            'les valeurs, les prerequis internes, le premier nœud ni la specialisation.',
        ]));
    }

    /**
     * Les gabarits du catalogue ne nomment jamais une competence.
     */
    public function testTheCatalogueTemplatesNeverMentionASkill(): void
    {
        foreach (['catalog', 'domain_catalog_card'] as $template) {
            $source = $this->read('templates/game/skills/' . $template . '.html.twig');

            // Les jetons visent une **iteration ou un acces de propriete**, pas
            // le mot « skill » : `path('app_game_skills')` est une route et
            // `game.skills.catalog` une cle de traduction. Interdire le mot
            // ferait echouer le test sur un lien de navigation et sur ses
            // propres libelles — un garde-fou qui crie sans rien garder.
            foreach (['card.skills', 'domain.skills', 'skill.', 'requiredPoints', 'canBeAcquired', 'harvestSpots', 'equippableTools'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source, sprintf(
                    'Le gabarit « %s » expose « %s » : un arbre ferme y laisserait fuir ses nœuds.',
                    $template,
                    $forbidden,
                ));
            }
        }
    }

    /**
     * L'ecran de domaine tranche **avant** de lire la moindre competence.
     *
     * L'ordre compte : un jour, quelqu'un ajoutera une variable au gabarit, et
     * il vaut mieux qu'elle n'ait jamais ete calculee.
     */
    public function testTheDomainScreenDecidesBeforeReadingAnySkill(): void
    {
        $source = $this->read('src/Controller/Game/Skill/DomainInfoController.php');

        $gate = strpos($source, 'domain_catalog_card.html.twig');
        $firstSkillRead = strpos($source, 'getSkills()');

        self::assertNotFalse($gate, 'L\'ecran de domaine ne sert plus la carte de catalogue.');
        self::assertNotFalse($firstSkillRead, 'L\'ecran de domaine ne lit plus aucune competence : le test ne verifierait rien.');
        self::assertLessThan($firstSkillRead, $gate, 'Les competences sont lues avant que l\'ouverture de l\'arbre ne soit tranchee.');
    }

    /**
     * Twig et JSON servent les **arbres ouverts**, jamais les domaines ou l'on
     * a seulement de l'experience.
     *
     * La nuance n'est pas cosmetique : `CrossDomainSkillResolver` credite tous
     * les domaines d'un nœud partage, y compris ceux ou l'on n'est jamais
     * entre. Lister « les domaines avec de l'experience » exposait donc des
     * arbres fermes — et, symetriquement, cachait un arbre tout juste ouvert.
     */
    public function testBothScreensListOpenedTreesRatherThanExperiencedOnes(): void
    {
        $sources = [
            'src/Controller/Game/Skill/IndexController.php',
            'src/Service/Skill/SkillTreePayloadBuilder.php',
        ];

        foreach ($sources as $path) {
            $source = $this->read($path);

            self::assertStringContainsString('openedDomains(', $source, sprintf('%s ne liste pas les arbres ouverts.', $path));
            self::assertStringNotContainsString('getDomains()', $source, sprintf(
                '%s liste encore les domaines par experience : un arbre ferme y apparaitrait.',
                $path,
            ));
        }
    }

    private function read(string $relativePath): string
    {
        return (string) file_get_contents(\dirname(__DIR__, 4) . '/' . $relativePath);
    }
}
