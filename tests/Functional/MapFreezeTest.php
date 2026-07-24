<?php

namespace App\Tests\Functional;

use App\Entity\App\FeatureFlag;
use App\Entity\User;
use App\GameEngine\Zone\MapFreeze;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Gel de la carte (pivot PBBG, ZON-01) : quand le flag `map_frozen` est actif,
 * /game/map redirige vers /game/zone et les endpoints de deplacement refusent.
 */
class MapFreezeTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager = $em;

        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);
        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $this->client->loginUser($user);
    }

    protected function tearDown(): void
    {
        $this->setFlagEnabled(false);
        parent::tearDown();
    }

    private function setFlagEnabled(bool $enabled): void
    {
        $flag = $this->entityManager->getRepository(FeatureFlag::class)->findOneBy(['slug' => MapFreeze::FLAG]);
        if (null === $flag) {
            $flag = new FeatureFlag();
            $flag->setSlug(MapFreeze::FLAG);
            $flag->setName('Gel de la carte (pivot PBBG)');
            $flag->setCreatedAt(new \DateTime());
            $flag->setUpdatedAt(new \DateTime());
            $this->entityManager->persist($flag);
        }
        $flag->setEnabled($enabled);
        $this->entityManager->flush();
    }

    public function testMapPageDoesNotRedirectToZoneWhenNotFrozen(): void
    {
        $this->setFlagEnabled(false);

        $this->client->request('GET', '/game/map');
        $response = $this->client->getResponse();

        $this->assertLessThan(500, $response->getStatusCode());
        // D'autres redirections metier restent legitimes (combat en cours...),
        // mais jamais vers l'ecran de zone tant que la carte n'est pas gelee.
        $this->assertNotSame('/game/zone', $response->headers->get('Location'));
    }

    public function testMapPageRedirectsToZoneWhenFrozen(): void
    {
        $this->setFlagEnabled(true);

        $this->client->request('GET', '/game/map');

        $this->assertResponseRedirects('/game/zone');
    }

    public function testMoveApiRefusesWhenFrozen(): void
    {
        $this->setFlagEnabled(true);

        $this->client->request('POST', '/api/map/move', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['targetX' => 1, 'targetY' => 1]));

        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testTeleportApiRefusesWhenFrozen(): void
    {
        $this->setFlagEnabled(true);

        $this->client->request('POST', '/api/map/teleport', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['mapId' => 1, 'coordinates' => '1.1']));

        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }
}
