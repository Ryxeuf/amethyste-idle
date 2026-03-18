<?php

namespace App\Helper;

use App\Entity\App\PlayerItem;
use App\Entity\CharacterInterface;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use App\GameEngine\Item\ItemEffectEncoder;
use Doctrine\ORM\EntityManagerInterface;

class ItemHelper
{
    /**
     * @var array|Spell[]
     */
    private array $spells = [];

    /**
     * @var array|Skill[]
     */
    private array $skills = [];

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly PlayerDomainHelper $playerDomainHelper, private readonly PlayerHelper $playerHelper)
    {
    }

    public function getItem(int $id): ?Item
    {
        return $this->entityManager->getRepository(Item::class)->find($id);
    }

    public function getPlayerItem(int $id): ?PlayerItem
    {
        return $this->entityManager->getRepository(PlayerItem::class)->find($id);
    }

    public function getItemSpell(Item $item): ?Spell
    {
        if (!$item->getEffect() && !$item->getSpell()) {
            return null;
        }
        if (isset($this->spells[$item->getId()])) {
            return $this->spells[$item->getId()];
        }

        if ($spell = $item->getSpell()) {
            $this->spells[$item->getId()] = $spell;
        } elseif ($item->getEffect()) {
            $effect = json_decode($item->getEffect(), true, 512, JSON_THROW_ON_ERROR);
            if (ItemEffectEncoder::ACTION_USE_SPELL === $effect['action'] ?? false) {
                if ($spell = $this->entityManager->getRepository(Spell::class)->findOneBy(['slug' => $effect['slug']])) {
                    $this->spells[$item->getId()] = $spell;
                }
            }
        }

        return $this->spells[$item->getId()] ?? null;
    }

    public function getItemSkillLearning(Item $item): ?Skill
    {
        $skill = null;
        if (isset($this->skills[$item->getId()])) {
            return $this->skills[$item->getId()];
        }
        if ($item->getEffect()) {
            $effect = json_decode($item->getEffect(), true, 512, JSON_THROW_ON_ERROR);
            if (ItemEffectEncoder::ACTION_LEARN_SKILL === $effect['action'] ?? false) {
                if ($skill = $this->entityManager->getRepository(Skill::class)->findOneBy(['slug' => $effect['slug']])) {
                    $this->skills[$item->getId()] = $skill;
                }
            }
        }

        return $this->skills[$item->getId()] ?? null;
    }

    public function isUsable(Item $item): bool
    {
        return $this->getItemSpell($item) !== null || $this->getItemSkillLearning($item) !== null;
    }

    public function getItemBuildItem(Item $item): ?Item
    {
        return null;
    }

    public function getItemSpellModifiers(Item $item, ?CharacterInterface $character = null): array
    {
        $modifiers = [
            'hit' => 0,
            'critical' => 0,
            'damage' => 0,
            'heal' => 0,
        ];

        if ($domain = $item->getDomain()) {
            $player = $character ?? $this->playerHelper->getPlayer();
            if ($domainExperience = $this->playerDomainHelper->getDomainExperience($domain, $player)) {
                $modifiers['hit'] += $domainExperience->getHit();
                $modifiers['critical'] += $domainExperience->getCritical();
                $modifiers['damage'] += $domainExperience->getDamage();
                $modifiers['heal'] += $domainExperience->getHeal();
            }
        }

        return $modifiers;
    }
}
