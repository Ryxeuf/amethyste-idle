<?php

namespace App\Tests\Integration\Season;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\Settlement;
use App\Enum\SettlementIndex;
use App\GameEngine\Season\RotationTideSelector;
use App\GameEngine\Season\TideDefinitionLoader;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Le monde prescrit ce qui lui manque (NAR-15).
 *
 * GAME_SEASONS § 2 : la rotation joue le gabarit dont l'indice de sédiment
 * **mondial** est le plus faible. C'est l'indice décroissant d'EVE appliqué au
 * calendrier — un serveur de guerriers verra venir des foires, un serveur de
 * marchands des battues.
 */
class RotationTideSelectorTest extends AbstractIntegrationTestCase
{
    private RotationTideSelector $selector;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var RotationTideSelector $selector */
        $selector = self::getContainer()->get(RotationTideSelector::class);
        $this->selector = $selector;

        // On part d'un monde à plat : chaque test dit lui-même ce qui manque.
        foreach ($this->em->getRepository(Settlement::class)->findAll() as $settlement) {
            foreach (SettlementIndex::cases() as $index) {
                $settlement->setSediment($index, 100);
            }
        }
        $this->em->flush();
    }

    private function starve(SettlementIndex $index): void
    {
        foreach ($this->em->getRepository(Settlement::class)->findAll() as $settlement) {
            $settlement->setSediment($index, 0);
        }
        $this->em->flush();
    }

    private function themeOf(string $key): string
    {
        return (new TideDefinitionLoader(\dirname(__DIR__, 3)))->load()['rotation'][$key]['theme'];
    }

    /**
     * **Un serveur de marchands voit venir une battue.**.
     *
     * `war` au plus bas, et le seul gabarit qui le nourrit passe — sans qu'aucun
     * dé n'ait été lancé.
     */
    public function testTheWorldAsksForWhatItLacks(): void
    {
        $this->starve(SettlementIndex::War);

        self::assertSame('the_great_hunt', $this->selector->select());
    }

    /**
     * **Un gabarit à deux indices répond dès que l'un des deux manque.**.
     *
     * La Fonte nourrit `lore` **et** `rite` : son score est le minimum des deux,
     * ce qui est exactement ce que « nourrit les deux » veut dire. Ici `rite`
     * manque, et elle passe devant Le Chœur par l'ancienneté.
     */
    public function testATemplateWithTwoIndicesAnswersForEither(): void
    {
        $this->starve(SettlementIndex::Rite);

        self::assertContains($this->selector->select(), ['the_thaw', 'the_choir']);
    }

    /**
     * **À manque égal, on joue ce qu'on a le moins vu.**.
     *
     * Deux gabarits nourrissent `trade`. Rien n'est tiré au sort — *le monde ne
     * joue pas aux dés avec sa propre partition* : le départage est la dernière
     * fois qu'on les a joués, si bien que la variété vient de l'histoire du
     * serveur.
     */
    public function testAtEqualNeedTheLeastRecentlyPlayedWins(): void
    {
        $this->starve(SettlementIndex::Trade);

        $first = $this->selector->select();
        self::assertContains($first, ['the_forgery', 'the_free_fair']);

        // On inscrit le gagnant dans l'histoire du serveur : il ne doit plus
        // repasser tant que son voisin n'a pas été joué.
        $played = new InfluenceSeason();
        $played->setName('Marée jouée');
        $played->setSlug('maree-jouee');
        $played->setSeasonNumber(99);
        $played->setStartsAt(new \DateTime('-2 months'));
        $played->setEndsAt(new \DateTime('-1 month'));
        $played->setTheme($this->themeOf((string) $first));
        $this->em->persist($played);
        $this->em->flush();

        $second = $this->selector->select();
        self::assertNotSame($first, $second, 'Le même gabarit repasse : le départage ne lit plus l\'histoire du serveur.');
        self::assertContains($second, ['the_forgery', 'the_free_fair']);
    }

    /**
     * **Le tirage est déterministe** : deux lectures du même monde rendent le
     * même gabarit. Sans cela, ni un joueur ne pourrait déduire de son activité
     * ce qui l'attend, ni nous ne pourrions le tester.
     */
    public function testTheDrawIsDeterministic(): void
    {
        $this->starve(SettlementIndex::Lore);

        self::assertSame($this->selector->select(), $this->selector->select());
    }

    /**
     * L'indice se lit sur **tous** les foyers, jamais sur un seul.
     *
     * Lire le foyer dominant ferait dépendre la partition de l'humeur d'une
     * ville : un serveur avec une grande cité marchande et dix hameaux guerriers
     * verrait des foires jusqu'à la fin de l'année.
     */
    public function testTheIndexIsSummedOverTheWholeWorld(): void
    {
        $settlements = $this->em->getRepository(Settlement::class)->findAll();
        self::assertNotEmpty($settlements, 'Aucun foyer : le test ne mesure rien.');

        $expected = \count($settlements) * 100;
        self::assertSame($expected, $this->selector->worldSediment()[SettlementIndex::War->value]);
    }
}
