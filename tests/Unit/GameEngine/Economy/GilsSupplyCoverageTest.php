<?php

namespace App\Tests\Unit\GameEngine\Economy;

use PHPUnit\Framework\TestCase;

/**
 * Garde-fou de la couverture de la masse monetaire (ECO-15).
 *
 * `GilsSupplyService` somme trois soldes et deux escrows. Rien n'oblige un
 * futur solde a s'y ajouter : une nouvelle colonne de Gils sur une entite se
 * mettrait a exister, a se remplir, et la masse mesuree deviendrait
 * silencieusement fausse — la pire des issues pour un indicateur, puisqu'elle
 * ressemble en tout point a une mesure juste.
 *
 * Ce test force le choix : toute colonne dont le nom parle de Gils est soit un
 * **solde** compte par le service, soit declaree ici comme n'en etant pas un.
 * Il n'y a pas de troisieme cas, et surtout pas celui du silence.
 *
 * Limite connue : les escrows ne se nomment pas d'apres les Gils
 * (`auction_listing.current_bid`, `craft_order.commission`). Ils sont couverts
 * par `GilsSupplyTrendTest`, pas par ce scan.
 */
class GilsSupplyCoverageTest extends TestCase
{
    /**
     * Colonnes qui parlent de Gils sans etre un solde detenu.
     *
     * Un prix affiche, une recompense promise, une ligne de journal : aucune de
     * ces valeurs n'est de la monnaie en circulation. Les compter reviendrait a
     * additionner l'etiquette au contenu du porte-monnaie.
     *
     * @var array<string, string> colonne => raison
     */
    private const NOT_A_BALANCE = [
        'cost_gils' => 'RegionUpgrade : prix affiche d\'une amelioration, pas un solde',
        'gils_awarded' => 'GroupDungeonClear : trace d\'une recompense deja versee dans une bourse',
        'tax_gils' => 'ShopSaleLog : journal d\'une taxe deja prelevee',
        'net_gils' => 'ShopSaleLog : journal d\'un net deja verse',
        'gils_reward' => 'GuildQuest : recompense promise, pas encore versee',
        'gils_per_unit' => 'FoundryContract : prix affiche du contrat de la semaine (FAC-05), verse dans une bourse a la livraison',
        'reward_gils' => 'SmugglingContract : prime figee a l\'acceptation (FAC-08), versee dans une bourse a la livraison',
        'player_gils' => 'GilsSupplySnapshot : le releve lui-meme',
        'guild_gils' => 'GilsSupplySnapshot : le releve lui-meme',
        'shop_gils' => 'GilsSupplySnapshot : le releve lui-meme',
        'escrow_gils' => 'GilsSupplySnapshot : le releve lui-meme',
        'gils_from_rents' => 'Guild : cumul de ce que les habitants ont rapporte (FOY-19), deja compte dans `gils_treasury`',
        'gils_paid' => 'AwakeningRite : le prix fige a l\'ouverture du rite (REP-04), deja retire de la bourse et deja taxe',
    ];

    /** Soldes reellement detenus, et donc sommes par le service. */
    private const BALANCES = ['gils', 'vault_gils', 'gils_treasury'];

    /**
     * @return list<string>
     */
    private function gilsColumns(): array
    {
        $columns = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(\dirname(__DIR__, 4) . '/src/Entity', \RecursiveDirectoryIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            /* @var \SplFileInfo $file */
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            $source = (string) file_get_contents($file->getPathname());
            if (preg_match_all("/ORM\\\\Column\(name: '([a-z_]*gils[a-z_]*)'/", $source, $matches)) {
                $columns = array_merge($columns, $matches[1]);
            }
        }

        sort($columns);

        return array_values(array_unique($columns));
    }

    /**
     * Toute colonne de Gils est classee : solde compte, ou non-solde justifie.
     */
    public function testEveryGilsColumnIsClassified(): void
    {
        $columns = $this->gilsColumns();
        $this->assertNotEmpty($columns, 'Le test ne verifie rien si l\'extraction echoue.');

        $classified = array_merge(self::BALANCES, array_keys(self::NOT_A_BALANCE));

        $this->assertSame(
            [],
            array_values(array_diff($columns, $classified)),
            'Ces colonnes de Gils ne sont ni comptees dans la masse monetaire ni declarees comme n\'etant pas un solde. '
            . 'Si c\'est un solde detenu, l\'ajouter a GilsSupplyService::measure() ; sinon, le justifier dans NOT_A_BALANCE.',
        );
    }

    /**
     * Chaque solde declare est bien somme par le service.
     *
     * Sans cela, la liste `BALANCES` pourrait grossir sans que le service
     * change : le garde-fou passerait au vert en certifiant une couverture qui
     * n'existe pas.
     */
    public function testEveryDeclaredBalanceIsSummedByTheService(): void
    {
        $service = (string) file_get_contents(
            \dirname(__DIR__, 4) . '/src/GameEngine/Economy/GilsSupplyService.php',
        );

        foreach (self::BALANCES as $column) {
            $this->assertMatchesRegularExpression(
                '/SUM\(\w+\.' . preg_quote($column, '/') . '\)/',
                $service,
                sprintf('Le solde "%s" est declare couvert mais GilsSupplyService ne le somme pas.', $column),
            );
        }
    }

    /**
     * Le plan de justification ne cite que des colonnes existantes.
     */
    public function testNoStaleJustification(): void
    {
        $columns = $this->gilsColumns();

        $this->assertSame(
            [],
            array_values(array_diff(array_keys(self::NOT_A_BALANCE), $columns)),
            'Ces colonnes justifiees n\'existent plus : la ligne morte autoriserait leur reintroduction sans examen.',
        );
    }
}
