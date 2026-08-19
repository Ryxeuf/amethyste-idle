<?php

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\DomainParchmentFixtures;
use App\Entity\Game\Domain;
use App\GameEngine\Item\ItemEffectEncoder;
use App\GameEngine\Progression\FoundTreeCatalog;
use PHPUnit\Framework\TestCase;

/**
 * Les quatre conditions non negociables du parchemin (ONB-08).
 *
 * GAME_ONBOARDING § 6.3 : *le parchemin est un cout, jamais un verrou*. Ce test
 * verrouille cette phrase la ou elle peut se perdre — dans les donnees.
 *
 * Il lit les **sources** de fixtures plutot que la base : la loi doit tenir des
 * la relecture du fichier, sans schema ni serveur, et un jalon futur qui
 * ajouterait un arbre sans son parchemin doit echouer immediatement.
 */
class DomainParchmentContractTest extends TestCase
{
    /**
     * Condition implicite mais premiere : **un arbre sans parchemin est un arbre
     * inatteignable**. C'est le defaut le plus facile a introduire — on ajoute
     * un metier, on oublie sa porte d'entree, et personne ne s'en apercoit
     * avant qu'un joueur ne cherche a l'ouvrir.
     */
    public function testEveryTreeHasExactlyOneParchment(): void
    {
        $covered = array_merge(
            array_keys($this->declaredParchments()),
            array_keys(DomainParchmentFixtures::LEGACY_PARCHMENTS),
            // DOM-10 : un arbre **retrouve** a lui aussi exactement un
            // parchemin — il vit simplement dans `found_trees.yaml`, parce
            // qu'aucune echoppe ne le vend. La loi ne change pas : elle
            // compte une porte d'entree par arbre, quelle qu'en soit la
            // provenance.
            (new FoundTreeCatalog(\dirname(__DIR__, 3)))->keys(),
        );

        $missing = array_values(array_diff($this->shippedDomainKeys(), $covered));
        self::assertSame([], $missing, sprintf(
            'Ces arbres n\'ont aucun parchemin, donc aucune porte d\'entree : %s.',
            implode(', ', $missing),
        ));

        $orphans = array_values(array_diff($covered, $this->shippedDomainKeys()));
        self::assertSame([], $orphans, sprintf(
            'Ces parchemins ouvrent un arbre qui n\'existe pas : %s.',
            implode(', ', $orphans),
        ));

        self::assertCount(\count($covered), array_unique($covered), 'Un arbre a deux parchemins : lequel fait foi ?');
    }

    /**
     * Condition 3 — aucun parchemin n'est unique ni limite : un PNJ le vend,
     * toujours, **a prix fixe**. Le prix uniforme est provisoire (le bareme
     * revient a PLAN_PLAYER_ECONOMY), mais son uniformite, elle, est la regle :
     * un parchemin plus cher qu'un autre ferait de l'entree dans un metier une
     * decision economique avant d'etre un choix de jeu.
     */
    public function testEveryParchmentSharesTheSameFixedPrice(): void
    {
        self::assertSame(100, DomainParchmentFixtures::PARCHMENT_PRICE);

        foreach (DomainParchmentFixtures::LEGACY_PARCHMENTS as $slug) {
            self::assertSame(
                DomainParchmentFixtures::PARCHMENT_PRICE,
                $this->legacyEntry($slug)['price'],
                sprintf('Le parchemin historique « %s » n\'est pas au prix commun.', $slug),
            );
        }
    }

    /**
     * Les trois parchemins historiques accordaient **une competence precise**
     * (`learn_skill`), ce qui faisait du parchemin un raccourci de progression.
     * Le geste joueur etait le bon, la semantique ne l'etait pas.
     */
    public function testLegacyParchmentsOpenATreeInsteadOfGrantingASkill(): void
    {
        $slugs = $this->shippedDomainSlugs();

        foreach (DomainParchmentFixtures::LEGACY_PARCHMENTS as $domainKey => $itemSlug) {
            $effect = json_decode($this->legacyEntry($itemSlug)['effect'], true, 512, JSON_THROW_ON_ERROR);

            self::assertSame(
                ItemEffectEncoder::ACTION_OPEN_DOMAIN,
                $effect['action'] ?? null,
                sprintf('« %s » n\'ouvre pas d\'arbre.', $itemSlug),
            );
            self::assertSame(
                $slugs[$domainKey] ?? null,
                $effect['slug'] ?? null,
                sprintf('« %s » vise un domaine qui n\'est pas %s.', $itemSlug, $domainKey),
            );
        }
    }

