<?php

namespace App\GameEngine\Progression;

use App\Entity\Game\Domain;
use App\Event\Fight\ItemUsedEvent;
use App\Event\Map\ButcheringEvent;
use App\Event\Map\FishingEvent;
use App\Event\Map\SpotHarvestEvent;
use App\Helper\PlayerDomainHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DomainExperienceEvolver implements EventSubscriberInterface
{
    public function __construct(private readonly PlayerDomainHelper $playerDomainHelper, private readonly EntityManagerInterface $entityManager)
    {
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
            $this->increaseDomainExperience($domain);
        }
    }

    public function experienceFromHarvesting(SpotHarvestEvent $event): void
    {
        $slug = $event->getObjectLayer()->getSlug();
        if ($domain = $this->playerDomainHelper->getDomainBySkillAction('harvest', ['spot' => $slug])) {
            $this->increaseDomainExperience($domain);
        }

    }

    public function experienceFromFishing(FishingEvent $event): void
    {
        if (!$event->isSuccess()) {
            return;
        }

        $slug = $event->getObjectLayer()->getSlug();
        if ($domain = $this->playerDomainHelper->getDomainBySkillAction('harvest', ['spot' => $slug])) {
            $this->increaseDomainExperience($domain);
        }
    }

    public function experienceFromButchering(ButcheringEvent $event): void
    {
        if (empty($event->getHarvestedItems())) {
            return;
        }

        // Chercher un domaine lié au butchering via les skills du joueur
        if ($domain = $this->playerDomainHelper->getDomainBySkillAction('butcher')) {
            $this->increaseDomainExperience($domain);
        }
    }

    private function increaseDomainExperience(Domain $domain, int $amount = 1): void
    {
        if ($domainExperience = $this->playerDomainHelper->getDomainExperience($domain)) {
            $newExperience = $domainExperience->getTotalExperience() + $amount;
            $domainExperience->setTotalExperience($newExperience);
            $this->entityManager->persist($domainExperience);
            $this->entityManager->flush();
        }
    }
}
