<?php

namespace App\EventListener;

use App\Entity\App\Player;
use App\Entity\App\PlayerJournalEntry;
use App\Event\CraftEvent;
use App\Event\Fight\MobDeadEvent;
use App\Event\Fight\PlayerDeadEvent;
use App\Event\Game\DungeonCompletedEvent;
use App\Event\Game\QuestCompletedEvent;
use App\Event\Map\SpotHarvestEvent;
use App\Helper\PlayerHelper;
use App\Repository\PlayerJournalEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JournalListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerJournalEntryRepository $journalRepository,
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => ['onMobDead', -20],
            PlayerDeadEvent::NAME => ['onPlayerDead', -20],
            QuestCompletedEvent::NAME => ['onQuestCompleted', -20],
            CraftEvent::NAME => ['onCraft', -20],
            SpotHarvestEvent::NAME => ['onSpotHarvest', -20],
            DungeonCompletedEvent::NAME => ['onDungeonCompleted', -20],
        ];
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        $mob = $event->getMob();

        if ($mob->isSummoned()) {
            return;
        }

        $fight = $mob->getFight();
        if ($fight === null) {
            return;
        }

        foreach ($fight->getPlayers() as $player) {
            if ($player->isDead()) {
                continue;
            }

            $this->addEntry(
                $player,
                PlayerJournalEntry::TYPE_COMBAT_VICTORY,
                \sprintf('Victoire contre %s', $mob->getName()),
                ['monster' => $mob->getMonster()->getSlug()]
            );
        }
    }

    public function onPlayerDead(PlayerDeadEvent $event): void
    {
        $player = $event->getPlayer();

        $this->addEntry(
            $player,
            PlayerJournalEntry::TYPE_COMBAT_DEFEAT,
            'Defaite au combat'
        );
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $this->addEntry(
            $event->getPlayer(),
            PlayerJournalEntry::TYPE_QUEST_COMPLETED,
            \sprintf('Quete terminee : %s', $event->getQuest()->getName()),
            ['quest' => $event->getQuest()->getName()]
        );
    }

    public function onCraft(CraftEvent $event): void
    {
        $qty = $event->getQuantity();
        $itemName = $event->getResultItem()->getName();

        $this->addEntry(
            $event->getPlayer(),
            PlayerJournalEntry::TYPE_CRAFT,
            \sprintf('Fabrication : %s%s', $itemName, $qty > 1 ? \sprintf(' x%d', $qty) : ''),
            ['item' => $event->getResultItem()->getSlug(), 'quantity' => $qty]
        );
    }

    public function onSpotHarvest(SpotHarvestEvent $event): void
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return;
        }

        $items = $event->getHarvestedItems();
        if (empty($items)) {
            return;
        }

        $names = [];
        foreach ($items as $playerItem) {
            $names[] = $playerItem->getGenericItem()->getName();
        }

        $this->addEntry(
            $player,
            PlayerJournalEntry::TYPE_GATHERING,
            \sprintf('Recolte : %s', implode(', ', $names)),
            ['spot' => $event->getObjectLayer()->getSlug()]
        );
    }

    public function onDungeonCompleted(DungeonCompletedEvent $event): void
    {
        $dungeonRun = $event->getDungeonRun();

        $this->addEntry(
            $event->getPlayer(),
            PlayerJournalEntry::TYPE_DUNGEON,
            \sprintf('Donjon termine : %s', $dungeonRun->getDungeon()->getName()),
            ['dungeon' => $dungeonRun->getDungeon()->getSlug()]
        );
    }

    private function addEntry(Player $player, string $type, string $message, ?array $metadata = null): void
    {
        $entry = new PlayerJournalEntry();
        $entry->setPlayer($player);
        $entry->setType($type);
        $entry->setMessage($message);
        $entry->setMetadata($metadata);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        $this->journalRepository->enforceEntryLimit($player);
    }
}