    /**
     * Condition 4 — aucun parchemin payant sur le chemin critique de l'acte I.
     *
     * Les trois parchemins que la chaine **donne** (ONB-12) sont exactement les
     * trois historiques : arme, matéria et recolte. Le jour ou l'un d'eux
     * quitterait cette liste, l'acte I demanderait 100 gils a un personnage qui
     * n'en a pas encore gagne un seul.
     */
    public function testTheThreeParchmentsOfActOneAreTheGivenOnes(): void
    {
        self::assertCount(3, DomainParchmentFixtures::LEGACY_PARCHMENTS);
        self::assertSame(
            ['healer', 'herbalist', 'miner'],
            $this->sorted(array_keys(DomainParchmentFixtures::LEGACY_PARCHMENTS)),
        );
    }

    /**
     * Condition 1 — accessible a tout le monde.
     *
     * Aucun parchemin ne declare de prerequis : ni competence, ni peuple, ni
     * faction. Le test relit la source plutot que l'entite, parce que c'est
     * dans la source qu'une exception se glisserait.
     */
    public function testNoParchmentDeclaresAnyPrerequisite(): void
    {
        $source = $this->read('src/DataFixtures/DomainParchmentFixtures.php');

        foreach (['setRequirements', 'addRequirement', 'setRace', 'setFaction'] as $forbidden) {
            self::assertStringNotContainsString(
                $forbidden . '(',
                $source,
                sprintf('Un parchemin appelle %s() : ce serait un verrou, pas un cout.', $forbidden),
            );
        }

        // Les trois historiques passent par le tableau de donnees d'ItemFixtures,
        // ou une condition s'ecrirait `'requirements' => [...]`.
        foreach (DomainParchmentFixtures::LEGACY_PARCHMENTS as $slug) {
            self::assertStringNotContainsString('requirements', $this->legacyBlock($slug));
        }
    }

    // =====================================================================
    // Lecture des sources
    // =====================================================================

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    private function declaredParchments(): array
    {
        $property = new \ReflectionClassConstant(DomainParchmentFixtures::class, 'PARCHMENTS');

        /** @var array<string, array{0: string, 1: string}> $value */
        $value = $property->getValue();

        return $value;
    }

    /**
     * @return list<string> les cles de domaine livrees par DomainFixtures
     */
    private function shippedDomainKeys(): array
    {
        return array_keys($this->shippedDomainSlugs());
    }

    /**
     * Cle de domaine => slug derive, exactement comme l'entite le derive.
     *
     * @return array<string, string>
     */
    private function shippedDomainSlugs(): array
    {
        preg_match_all(
            "/'([a-z]+)' => \['title' => '([^']+)'/",
            $this->read('src/DataFixtures/DomainFixtures.php'),
            $matches,
            PREG_SET_ORDER,
        );
        self::assertNotEmpty($matches, 'Aucun domaine trouve : la loi ne verifierait rien.');

        $slugs = [];
        foreach ($matches as [, $key, $title]) {
            $domain = new Domain();
            $domain->setTitle($title);
            $slugs[$key] = $domain->getSlug();
        }

        return $slugs;
    }

    /**
     * L'entree d'`ItemFixtures` d'un parchemin historique, decodee de sa source.
     *
     * @return array{price: int, effect: string}
     */
    private function legacyEntry(string $itemSlug): array
    {
        $block = $this->legacyBlock($itemSlug);

        self::assertSame(1, preg_match("/'effect' => '([^']+)'/", $block, $effect));
        self::assertSame(1, preg_match("/'price' => (\d+)/", $block, $price));

        return ['price' => (int) $price[1], 'effect' => $effect[1]];
    }

    /**
     * Le fragment de source qui va du slug du parchemin a la fin de son entree.
     */
    private function legacyBlock(string $itemSlug): string
    {
        $found = preg_match(
            sprintf("/'slug' => '%s',(.*?)'nb_usages'/s", preg_quote($itemSlug, '/')),
            $this->read('src/DataFixtures/ItemFixtures.php'),
            $match,
        );
        self::assertSame(1, $found, sprintf('Le parchemin « %s » a disparu d\'ItemFixtures.', $itemSlug));

        return $match[1];
    }

    private function read(string $relativePath): string
    {
        return (string) file_get_contents(\dirname(__DIR__, 3) . '/' . $relativePath);
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }
}
