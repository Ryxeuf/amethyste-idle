<?php

namespace App\Tests\Unit\GameEngine\Economy;

use PHPUnit\Framework\TestCase;

/**
 * Les deux points d'etranglement de l'economie (ECO-17).
 *
 * `EconomyInvariantTest` verifie que les lois tiennent **la ou elles
 * s'appliquent**. Encore faut-il qu'aucun canal ne passe a cote.
 *
 * L'audit d'ECO-17 a trouve deux etranglements bien tenus, et rien pour les
 * garder :
 *
 * 1. **Repartition des Gils** — les trois canaux marchands appellent tous
 *    `AuctionSettlement::compute()`. Un quatrieme qui calculerait sa taxe
 *    lui-meme echapperait a toutes les lois d'un coup.
 * 2. **Entree en inventaire** — les treize services qui donnent un objet a un
 *    joueur passent tous par `InventoryHelper::addItem()`, seul endroit qui
 *    applique la liaison a l'obtention (ECO-01). Un quatorzieme qui appellerait
 *    `$inventory->addItem()` directement ferait entrer dans le monde des objets
 *    « lies a l'obtention » **non lies**, donc librement revendables.
 *
 * Aucune des deux fuites ne leverait d'erreur. La premiere ferait un canal
 * detaxe, la seconde un canal de blanchiment.
 */
class EconomyChokePointTest extends TestCase
{
    private function projectDir(): string
    {
        return \dirname(__DIR__, 4);
    }

    /**
     * @return array<string, string> chemin relatif => contenu
     */
    private function sources(string $directory): array
    {
        $root = $this->projectDir() . '/' . $directory;
        $sources = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            /* @var \SplFileInfo $file */
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            $relative = substr($file->getPathname(), \strlen($this->projectDir()) + 1);
            $sources[$relative] = (string) file_get_contents($file->getPathname());
        }

