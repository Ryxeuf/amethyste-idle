<?php

namespace App\GameEngine\Progression;

use App\Entity\Game\Domain;
use App\Event\Fight\ItemUsedEvent;
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
            ItemUsedEvent::NAME => "experienceFromItemUsed",
            SpotHarvestEvent::NAME => "experienceFromHarvesting",
        ];
    }

    public function experienceFromItemUsed(ItemUsedEvent $event): void
    {
        if (!$event->isSuccess()){
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

    private function increaseDomainExperience(Domain $domain): void
    {
        if ($domainExperience = $this->playerDomainHelper->getDomainExperience($domain)) {
            $newExperience = $domainExperience->getTotalExperience()+1;
            $domainExperience->setTotalExperience($newExperience);
            $this->entityManager->persist($domainExperience);
            $this->entityManager->flush();
        }
    }

}
