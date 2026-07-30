<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * La population du Fanal (ONB-16).
 *
 * Ferme la dette **D5**. Deux fixtures peuplaient le hub sans se connaitre :
 * `PnjFixtures` (les soixante habitants historiques, dont les quatre porteurs
 * de l'arc `intro` qu'ONB-15 y a installes) et `VillageHubPnjFixtures` (les sept
 * figures a dialogue). Resultat : **deux forgerons**, et **aucun maitre
 * d'armes** — alors que la chaine de l'acte I commence chez lui.
 *
 * Les lois verifiees ici tiennent aux **donnees**, pas au rendu : un doublon de
 * role ne casse rien, il se lit simplement comme un monde mal tenu, et personne
 * ne le signale.
 */
class FanalPopulationTest extends TestCase
{
    /**
     * Le maitre d'armes existe.
     *
     * L'etape 1 de l'acte I (GAME_ONBOARDING § 5.2) commence chez lui. Sans ce
     * personnage, la chaine n'a pas de premiere porte.
     */
    public function testTheWeaponMasterExists(): void
    {
        self::assertStringContainsString(
            "'slug' => 'fanal-maitresse-armes-ysold'",
            $this->villageHub(),
            'Le Fanal n\'a pas de maitre d\'armes : la chaine de l\'acte I n\'a pas de premiere porte.',
        );
    }

    /**
     * Un seul forgeron au Fanal, et c'est le doublon exact que D5 nomme.
     *
     * Gerard reste — les quetes le designent par sa reference de fixture
     * (`pnj_0`), et l'on ne debranche pas un donneur.
     */
    public function testNoRoleIsHeldTwiceAtTheBeacon(): void
    {
        self::assertSame(
            0,
            preg_match_all("/'name' => 'Aldric le Forgeron'/", $this->villageHub()),
            'Le Fanal compte deux forgerons. Un role tenu deux fois ne casse rien — il se lit comme un monde mal tenu.',
        );

        // Le role se lit dans le **slug**, pas dans `classType` : ce dernier
        // designe une classe de sprite, et deux metiers distincts peuvent
        // legitimement la partager — la maitresse des quetes et le banquier sont
        // tous deux `noble`. Le slug, lui, nomme le poste.
        $trades = [];
        foreach ($this->residentSlugs() as $slug) {
            $parts = explode('-', $slug);
            array_shift($parts);   // « fanal »
            array_pop($parts);     // le prenom
            $trades[] = implode('-', $parts);
        }

        $duplicates = array_keys(array_filter(array_count_values($trades), static fn (int $n): bool => $n > 1));

        self::assertSame([], $duplicates, sprintf(
            'Ces metiers sont tenus deux fois au Fanal : %s.',
            implode(', ', $duplicates),
        ));
    }

    /**
     * @return list<string>
     */
    private function residentSlugs(): array
    {
        preg_match_all("/^                'slug' => '([a-z-]+)'/m", $this->villageHub(), $matches);
        self::assertNotEmpty($matches[1], 'Aucun slug trouve : la loi ne verifierait rien.');

        return $matches[1];
    }

    /**
     * « Aldric » cesse d'etre porte par deux personnages.
     *
     * **Aldric l'Ancien** est l'ermite de la Crete, donneur de quete de
     * l'acte 2. Deux Aldric a trois zones d'ecart, dont un seul compte pour une
     * quete, est le genre de collision qu'on ne decouvre qu'en jouant — et
     * qu'on met longtemps a croire.
     */
    public function testOnlyOneAldricRemainsInTheWorld(): void
    {
        $bearers = 0;
        foreach (['PnjFixtures', 'VillageHubPnjFixtures'] as $fixture) {
            $source = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/' . $fixture . '.php');
            $bearers += preg_match_all("/'name' => 'Aldric/", $source);
        }

        self::assertSame(0, $bearers, 'Le Fanal porte encore un Aldric, alors que l\'ermite de la Crete en est un.');
    }

    /**
     * Chaque habitant declare du Fanal porte un slug.
     *
     * Convention ZON-26b-b : sans slug, la seule facon de retrouver un PNJ est
     * son **nom affiche** — ce qui interdit de le renommer, et fait dependre le
     * code d'un texte de fiction. ZON-39, qui reecrit precisement les libelles,
     * arrive juste apres.
     */
    public function testEveryDeclaredResidentCarriesASlug(): void
    {
        $source = $this->villageHub();

        $names = preg_match_all("/^                'name' => /m", $source);
        $slugs = preg_match_all("/^                'slug' => /m", $source);

        self::assertGreaterThan(0, $names, 'Aucun habitant trouve : la loi ne verifierait rien.');
        self::assertSame($names, $slugs, 'Un habitant du Fanal n\'a pas de slug : on ne pourra plus le renommer.');
    }

    /**
     * Chaque parchemin de l'acte I a un vendeur identifie.
     *
     * C'est la moitie d'ONB-08 qui restait ouverte : les 36 parchemins
     * existaient, aucun n'avait de marchand. Les trois metiers de l'acte I sont
     * couverts au Fanal — l'arme chez la maitresse d'armes, l'alchimie chez
     * Iris, la forge et l'herboristerie chez leurs porteurs d'arc.
     */
    public function testEveryActOneParchmentHasAVendor(): void
    {
        $shelves = $this->villageHub() . $this->historicPnjs();

        $expected = [
            // L'arme : l'etape 1 fait choisir, il faut donc du choix en rayon.
            'soldier-domain-parchment',
            'berserker-domain-parchment',
            'archer-domain-parchment',
            'assassin-domain-parchment',
            'knight-domain-parchment',
            'paladin-domain-parchment',
            // Les metiers du Fanal vendent le parchemin de leur arbre.
            'alchimist-domain-parchment',
            'blacksmith-domain-parchment',
            'herbalist-domain-parchment',
        ];

        $orphans = [];
        foreach ($expected as $parchment) {
            if (!str_contains($shelves, $parchment)) {
                $orphans[] = $parchment;
            }
        }

        self::assertSame([], $orphans, sprintf(
            "Ces parchemins n'ont aucun vendeur au Fanal : %s.\nUn parchemin sans marchand est un arbre qu'on ne peut pas ouvrir.",
            implode(', ', $orphans),
        ));
    }

    /**
     * Les parchemins vendus designent des arbres qui existent.
     *
     * Un slug fautif produit un rayon vide, jamais une erreur : le joueur
     * conclut que le marchand n'a rien, et personne ne cherche plus loin.
     */
    public function testEverySoldParchmentPointsAtARealTree(): void
    {
        $shelves = $this->villageHub() . $this->historicPnjs();
        $declared = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/DomainParchmentFixtures.php');

        preg_match_all("/'([a-z-]+)-domain-parchment'/", $shelves, $matches);
        self::assertNotEmpty($matches[1], 'Aucun parchemin en rayon : la loi ne verifierait rien.');

        $unknown = [];
        foreach (array_unique($matches[1]) as $key) {
            // Les trois parchemins historiques vivent dans `ItemFixtures`.
            if (\in_array($key, ['life', 'miner', 'herbalist'], true)) {
                continue;
            }
            if (!str_contains($declared, sprintf("'%s' => [", str_replace('-', '_', $key)))) {
                $unknown[] = $key;
            }
        }

        self::assertSame([], $unknown, sprintf('Ces parchemins en rayon n\'ouvrent aucun arbre livre : %s.', implode(', ', $unknown)));
    }

    private function villageHub(): string
    {
        return (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/VillageHubPnjFixtures.php');
    }

    private function historicPnjs(): string
    {
        return (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/PnjFixtures.php');
    }
}
