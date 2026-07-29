<?php

namespace App\Tests\Unit\GameEngine\Crafting;

use App\Enum\CraftSpecialization;
use App\GameEngine\Crafting\CraftBranchCatalog;
use App\GameEngine\Crafting\CraftBranchDefinitionException;
use PHPUnit\Framework\TestCase;

/**
 * Les branches terminales des arbres d'artisanat (DOM-04).
 *
 * GAME_DOMAINS § 6 : « une branche terminale exclusive **par arbre** ». Ce qui
 * se verrouille ici est la forme du catalogue, et surtout le refus qui compte :
 * **une branche unique n'est pas un choix**. Elle se prendrait toujours, et le
 * renoncement — qui est tout le sujet de la specialisation — disparaitrait sans
 * que rien ne le dise.
 */
class CraftBranchCatalogTest extends TestCase
{
    private function catalog(): CraftBranchCatalog
    {
        return new CraftBranchCatalog(\dirname(__DIR__, 4));
    }

    /**
     * Les sept arbres d'artisanat offrent une specialisation.
     *
     * Les trois metiers de la Piste H (cuisinier, charpentier, tailleur) avaient
     * des arbres et des recettes, mais aucune facon de s'y specialiser : le
     * tailleur pouvait etre le seul de la region a coudre des robes sans que
     * rien ne le dise.
     */
    public function testEveryCraftTreeOffersABranch(): void
    {
        $crafts = $this->catalog()->specializableCrafts();

        self::assertCount(7, $crafts);
        self::assertSame(CraftSpecialization::cases(), $crafts);
    }

    /**
     * Deux branches par arbre, et pas trois : le choix doit se raconter en une
     * phrase (« je suis le forgeron d'armes de la region »).
     */
    public function testEachTreeOffersExactlyTwoBranches(): void
    {
        $catalog = $this->catalog();

        foreach (CraftSpecialization::cases() as $craft) {
            self::assertCount(2, $catalog->branchesOf($craft), sprintf('L\'arbre "%s" n\'offre plus deux branches.', $craft->value));
        }
    }

    public function testTheCanonicalCouplesAreDeclared(): void
    {
        $catalog = $this->catalog();

        // Les deux couples que le canon nomme explicitement.
        self::assertSame('Forgeron d\'armes', $catalog->labelOf(CraftSpecialization::Forgeron, 'weapons'));
        self::assertSame('Forgeron d\'armures', $catalog->labelOf(CraftSpecialization::Forgeron, 'armour'));
        self::assertSame('Alchimiste des remedes', $catalog->labelOf(CraftSpecialization::Alchimiste, 'remedies'));
        self::assertSame('Alchimiste des toxines', $catalog->labelOf(CraftSpecialization::Alchimiste, 'toxins'));
    }

    public function testAnUnknownBranchIsNotAccepted(): void
    {
        self::assertFalse($this->catalog()->hasBranch(CraftSpecialization::Forgeron, 'cuisine'));
        self::assertTrue($this->catalog()->hasBranch(CraftSpecialization::Forgeron, 'weapons'));
    }

    /**
     * Le refus central : une branche unique n'est pas un choix.
     */
    public function testATreeWithASingleBranchIsRefused(): void
    {
        $this->expectException(CraftBranchDefinitionException::class);
        $this->expectExceptionMessageMatches('/one branch is not a choice/');

        $this->catalog()->normalize([
            'crafts' => [
                'forgeron' => [
                    'label' => 'Forgeron',
                    'branches' => ['weapons' => ['label' => 'Armes', 'description' => '...']],
                ],
            ],
        ]);
    }

    public function testAnUnknownCraftIsRefused(): void
    {
        $this->expectException(CraftBranchDefinitionException::class);
        $this->expectExceptionMessageMatches('/not a known craft/');

        $this->catalog()->normalize(['crafts' => ['sorcier' => ['label' => 'Sorcier', 'branches' => []]]]);
    }

    public function testAMissingCatalogueIsRefusedAtLoad(): void
    {
        $this->expectException(CraftBranchDefinitionException::class);
        $this->expectExceptionMessageMatches('/not found/');

        (new CraftBranchCatalog('/nowhere'))->load('/nowhere/config/game/craft_branches.yaml');
    }

    /**
     * La branche de reprise existe pour chaque arbre.
     *
     * La migration des joueurs deja specialises s'appuie dessus : leur ancienne
     * valeur designait un metier et pas une branche, et il fallait bien en
     * choisir une. Un arbre sans premiere branche ferait echouer la reprise en
     * silence — la ligne serait ecrite avec un `NULL`.
     */
    public function testEveryTreeHasAFirstBranchForTheMigration(): void
    {
        $catalog = $this->catalog();

        foreach (CraftSpecialization::cases() as $craft) {
            self::assertNotNull($catalog->firstBranchOf($craft), sprintf('L\'arbre "%s" n\'a pas de branche de reprise.', $craft->value));
        }
    }

    /**
     * Les branches de reprise sont **celles que la migration SQL cite**.
     *
     * La migration duplique ces sept valeurs pour ne pas dependre du conteneur
     * de services. La duplication est assumee ; la divergence ne l'est pas.
     */
    public function testTheMigrationFallbacksMatchTheCatalogue(): void
    {
        $catalog = $this->catalog();

        $expected = [
            'forgeron' => 'weapons',
            'alchimiste' => 'remedies',
            'tanneur' => 'armour',
            'joaillier' => 'focus',
            'cuisinier' => 'feast',
            'charpentier' => 'ranged',
            'tailleur' => 'spellrobes',
        ];

        foreach ($expected as $craft => $branch) {
            self::assertSame(
                $branch,
                $catalog->firstBranchOf(CraftSpecialization::from($craft)),
                sprintf('La branche de reprise de "%s" a change : la migration SQL cite une valeur obsolete.', $craft),
            );
        }
    }
}
