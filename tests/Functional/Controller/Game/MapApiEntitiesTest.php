<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Game;

use App\Entity\App\Player;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * AVT-22 — tests integration /api/map/entities.
 *
 * Verifie que le pipeline avatar (joueurs) cohabite avec le pipeline legacy
 * (mobs, PNJ) sans regression sur la forme du payload JSON.
 */
class MapApiEntitiesTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private Player $player;

    /** @var array<string, string>|null */
    private ?array $originalAppearance = null;
    private ?string $originalHash = null;
    private int $originalVersion = 0;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em = $em;

        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);
        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $this->client->loginUser($user);

        // Resolve the actual current player via the same API the controller uses (first player of the user).
        $firstPlayer = $user->getPlayers()->first() ?: null;
        if (!$firstPlayer instanceof Player) {
            $this->markTestSkipped('Fixture user remy has no attached player.');
        }

        $refreshed = $em->getRepository(Player::class)->find($firstPlayer->getId());
        if ($refreshed === null) {
            $this->markTestSkipped('Fixture player for remy not found after refresh.');
        }
        $this->player = $refreshed;

        $this->originalAppearance = $this->player->getAvatarAppearance();
        $this->originalHash = $this->player->getAvatarHash();
        $this->originalVersion = $this->player->getAvatarVersion();
    }

    protected function tearDown(): void
    {
        // Restore initial avatar state so tests stay isolated without transactions.
        if ($this->em->isOpen()) {
            $this->player->setAvatarAppearance($this->originalAppearance);
            $this->player->setAvatarHash($this->originalHash);
            $this->player->setAvatarVersion($this->originalVersion);
            $this->em->flush();
        }

        parent::tearDown();
    }

    public function testPlayerWithoutAvatarReturnsLegacyRenderMode(): void
    {
        $this->player->setAvatarAppearance(null);
        $this->em->flush();

        $data = $this->fetchEntities();

        $self = $this->findSelfPlayer($data['players']);
        $this->assertSame('legacy', $self['renderMode']);
        $this->assertSame('player_default', $self['spriteKey']);
        $this->assertArrayNotHasKey('avatarHash', $self);
        $this->assertArrayNotHasKey('avatar', $self);
    }

    public function testPlayerWithAvatarReturnsAvatarPayload(): void
    {
        $this->player->setAvatarAppearance([
            'body' => 'human_m_light',
            'hair' => 'short_01',
            'hairColor' => '#d6b25e',
        ]);
        $this->em->flush();

        $data = $this->fetchEntities();

        $self = $this->findSelfPlayer($data['players']);
        $this->assertSame('avatar', $self['renderMode']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $self['avatarHash']);

        $this->assertArrayHasKey('avatar', $self);
        $this->assertArrayHasKey('baseSheet', $self['avatar']);
        $this->assertArrayHasKey('layers', $self['avatar']);
        $this->assertSame('/assets/styles/images/avatar/body/human_m_light.png', $self['avatar']['baseSheet']);
        $this->assertNotEmpty($self['avatar']['layers']);

        // spriteKey reste present comme fallback legacy cote front.
        $this->assertArrayHasKey('spriteKey', $self);
    }

    public function testMobsUseLegacyPipelineWithoutAvatarFields(): void
    {
        $data = $this->fetchEntities();

        if (empty($data['mobs'])) {
            $this->markTestSkipped('Aucun mob dans le rayon par defaut — test non discriminant.');
        }

        foreach ($data['mobs'] as $mob) {
            $this->assertArrayHasKey('spriteKey', $mob, 'Un mob doit toujours avoir un spriteKey legacy.');
            $this->assertArrayNotHasKey('renderMode', $mob, 'Un mob ne doit pas avoir renderMode.');
            $this->assertArrayNotHasKey('avatarHash', $mob, 'Un mob ne doit pas avoir avatarHash.');
            $this->assertArrayNotHasKey('avatar', $mob, 'Un mob ne doit pas avoir le payload avatar.');
        }
    }

    public function testPnjsUseLegacyPipelineWithoutAvatarFields(): void
    {
        $data = $this->fetchEntities();

        if (empty($data['pnjs'])) {
            $this->markTestSkipped('Aucun PNJ dans le rayon par defaut — test non discriminant.');
        }

        foreach ($data['pnjs'] as $pnj) {
            $this->assertArrayHasKey('spriteKey', $pnj, 'Un PNJ doit toujours avoir un spriteKey legacy.');
            $this->assertArrayNotHasKey('renderMode', $pnj, 'Un PNJ ne doit pas avoir renderMode.');
            $this->assertArrayNotHasKey('avatarHash', $pnj, 'Un PNJ ne doit pas avoir avatarHash.');
            $this->assertArrayNotHasKey('avatar', $pnj, 'Un PNJ ne doit pas avoir le payload avatar.');
        }
    }

    /**
     * @return array{players: list<array<string, mixed>>, mobs: list<array<string, mixed>>, pnjs: list<array<string, mixed>>}
     */
    private function fetchEntities(): array
    {
        $this->client->request('GET', '/api/map/entities?radius=50');
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode(), '/api/map/entities doit renvoyer 200.');

        $content = (string) $response->getContent();
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('players', $data);
        $this->assertArrayHasKey('mobs', $data);
        $this->assertArrayHasKey('pnjs', $data);

        /* @var array{players: list<array<string, mixed>>, mobs: list<array<string, mixed>>, pnjs: list<array<string, mixed>>} $data */
        return $data;
    }

    /**
     * @param list<array<string, mixed>> $players
     *
     * @return array<string, mixed>
     */
    private function findSelfPlayer(array $players): array
    {
        foreach ($players as $player) {
            if (($player['self'] ?? false) === true) {
                return $player;
            }
        }

        $this->fail('Le joueur courant doit apparaitre dans la liste des entites avec self=true.');
    }
}
