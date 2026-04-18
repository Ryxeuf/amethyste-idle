<?php

namespace App\Service;

use App\Entity\App\Inventory;
use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\Game\Race;
use App\Entity\User;
use App\Enum\TutorialStep;
use App\Service\Avatar\AvatarHashRecalculator;
use Doctrine\ORM\EntityManagerInterface;

class PlayerFactory
{
    private const BASE_LIFE = 20;
    private const BASE_ENERGY = 80;
    private const BASE_MAX_ENERGY = 100;
    private const BASE_SPEED = 10;
    private const BASE_HIT = 50;
    private const SPAWN_COORDINATES = '20.20';
    private const DEFAULT_BODY = 'human_m_light';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AvatarHashRecalculator $avatarHashRecalculator,
    ) {
    }

    /**
     * @param array{body?: string|null, hair?: string|null, hairColor?: string|null}|null $appearance
     */
    public function createPlayer(User $user, string $name, Race $race, ?array $appearance = null): Player
    {
        $player = new Player();
        $player->setUser($user);
        $player->setName($name);
        $player->setRace($race);
        $player->setClassType('player');
        $player->setAvatarAppearance($this->normalizeAppearance($appearance));

        $modifiers = $race->getStatModifiers();
        $lifeMod = (int) ($modifiers['life'] ?? 0);
        $energyMod = (int) ($modifiers['energy'] ?? 0);
        $speedMod = (int) ($modifiers['speed'] ?? 0);
        $hitMod = (int) ($modifiers['hit'] ?? 0);

        $maxLife = self::BASE_LIFE + $lifeMod;
        $maxEnergy = self::BASE_MAX_ENERGY + $energyMod;

        $player->setLife($maxLife);
        $player->setMaxLife($maxLife);
        $player->setEnergy(self::BASE_ENERGY + $energyMod);
        $player->setMaxEnergy($maxEnergy);
        $player->setSpeed(self::BASE_SPEED + $speedMod);
        $player->setHit(self::BASE_HIT + $hitMod);
        $player->setGils(0);
        $player->setTutorialStep(TutorialStep::Movement->value);

        $spawnMap = $this->getSpawnMap();
        $player->setMap($spawnMap);
        $player->setCoordinates(self::SPAWN_COORDINATES);
        $player->setLastCoordinates(self::SPAWN_COORDINATES);

        $this->entityManager->persist($player);

        $this->createInventories($player);

        $this->entityManager->flush();

        $this->avatarHashRecalculator->recalculate($player);

        return $player;
    }

    /**
     * @param array{body?: string|null, hair?: string|null, hairColor?: string|null}|null $appearance
     *
     * @return array<string, string>
     */
    private function normalizeAppearance(?array $appearance): array
    {
        $body = isset($appearance['body']) && $appearance['body'] !== '' ? $appearance['body'] : self::DEFAULT_BODY;

        $normalized = ['body' => $body];

        if (isset($appearance['hair']) && $appearance['hair'] !== '' && $appearance['hair'] !== null) {
            $normalized['hair'] = $appearance['hair'];
        }

        if (isset($appearance['hairColor']) && $appearance['hairColor'] !== '' && $appearance['hairColor'] !== null) {
            $normalized['hairColor'] = $appearance['hairColor'];
        }

        return $normalized;
    }

    private function createInventories(Player $player): void
    {
        $types = [
            Inventory::TYPE_BAG => 100,
            Inventory::TYPE_MATERIA => 50,
            Inventory::TYPE_BANK => 1000,
        ];

        foreach ($types as $type => $size) {
            $inventory = new Inventory();
            $inventory->setType($type);
            $inventory->setSize($size);
            $inventory->setPlayer($player);
            $player->addInventory($inventory);
            $this->entityManager->persist($inventory);
        }
    }

    private function getSpawnMap(): Map
    {
        /** @var \Doctrine\ORM\EntityRepository<Map> $mapRepository */
        $mapRepository = $this->entityManager->getRepository(Map::class);

        $village = $mapRepository->findOneBy(['name' => 'Village de Lumière']);
        if ($village instanceof Map) {
            return $village;
        }

        $firstMap = $mapRepository->findOneBy([]);
        if ($firstMap instanceof Map) {
            return $firstMap;
        }

        throw new \RuntimeException('Aucune carte disponible pour le spawn du joueur.');
    }
}
