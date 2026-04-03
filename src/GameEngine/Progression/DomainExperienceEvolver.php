<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Map;
use App\Entity\Game\Domain;
use App\Event\Fight\ItemUsedEvent;
use App\Event\Game\DomainLevelUpEvent;
use App\Event\Map\ButcheringEvent;
use App\Event\Map\FishingEvent;
use App\Event\Map\SpotHarvestEvent;
use App\GameEngine\Event\GameEventBonusProvider;
use App\Helper\PlayerDomainHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DomainExperienceEvolver implements EventSubscriberInterface
{
    public function __construct(
        private readonly PlayerDomainHelper $playerDomainHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly GameEventBonusProvider $gameEventBonusProvider,
        private readonly PlayerHelper $playerHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ItemUsedEvent::NAME => 'experienceFromItemUsed',
            SpotHarvestEvent::NAME => 'experienceFromHarvesting',
            FishingEvent::NAME => 'experienceFromFishing',
            ButcheringEvent::NAME => 'experienceFromButchering',
        ];
    }

    public function experienceFromItemUsed(ItemUsedEvent $event): void
    {
        if (!$event->isSuccess()) {
            return;
        }

        if ($domain = $event->getItem()->getGenericItem()->getDomain()) {
            $map = $this->playerHelper->getPlayer()?->getMap();
            $this->increaseDomainExperience($domain, 1, $map);
        }
    }

    public function experienceFromHarvesting(SpotHarvestEvent $event): void
    {
        $slug = $event->getObjectLayer()->getSlug();
        if ($domain = $this->playerDomainHelper->getDomainBySkillAction('harvest', ['spot' => $slug])) {
            $map = $this->playerHelper->getPlayer()?->getMap();
            $this->increaseDomainExperience($domain, 1, $map);
        }
    }

    public function experienceFromFishing(FishingEvent $event): void
    {
        if (!$event->isSuccess()) {
            return;
        }

        $slug = $event->getObjectLayer()->getSlug();
        if ($domain = $this->playerDomainHelper->getDomainBySkillAction('harvest', ['spot' => $slug])) {
            $map = $event->getPlayer()->getMap();
            $this->increaseDomainExperience($domain, 1, $map);
        }
    }

    public function experienceFromButchering(ButcheringEvent $event): void
    {
        if (empty($event->getHarvestedItems())) {
            return;
        }

        // Chercher un domaine lié au butchering via les skills du joueur
        if ($domain = $this->playerDomainHelper->getDomainBySkillAction('butcher')) {
            $map = $event->getPlayer()->getMap();
            $this->increaseDomainExperience($domain, 1, $map);
        }
    }

    private function increaseDomainExperience(Domain $domain, int $amount = 1, ?Map $map = null): void
    {
        $xpMultiplier = $this->gameEventBonusProvider->getXpMultiplier($map);
        $finalAmount = (int) round($amount * $xpMultiplier);

        if ($finalAmount < 1) {
            $finalAmount = 1;
        }

        if ($domainExperience = $this->playerDomainHelper->getDomainExperience($domain)) {
            $oldLevel = $domainExperience->getLevel();
            $newExperience = $domainExperience->getTotalExperience() + $finalAmount;
            $domainExperience->setTotalExperience($newExperience);
            $this->entityManager->persist($domainExperience);
            $this->entityManager->flush();

            $newLevel = $domainExperience->getLevel();
            if ($newLevel > $oldLevel) {
                $player = $domainExperience->getPlayer();
                $this->eventDispatcher->dispatch(
                    new DomainLevelUpEvent($player, $domain, $oldLevel, $newLevel),
                    DomainLevelUpEvent::NAME
                );
            }
        }
    }
}
