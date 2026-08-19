<?php

namespace App\Tests\Integration\Housing;

use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\GameEngine\Housing\Homecoming;
use App\GameEngine\Housing\HousingManager;
use App\GameEngine\Housing\ResidenceGrain;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Le retour au logis et les cheminees (FOY-20).
 */
class HomecomingAndHearthTest extends AbstractIntegrationTestCase
{
    private Homecoming $homecoming;
    private HousingManager $housing;
    private ResidenceGrain $hearths;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Homecoming $homecoming */
        $homecoming = self::getContainer()->get(Homecoming::class);
        $this->homecoming = $homecoming;

        /** @var HousingManager $housing */
        $housing = self::getContainer()->get(HousingManager::class);
        $this->housing = $housing;

        /** @var ResidenceGrain $hearths */
        $hearths = self::getContainer()->get(ResidenceGrain::class);
        $this->hearths = $hearths;
    }

    /**
     * **Sans logis, pas de retour.** La commodite vient avec la demeure — c'est
     * ce qui la distingue d'un teleporteur.
     */
    public function testThereIsNoHomecomingWithoutAHome(): void
    {
        self::assertSame(
            'game.house.homecoming.error.no_home',
            $this->homecoming->refusalFor($this->player(), new \DateTimeImmutable()),
        );
    }

    /**
     * **Une fois par jour**, et la cible est unique : on rentre chez soi, jamais
     * ailleurs. La route elle-meme ne prend aucune destination — *la borne est
     * dans la forme, pas dans un reglage*.
     */
    public function testComingHomeIsInstantOncePerDayAndOnlyHome(): void
    {
        [$player, $house] = $this->settled('foret-des-murmures');

        $elsewhere = $this->zone('marais-brumeux');
        $player->setCurrentZone($elsewhere);
        $this->em->flush();

        $now = new \DateTimeImmutable('2026-09-01 18:00:00');

        self::assertNull($this->homecoming->refusalFor($player, $now));
        self::assertSame($house->getZone()->getId(), $this->homecoming->comeHome($player, $now)->getId());
        self::assertSame($house->getZone()->getId(), $player->getCurrentZone()?->getId(), 'Le voyage est instantane : on est deja arrive.');
        self::assertFalse($player->isTraveling());

        // Le second retour du meme jour est refuse — et il l'est **avec une
        // raison**, un bouton grise sans explication se lisant comme une panne.
        $player->setCurrentZone($elsewhere);
        self::assertSame('game.house.homecoming.error.already_used', $this->homecoming->refusalFor($player, $now));

        // Le lendemain, sans qu'aucune tache n'ait remis quoi que ce soit a
        // zero : une cle differente est un autre jour.
        self::assertNull($this->homecoming->refusalFor($player, $now->modify('+1 day')));
    }

    /**
     * On ne rentre pas d'un combat, ni au milieu d'un voyage : la commodite
     * raccourcit un trajet, **elle n'annule pas un etat**.
     */
    public function testHomecomingNeverCancelsAState(): void
    {
        [$player] = $this->settled('foret-des-murmures');
        $player->setCurrentZone($this->zone('marais-brumeux'));

        $player->setTravelToZone($this->zone('mines-profondes'));
        $player->setTravelStartedAt(new \DateTimeImmutable());
        $player->setTravelArrivesAt(new \DateTimeImmutable('+10 minutes'));

        self::assertSame('game.house.homecoming.error.traveling', $this->homecoming->refusalFor($player, new \DateTimeImmutable()));
    }

    /**
     * **Le coffre naît avec la demeure**, et il est plus petit que la banque :
     * ce n'est pas un second entrepot, c'est ce qu'on laisse chez soi.
     */
    public function testTheChestComesWithTheHome(): void
    {
        $player = $this->player();
        self::assertNull($this->housing->houseChest($player), 'Un coffre sans logis.');

        [$settled] = $this->settled('foret-des-murmures');

        $chest = $this->housing->houseChest($settled);
        self::assertNotNull($chest);
        self::assertGreaterThan(0, $chest->getSize());
        self::assertLessThan(1000, $chest->getSize(), 'Le coffre domestique n\'est pas une seconde banque.');
    }

    /**
     * **La cheminee ne fume que si le loyer est a jour**, et une seule fois par
     * jour.
     *
     * Sans la condition de loyer, on entretiendrait une ville avec des logis
     * vides : *la population residente soutient la ville*, pas les murs.
     */
    public function testAHearthBurnsOnceADayAndOnlyWhenTheRentIsPaid(): void
    {
        [, $house] = $this->settled('foret-des-murmures');

        // La date se derive de la demeure et non d'une constante : la premiere
        // periode est offerte a l'achat, donc l'echeance depend du jour ou le
        // test tourne. L'ecrire en dur ferait passer ce test aujourd'hui et
        // echouer la semaine prochaine.
        $now = $house->getRentDueAt()->modify('-1 day');
        self::assertTrue($house->isRentUpToDate($now), 'La premiere periode est offerte a l\'achat.');

        $first = $this->hearths->burnHearths($now);
        self::assertGreaterThanOrEqual(1, $first['burned']);

        // Relance le meme jour : rien de plus. La commande est idempotente,
        // parce que le calendrier ne rejoue rien mais qu'une relance a la main
        // ne doit pas deposer deux fois.
        $second = $this->hearths->burnHearths($now);
        self::assertSame(0, $second['burned']);

        // Loyer en arriere : la cheminee s'eteint.
        $late = $house->getRentDueAt()->modify('+1 day');
        self::assertFalse($house->isRentUpToDate($late));
        self::assertSame(0, $this->hearths->burnHearths($late)['burned']);
    }

    /**
     * Le grain de residence est une **ligne de la table de sediment**, pas un
     * chemin a part : c'est ce qui garantit qu'il obeit aux memes regles que les
     * autres gestes — multiplicateurs de doctrine compris.
     */
    public function testTheGrainIsADeclaredSedimentAction(): void
    {
        $rules = self::getContainer()->get(\App\GameEngine\Settlement\SettlementDefinitionLoader::class)->load()['sediment'];

        self::assertArrayHasKey(ResidenceGrain::ACTION, $rules);
        self::assertFalse(
            $rules[ResidenceGrain::ACTION]->capped,
            'La cheminee est plafonnee : le grain disparaîtrait chez les joueurs les plus actifs, c\'est-a-dire chez ceux qui font vivre la ville.',
        );
    }

    /**
     * Le premier joueur **qui n'est pas en combat**.
     *
     * Prendre le premier venu ferait dependre le test de ce qu'une autre suite
     * a laisse derriere elle : le refus « en combat » est une regle qu'on
     * verifie, pas un etat qu'on subit.
     */
    private function player(): Player
    {
        foreach ($this->em->getRepository(Player::class)->findBy([], ['id' => 'ASC']) as $player) {
            if ($player->getFight() === null) {
                return $player;
            }
        }

        self::fail('Aucun joueur au repos : le test ne mesure rien.');
    }

    private function zone(string $slug): Zone
    {
        $zone = $this->em->getRepository(Zone::class)->findOneBy(['slug' => $slug]);
        self::assertNotNull($zone);

        return $zone;
    }

    /**
     * @return array{0: Player, 1: PlayerHouse}
     */
    private function settled(string $slug): array
    {
        $zone = $this->zone($slug);
        self::assertNotNull(
            $this->em->getRepository(Settlement::class)->findOneBy(['zone' => $zone]),
            sprintf('« %s » n\'a pas de foyer : le test ne mesure rien.', $slug),
        );

        $player = $this->player();
        $player->setCurrentZone($zone);
        $player->setGils(1_000_000);

        $house = $this->housing->buyLand($player, $zone, 'Le Logis des essais');

        return [$player, $house];
    }
}
