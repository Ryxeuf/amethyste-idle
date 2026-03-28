<?php

namespace App\EventListener;

use App\Enum\GuildQuestType;
use App\Event\CraftEvent;
use App\Event\Fight\MobDeadEvent;
use App\Event\Map\SpotHarvestEvent;
use App\GameEngine\Guild\GuildQuestManager;
use App\Helper\PlayerHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Progresse les quetes de guilde lors de kills, recoltes et crafts.
 */
class GuildQuestListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly GuildQuestManager $guildQuestManager,
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'onMobDead',
            SpotHarvestEvent::NAME => 'onSpotHarvest',
            CraftEvent::NAME => 'onCraft',
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

        $monsterSlug = $mob->getMonster()->getSlug();

        foreach ($fight->getPlayers() as $player) {
            if ($player->isDead()) {
                continue;
            }

            $guild = $this->guildQuestManager->getPlayerGuild($player);
            if ($guild === null) {
                continue;
            }

            $this->guildQuestManager->trackProgress($guild, GuildQuestType::Kill, $monsterSlug);
        }
    }

    public function onSpotHarvest(SpotHarvestEvent $event): void
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return;
        }

        $guild = $this->guildQuestManager->getPlayerGuild($player);
        if ($guild === null) {
            return;
        }

        foreach ($event->getHarvestedItems() as $playerItem) {
            $slug = $playerItem->getGenericItem()->getSlug();
            $this->guildQuestManager->trackProgress($guild, GuildQuestType::Collect, $slug);
        }
    }

    public function onCraft(CraftEvent $event): void
    {
        $player = $event->getPlayer();

        $guild = $this->guildQuestManager->getPlayerGuild($player);
        if ($guild === null) {
            return;
        }

        $this->guildQuestManager->trackProgress(
            $guild,
            GuildQuestType::Craft,
            $event->getRecipe()->getSlug(),
            $event->getQuantity(),
        );
    }
}
