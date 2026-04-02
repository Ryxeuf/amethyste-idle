<?php

namespace App\Service;

use App\Entity\App\Inventory;
use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\Game\Race;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PlayerFactory
{
    private const BASE_LIFE = 20;
    private const BASE_ENERGY = 80;
    private const BASE_MAX_ENERGY = 100;
    private const BASE_SPEED = 10;
    private const BASE_HIT = 50;
    private const SPAWN_COORDINATES = '20.20';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createPlayer(User $user, string $name, Race $race): Player
    {
        $player = new Player();
        $player->setUser($user);
        $player->setName($name);
        $player->setRace($race);
        $player->setClassType('player');

        $modifiers = $race->getStatModifiers();
        $maxLife = self::BASE_LIFE + ($modifiers['life'] ?? 0);
        $maxEnergy = self::BASE_MAX_ENERGY + ($modifiers['energy'] ?? 0);

        $player->setLife($maxLife);
        $player->setMaxLife($maxLife);
        $player->setEnergy(self::BASE_ENERGY + ($modifiers['energy'] ?? 0));
        $player->setMaxEnergy($maxEnergy);
        $player->setSpeed(self::BASE_SPEED + ($modifiers['speed'] ?? 0));
        $player->setHit(self::BASE_HIT + ($modifiers['hit'] ?? 0));
        $player->setGils(0);

        $spawnMap = $this->getSpawnMap();
        $player->setMap($spawnMap);
        $player->setCoordinates(self::SPAWN_COORDINATES);
        $player->setLastCoordinates(self::SPAWN_COORDINATES);

        $this->entityManager->persist($player);

        $this->createInventories($player);

        $this->entityManager->flush();

        return $player;
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
        $mapRepository = $this->entityManager->getRepository(Map::class);

        $village = $mapRepository->findOneBy(['name' => 'Village de Lumière']);
        if ($village !== null) {
            return $village;
        }

        $firstMap = $mapRepository->findOneBy([]);
        if ($firstMap !== null) {
            return $firstMap;
        }

        throw new \RuntimeException('Aucune carte disponible pour le spawn du joueur.');
    }
}
