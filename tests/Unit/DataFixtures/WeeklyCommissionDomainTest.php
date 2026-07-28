<?php

namespace App\Tests\Unit\DataFixtures;

use App\Entity\Game\Domain;
use App\GameEngine\Retention\WeeklyCommissionTemplateLoader;
use PHPUnit\Framework\TestCase;

/**
 * Loi transverse : chaque commission se rattache a un domaine qui existe
 * vraiment (RET-02).
 *
 * Le couplage surveille ici est **fragile par construction**, et c'est pour cela
 * qu'il merite une loi. `Domain::getSlug()` ne lit pas une colonne : il derive
 * l'identifiant du **titre affiche**, en minuscules, espaces remplacés par des
 * tirets, **accents conserves**. Le slug de « Pêcheur » est donc `pêcheur`.
 *
 * Deux consequences, toutes deux muettes :
 *
 * 1. Renommer ou retraduire un domaine change son slug **sans que rien ne le
 *    signale**. Les commissions rattachees a l'ancien nom cessent alors d'etre
 *    eligibles, et le tirage se rabat silencieusement sur le pool entier — le
 *    joueur recoit des objectifs hors de ce qu'il travaille, ce qui ressemble a
 *    du hasard plutot qu'a une regression.
 * 2. Une faute d'accent dans le YAML produit exactement le meme effet, sans
 *    erreur ni journal.
 *
 * Le test compare donc la colonne `domain` du pool aux titres reellement
 * livres par `DomainFixtures`, en rejouant la meme derivation.
 */
class WeeklyCommissionDomainTest extends TestCase
{
    /**
     * Slugs des domaines livres, derives exactement comme le fait l'entite.
     *
     * @return list<string>
     */
    private function shippedDomainSlugs(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/DomainFixtures.php');

        preg_match_all("/'[a-z]+' => \['title' => '([^']+)'/", $source, $matches);
        self::assertNotEmpty($matches[1], 'Aucun domaine trouve dans DomainFixtures : la loi ne verifierait rien.');

        $slugs = [];
        foreach ($matches[1] as $title) {
            $domain = new Domain();
            $domain->setTitle($title);
            $slugs[] = $domain->getSlug();
        }

        return $slugs;
    }

    /**
     * @return list<string>
     */
    private function commissionDomains(): array
    {
        $pool = (new WeeklyCommissionTemplateLoader(\dirname(__DIR__, 3)))->load()['commissions'];
        self::assertNotEmpty($pool);

        return array_values(array_unique(array_map(
            static fn (object $template): string => $template->domain,
            $pool,
        )));
    }

    public function testEveryCommissionPointsAtARealDomain(): void
    {
        $unknown = array_values(array_diff($this->commissionDomains(), $this->shippedDomainSlugs()));

        self::assertSame([], $unknown, sprintf(
            "Ces commissions se rattachent a un domaine inexistant : %s.\n"
            . 'Rappel : le slug derive du titre affiche, accents compris (« Pêcheur » donne `pêcheur`).',
            implode(', ', $unknown),
        ));
    }

    /**
     * Le pool doit couvrir les deux moities du jeu. Un pool entierement tourne
     * vers le combat ferait, pour un artisan, un rendez-vous hebdomadaire qui ne
     * parle jamais de son travail.
     */
    public function testThePoolSpansMoreThanOneKindOfPlay(): void
    {
        $activities = array_map(
            static fn (object $template): string => $template->activity->value,
            (new WeeklyCommissionTemplateLoader(\dirname(__DIR__, 3)))->load()['commissions'],
        );

        self::assertGreaterThanOrEqual(4, \count(array_unique($activities)));
        self::assertContains('craft', $activities);
        self::assertContains('mob_kill', $activities);
    }
}
