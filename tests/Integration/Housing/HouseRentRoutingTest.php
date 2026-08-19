<?php

namespace App\Tests\Integration\Housing;

use App\Entity\App\Guild;
use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\GameEngine\Housing\HouseRentRouting;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Le loyer politique (FOY-19).
 *
 * GAME_WORLD § 12.6 c : *« dans une zone a foyer, le loyer part au tresor de la
 * guilde controlante de la region ; sans guilde controlante, il reste un
 * sink »*.
 */
class HouseRentRoutingTest extends AbstractIntegrationTestCase
{
    private HouseRentRouting $routing;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var HouseRentRouting $routing */
        $routing = self::getContainer()->get(HouseRentRouting::class);
        $this->routing = $routing;
    }

    /**
     * **Une zone sans foyer n'a pas de percepteur.**.
     *
     * Le Quartier des Jardins en est le cas — bati sur la Voute, rien ne s'y
     * depose —, mais la regle porte sur l'**absence de foyer** et non sur son
     * nom : le jour ou une seconde zone residentielle naîtra hors foyer, elle
     * sera traitee sans qu'on revienne au code.
     *
     * C'est aussi ce qui rend le plancher du logement **inconditionnel** :
     * personne ne peut le fermer, parce que personne n'en tire rien.
     */
    public function testAZoneWithoutASettlementHasNoCollector(): void
    {
        $jardins = $this->zone('quartier-des-jardins');
        self::assertNull(
            $this->em->getRepository(Settlement::class)->findOneBy(['zone' => $jardins]),
            'Le Quartier a un foyer : la premisse du test a change.',
        );

        self::assertNull($this->routing->beneficiaryOf($this->houseIn($jardins)));
    }

    /**
     * **Un foyer que personne ne gouverne ne percoit pas non plus.**.
     *
     * Le loyer reste un sink tant qu'aucune guilde ne tient la region : le
     * revenu politique se merite, il ne tombe pas avec le rang.
     */
    public function testASettlementWithoutARulingGuildStillSinks(): void
    {
        $foret = $this->zone('foret-des-murmures');
        self::assertNotNull(
            $this->em->getRepository(Settlement::class)->findOneBy(['zone' => $foret]),
            'La Foret n\'a pas de foyer : le test ne mesure rien.',
        );

        self::assertNull($this->routing->beneficiaryOf($this->houseIn($foret)));
    }

    /**
     * **Le sink brule, il ne rend pas.**.
     *
     * Meme regle qu'a l'hotel des ventes, a l'echoppe et a l'Autel : les gils
     * ont ete retires au joueur et ne vont a personne. Les lui rendre en ferait
     * une remise deguisee — l'inverse d'un gold sink. Le test le verifie par ce
     * qui **ne bouge pas** : aucun tresor du monde ne monte.
     */
    public function testAnUnroutedRentEnrichesNobody(): void
    {
        $before = $this->treasuries();

        $this->routing->route($this->houseIn($this->zone('quartier-des-jardins')), PlayerHouse::RENT_AMOUNT);

        self::assertSame($before, $this->treasuries(), 'Un loyer sans percepteur a enrichi quelqu\'un.');
    }

    /**
     * Un versement se **souvient d'ou il vient**.
     *
     * Le trait politique du loyer ne vaut que s'il se voit : un tresor qui
     * monte sans dire d'ou ne dit rien de la ville qu'on tient.
     */
    public function testAPaidRentRemembersItCameFromInhabitants(): void
    {
        $guild = new Guild();
        $guild->setName('Les Percepteurs');

        $guild->addRentToTreasury(120);
        $guild->addGilsTreasury(500);

        self::assertSame(620, $guild->getGilsTreasury(), 'Le tresor compte les deux versements.');
        self::assertSame(120, $guild->getGilsFromRents(), 'Seuls les loyers comptent comme loyers.');
    }

    /**
     * **La boucle entiere**, celle qu'aucun des cas ci-dessus ne prouve.
     *
     * Un habitant paie, et le tresor de la guilde qui tient la region monte de
     * ce qu'il a paye — en se souvenant que c'etait un loyer. C'est le jalon ;
     * le reste en sont les cas.
     *
     * Le paiement passe par `HousingManager`, et non par le routeur : router
     * correctement sans jamais appeler le routeur passerait tous les tests
     * precedents.
     */
    public function testPayingRentInAGovernedSettlementFillsItsTreasury(): void
    {
        $zone = $this->zone('foret-des-murmures');
        $region = self::getContainer()->get(\App\GameEngine\Region\PlayerRegionResolver::class)->resolveForZone($zone);
        self::assertNotNull($region, 'La Foret n\'a pas de region : le test ne mesure rien.');

        $player = $this->em->getRepository(Player::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($player);

        $guild = new Guild();
        $guild->setName('Les Percepteurs de la Foret');
        $guild->setTag('PDF');
        $guild->setLeader($player);
        $this->em->persist($guild);

        $season = $this->em->getRepository(\App\Entity\App\InfluenceSeason::class)->findOneBy([], ['id' => 'DESC']);
        self::assertNotNull($season, 'Aucune saison d\'influence : le controle de region ne peut pas exister.');

        $control = new \App\Entity\App\RegionControl();
        $control->setRegion($region);
        $control->setGuild($guild);
        $control->setSeason($season);
        $control->setStartedAt(new \DateTime());
        $control->setEndsAt(null);
        $this->em->persist($control);

        $this->em->flush();

        /** @var \App\GameEngine\Housing\HousingManager $manager */
        $manager = self::getContainer()->get(\App\GameEngine\Housing\HousingManager::class);

        // La demeure passe par le vrai chemin d'acquisition : construire une
        // entite a la main obligerait ce test a connaître chaque colonne
        // obligatoire, et il vieillirait a la premiere qu'on ajoute.
        $player->setGils(1_000_000);
        $house = $manager->buyLand($player, $zone, 'Le Logis des essais');

        $player->setGils(PlayerHouse::RENT_AMOUNT * 3);
        $gilsBefore = $player->getGils();

        $manager->payRent($player, $house);

        self::assertSame($gilsBefore - PlayerHouse::RENT_AMOUNT, $player->getGils(), 'Le loyer n\'a pas ete preleve.');
        self::assertSame(PlayerHouse::RENT_AMOUNT, $guild->getGilsTreasury(), 'Le tresor n\'a pas recu le loyer.');
        self::assertSame(PlayerHouse::RENT_AMOUNT, $guild->getGilsFromRents(), 'Le tresor a recu le loyer sans savoir que c\'en etait un.');
    }

    private function zone(string $slug): Zone
    {
        $zone = $this->em->getRepository(Zone::class)->findOneBy(['slug' => $slug]);
        self::assertNotNull($zone);

        return $zone;
    }

    private function houseIn(Zone $zone): PlayerHouse
    {
        $player = $this->em->getRepository(Player::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($player);

        return (new PlayerHouse())->setOwner($player)->setZone($zone);
    }

    /**
     * @return array<int, int>
     */
    private function treasuries(): array
    {
        $treasuries = [];
        foreach ($this->em->getRepository(Guild::class)->findAll() as $guild) {
            $treasuries[(int) $guild->getId()] = $guild->getGilsTreasury();
        }

        return $treasuries;
    }
}