        return $sources;
    }

    /**
     * Personne ne calcule sa propre taxe.
     *
     * Le motif recherche est le calcul d'un pourcentage sur un prix ou une
     * commission. `AuctionSettlement` est evidemment exempte : c'est elle,
     * l'autorite.
     */
    public function testNoChannelComputesItsOwnTax(): void
    {
        $offenders = [];

        foreach ($this->sources('src/GameEngine') as $path => $source) {
            if (str_ends_with($path, 'Auction/AuctionSettlement.php')) {
                continue;
            }
            if (preg_match('/\$\w*(?:total|price|commission|amount)\w*\s*\*\s*\$\w*(?:tax|rate)\w*/i', $source, $match)) {
                $offenders[$path] = $match[0];
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Un canal calcule sa taxe lui-meme au lieu de passer par AuctionSettlement::compute(). '
            . 'Il echapperait d\'un coup a toutes les lois d\'EconomyInvariantTest.',
        );
    }

    /**
     * Les trois canaux marchands passent bien par l'autorite commune.
     *
     * Sans cette verification, le test precedent resterait vert si un canal
     * cessait simplement de prelever une taxe.
     */
    public function testEveryTradeChannelUsesTheSharedSettlement(): void
    {
        $channels = [
            'src/GameEngine/Auction/AuctionManager.php' => 'hotel des ventes',
            'src/GameEngine/Shop/ShopSaleService.php' => 'echoppe joueur',
            'src/GameEngine/Crafting/CraftOrderManager.php' => 'commande de craft',
        ];

        foreach ($channels as $path => $label) {
            $source = (string) file_get_contents($this->projectDir() . '/' . $path);

            $this->assertStringContainsString(
                'AuctionSettlement::compute(',
                $source,
                sprintf('Le canal « %s » ne passe plus par la repartition commune.', $label),
            );
        }
    }

    /**
     * Rien n'entre dans un inventaire joueur hors du point unique.
     *
     * `InventoryHelper::addItem()` est le seul endroit qui lie un objet
     * « lie a l'obtention » a son proprietaire. Un appel direct a
     * `$inventory->addItem()` contournerait la liaison **sans rien casser** :
     * l'objet arriverait normalement, simplement libre de circuler.
     *
     * Les tas de butin (`Mob::addItem()`) sont exclus : ce ne sont pas des
     * inventaires de joueur, et le butin est lie au ramassage, pas au depot.
     *
     * Une seule exception : `GuildVaultManager::withdraw()` rend un objet au
     * joueur qu'on lui **passe**, quand `InventoryHelper` ecrit dans le sac du
     * joueur de la **session**. Il ne peut donc pas deleguer, et reapplique la
     * regle lui-meme — ce que verifie
     * `testTheGuildVaultExceptionStillAppliesTheRule()`.
     */
    public function testNothingEntersAPlayerInventoryOutsideTheHelper(): void
    {
        $offenders = [];

        foreach ($this->sources('src') as $path => $source) {
            if (str_ends_with($path, 'Helper/InventoryHelper.php')
                || str_ends_with($path, 'Guild/GuildVaultManager.php')) {
                continue;
            }
            foreach (explode("\n", $source) as $number => $line) {
                if (!str_contains($line, '->addItem(')) {
                    continue;
                }
                // Le point d'entree autorise, les tas de butin, et les coffres
                // (guilde, banque) qui ne sont pas des inventaires de joueur.
                if (preg_match('/(?:inventoryHelper|mob|vault|Vault|bank|Bank)\s*(?:\??->|->)\s*addItem\(/', $line)) {
                    continue;
                }
                $offenders[] = sprintf('%s:%d — %s', $path, $number + 1, trim($line));
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Un objet entre dans un inventaire joueur sans passer par InventoryHelper::addItem() : '
            . 'la liaison a l\'obtention (ECO-01) ne s\'appliquera pas, et l\'objet sera librement revendable.',
        );
    }

    /**
     * Le point d'entree applique bien la liaison a l'obtention.
     *
     * Sans cela, le test precedent certifierait un etranglement qui ne fait
     * plus rien.
     */
    public function testTheEntryPointStillBindsOnPickup(): void
    {
        $helper = (string) file_get_contents($this->projectDir() . '/src/Helper/InventoryHelper.php');

        $this->assertStringContainsString('isBoundOnPickup()', $helper);
        $this->assertStringContainsString('setBoundToPlayerId(', $helper);
    }

    /**
     * La seule exception a l'etranglement applique bien la regle elle-meme.
     *
     * C'est ce test qui rend l'exception acceptable. Sans lui, exempter
     * `GuildVaultManager` reviendrait a lui accorder un blanc-seing : la ligne
     * d'exception survivrait a la suppression de la regle qu'elle justifie.
     */
    public function testTheGuildVaultExceptionStillAppliesTheRule(): void
    {
        $source = (string) file_get_contents($this->projectDir() . '/src/GameEngine/Guild/GuildVaultManager.php');

        $this->assertStringContainsString(
            'isBoundOnPickup()',
            $source,
            'Le retrait de coffre de guilde n\'applique plus la liaison a l\'obtention : '
            . 'un objet dont le type a change pendant qu\'il dormait dans le coffre en ressortirait libre.',
        );
        $this->assertStringContainsString('setBoundToPlayerId(', $source);
    }

    /**
     * Tout canal d'echange refuse les objets non echangeables.
     *
     * Un objet lie, ou actuellement equipe, ne doit pouvoir entrer dans aucun
     * canal. Le coffre de guilde pose les deux conditions separement plutot que
     * d'appeler `isExchangeable()` — c'est equivalent, et le test l'accepte
     * explicitement pour ne pas imposer une reecriture sans gain.
     */
    public function testEveryTradeChannelRefusesUntradableItems(): void
    {
        $channels = [
            'src/GameEngine/Auction/AuctionManager.php' => ['isExchangeable('],
            'src/GameEngine/Shop/ShopManager.php' => ['isExchangeable('],
            'src/GameEngine/Crafting/CraftOrderManager.php' => ['isExchangeable('],
            'src/GameEngine/Guild/GuildVaultManager.php' => ['isBound(', 'getGear()'],
        ];

        foreach ($channels as $path => $required) {
            $source = (string) file_get_contents($this->projectDir() . '/' . $path);

            foreach ($required as $needle) {
                $this->assertStringContainsString(
                    $needle,
                    $source,
                    sprintf('%s ne verifie plus qu\'un objet est cessible avant de le laisser circuler.', $path),
                );
            }
        }
    }
}
