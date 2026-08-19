<?php

namespace App\Tests\Integration\Repertoire;

use App\Entity\App\Player;
use App\Entity\App\RepertoireGesture;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\Enum\SettlementType;
use App\GameEngine\Repertoire\AwakeningAltar;
use App\GameEngine\Repertoire\AwakeningException;
use App\GameEngine\Repertoire\RepertoireCatalog;
use App\Repository\SettlementRepository;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Le rite d'eveil (REP-04).
 */
class AwakeningAltarTest extends AbstractIntegrationTestCase
{
    private AwakeningAltar $altar;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var AwakeningAltar $altar */
        $altar = self::getContainer()->get(AwakeningAltar::class);
        $this->altar = $altar;
    }

    /**
     * **La porte d'abord.** Aucun foyer du monde livre n'atteint la Metropole,
     * donc l'Autel est ferme partout — et c'est l'etat normal : le rite est a
     * l'horizon de l'an.
     */
    public function testTheAltarIsClosedBelowMetropolis(): void
    {
        $this->expectException(AwakeningException::class);
        $this->expectExceptionMessage('game.repertoire.altar.error.closed');

        $this->altar->start(
            $this->player(),
            $this->zone('village-de-lumiere'),
            $this->anyMateria(),
            new \DateTimeImmutable(),
        );
    }

    /**
     * **L'Autel ne connaît que ce que le monde a retrouve.**.
     *
     * C'est le debouche des gestes de REP-03 : si la liste de base contenait
     * deja tout, retrouver un geste n'elargirait rien, et le debouche collectif
     * de « lire » serait vide.
     */
    public function testNothingIsAwakenableBeforeAGestureIsRecovered(): void
    {
        self::assertSame([], $this->altar->awakenableBy($this->player()));
    }

    /**
     * **Deux conditions se croisent** : le monde a retrouve le geste, et le
     * personnage en possede l'accord. Ni l'une ni l'autre ne suffit.
     */
    public function testARecoveredGestureStillNeedsThePlayersAccord(): void
    {
        $this->em->persist(new RepertoireGesture('frappe-meteorique', 1));
        $this->em->flush();

        $player = $this->player();

        // Le joueur de fixture n'a pas appris l'accord de la Frappe meteorique.
        self::assertSame([], $this->altar->awakenableBy($player));
    }

    /**
     * **Le Sanctuaire allege le rite, et rien d'autre.**.
     *
     * Il retire au cout **et** au delai, dans la meme proportion. Ce qu'il ne
     * change jamais, c'est *ce que* l'on peut eveiller : le type d'un foyer
     * n'ouvre pas de contenu, il rend le meme moins cher.
     */
    public function testASanctuaryDiscountsBothPriceAndDelay(): void
    {
        $catalog = self::getContainer()->get(RepertoireCatalog::class);
        $altar = $catalog->altar();

        $plain = $this->altar->costAt($this->zone('foret-des-murmures'));

        $settlement = self::getContainer()->get(SettlementRepository::class)
            ->findOneByZone($this->zone('foret-des-murmures'));
        self::assertNotNull($settlement, 'La Foret n\'a pas de foyer : le test ne mesure rien.');

        $settlement->setType(SettlementType::Sanctuary);
        $this->em->flush();

        $discounted = $this->altar->costAt($this->zone('foret-des-murmures'));

        self::assertTrue($discounted['sanctuary']);
        self::assertLessThan($plain['gils'], $discounted['gils']);
        self::assertLessThan($plain['seconds'], $discounted['seconds']);

        // La remise est la meme des deux cotes : un rite deux fois moins cher
        // mais aussi long serait un autre reglage, pas une remise.
        $keep = 100 - $altar['sanctuary_discount_percent'];
        self::assertSame(intdiv($altar['gils'] * $keep, 100), $discounted['gils']);
        self::assertSame(intdiv($altar['duration_hours'] * 3600 * $keep, 100), $discounted['seconds']);

        // Et le type ne change rien a ce qu'on peut eveiller.
        self::assertSame([], $this->altar->awakenableBy($this->player()));
    }

    /**
     * Sans rite en cours, il n'y a rien a recueillir — et le refus le dit
     * plutot que de rendre un inventaire silencieusement inchange.
     */
    public function testThereIsNothingToClaimWithoutARite(): void
    {
        $this->expectException(AwakeningException::class);
        $this->expectExceptionMessage('game.repertoire.altar.error.nothing_to_claim');

        $this->altar->claim($this->player(), new \DateTimeImmutable());
    }

    /**
     * **Seul le Parfait eveille** (ECO-22) : c'est la seule bande dont la
     * valeur ne soit pas qu'un prix, et c'est ici qu'elle la trouve. Le joueur
     * de fixture n'en porte aucun.
     */
    public function testOnlyPerfectLotsCount(): void
    {
        self::assertSame([], $this->altar->perfectLots($this->player()));
    }

    private function player(): Player
    {
        $player = $this->em->getRepository(Player::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($player);

        return $player;
    }

    private function zone(string $slug): Zone
    {
        $zone = $this->em->getRepository(Zone::class)->findOneBy(['slug' => $slug]);
        self::assertNotNull($zone);

        return $zone;
    }

    private function anyMateria(): Item
    {
        $materia = $this->em->getRepository(Item::class)->findOneBy(['slug' => 'm4-meteor-strike']);
        self::assertNotNull($materia);

        return $materia;
    }
}
