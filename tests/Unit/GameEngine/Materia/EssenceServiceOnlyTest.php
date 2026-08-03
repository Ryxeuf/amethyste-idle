<?php

namespace App\Tests\Unit\GameEngine\Materia;

use PHPUnit\Framework\TestCase;

/**
 * L'essence ne s'echange pas — elle se depense en services (FAC-04b).
 *
 * GAME_WORLD § 12.2 : « l'essence est une monnaie secondaire, depensable
 * uniquement en services (jamais en objets — sinon elle concurrence les gils
 * et le craft joueur). » L'invariant se tient **par l'absence de chemin** :
 * aucun canal d'achat, de vente ou d'echange ne connait l'essence. Ce test
 * fige cette absence — ouvrir un canal devient un acte conscient, qui
 * repasse par la doctrine.
 */
class EssenceServiceOnlyTest extends TestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 4);
    }

    /**
     * Aucun canal marchand ne touche l'essence : ni la boutique PNJ, ni
     * l'hotel des ventes, ni les echoppes de joueur, ni les commandes de
     * craft. Elle entre par la fonte, elle sortira par des services — la
     * reparation, l'entretien, l'acceleration (FAC-05, brûleurs d'Honore).
     */
    public function testNoTradeChannelKnowsEssence(): void
    {
        $channels = [
            'src/Controller/Game/ShopController.php',
            'src/GameEngine/Auction/AuctionManager.php',
            'src/Controller/Game/AuctionController.php',
            'src/Controller/Game/PlayerShopController.php',
            'src/GameEngine/Shop/PlayerShopManager.php',
            'src/GameEngine/Craft/CraftOrderManager.php',
        ];

        foreach ($channels as $channel) {
            $path = $this->root() . '/' . $channel;
            if (!is_file($path)) {
                continue;
            }
            self::assertStringNotContainsStringIgnoringCase(
                'essence',
                (string) file_get_contents($path),
                sprintf('%s touche l\'essence : un canal marchand en ferait une monnaie d\'objets.', $channel),
            );
        }
    }

    /**
     * Personne ne depense l'essence aujourd'hui : `removeEssence` n'a aucun
     * appelant hors de l'entite. Le jour ou un service la consomme (brûleurs,
     * entretien), il s'ajoute a la liste blanche — et ce test dit ou.
     */
    public function testEssenceSpendersAreAnExplicitAllowlist(): void
    {
        $allowed = [
            'src/Entity/App/Player.php',
        ];

        $spenders = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root() . '/src'));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }
            $relative = str_replace($this->root() . '/', '', (string) $file->getPathname());
            if (str_contains((string) file_get_contents((string) $file->getPathname()), 'removeEssence')) {
                $spenders[] = $relative;
            }
        }

        sort($spenders);
        sort($allowed);
        self::assertSame($allowed, $spenders, 'Un nouveau depensier d\'essence doit etre un service, et s\'ajouter ici consciemment.');
    }
}
