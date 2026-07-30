<?php

namespace App\GameEngine\Item;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\CharacterInterface;
use App\Event\Fight\ItemUsedEvent;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Progression\DomainAccessManager;
use App\GameEngine\Progression\SkillAcquiring;
use App\Helper\ItemHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ItemUsageResolver implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly EventDispatcherInterface $eventDispatcher, private readonly SpellApplicator $spellApplicator, private readonly SkillAcquiring $skillAcquiring, private readonly ItemHitResolver $itemHitResolver, private readonly ItemHelper $itemHelper, private readonly DomainAccessManager $domainAccessManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ItemUsedEvent::NAME => 'removeExpiredUsableItem',
        ];
    }

    public function resolve(PlayerItem $playerItem, CharacterInterface $sender, CharacterInterface $target): bool
    {
        $item = $playerItem->getGenericItem();
        $hit = $this->itemHitResolver->hasItemHit($item, $sender, $target);
        if (!$hit) {
            return false;
        }

        if ($spell = $this->itemHelper->getItemSpell($item)) {
            $modifiers = $this->itemHelper->getItemSpellModifiers($item, $sender instanceof Player ? $sender : null);
            $this->spellApplicator->apply($spell, $sender, $target, $modifiers);
        } elseif ($skill = $this->itemHelper->getItemSkillLearning($item)) {
            $this->skillAcquiring->acquireSkill($skill);
        } elseif ($sender instanceof Player && $domain = $this->itemHelper->getItemDomainOpening($item)) {
            // ONB-08 — un parchemin lu en combat ouvre l'arbre comme ailleurs.
            // Seul un joueur a des arbres : un monstre qui utiliserait l'objet
            // ne doit rien ouvrir, et surtout pas lever.
            $this->domainAccessManager->open($sender, $domain);
        } elseif ($item = $this->itemHelper->getItemBuildItem($item)) {
            // Craft
        }

        $this->eventDispatcher->dispatch(new ItemUsedEvent($playerItem), ItemUsedEvent::NAME);

        return true;
    }

    public function removeExpiredUsableItem(ItemUsedEvent $event): void
    {
        $item = $event->getItem();
        if ($item->getNbUsages() < 0) {
            return;
        }

        $item->setNbUsages($item->getNbUsages() - 1);
        if ($item->getNbUsages() <= 0) {
            $this->entityManager->remove($item);
            $this->entityManager->flush();
        }
    }
}
