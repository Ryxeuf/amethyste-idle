<?php

namespace App\Helper;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use App\GameEngine\Item\ItemEffectEncoder;
use App\GameEngine\Progression\DomainCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\ResetInterface;

class ItemHelper implements ResetInterface
{
    /**
     * @var array|Spell[]
     */
    private array $spells = [];

    /**
     * @var array|Skill[]
     */
    private array $skills = [];

    /**
     * Domaines ouverts par un objet, `null` compris — l'absence d'ouverture est
     * une reponse, et la memoriser evite de redecoder l'effet a chaque appel
     * d'`isUsable()` (l'inventaire l'appelle une fois par ligne).
     *
     * @var array<int, Domain|null>
     */
    private array $domains = [];

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly PlayerDomainHelper $playerDomainHelper, private readonly PlayerHelper $playerHelper, private readonly DomainCatalog $domainCatalog)
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

        $spell = $item->getSpell();
        if (!$spell && $item->getEffect()) {
            $effect = json_decode($item->getEffect(), true, 512, JSON_THROW_ON_ERROR);
            if (ItemEffectEncoder::ACTION_USE_SPELL === ($effect['action'] ?? false)) {
                $spell = $this->entityManager->getRepository(Spell::class)->findOneBy(['slug' => $effect['slug']]);
            }
        }

        if ($spell !== null) {
            $this->spells[$item->getId()] = $spell;
        }

        return $spell;
    }

    public function getItemSkillLearning(Item $item): ?Skill
    {
        if (isset($this->skills[$item->getId()])) {
            return $this->skills[$item->getId()];
        }
        if ($item->getEffect()) {
            $effect = json_decode($item->getEffect(), true, 512, JSON_THROW_ON_ERROR);
            if (ItemEffectEncoder::ACTION_LEARN_SKILL === ($effect['action'] ?? false)) {
                if ($skill = $this->entityManager->getRepository(Skill::class)->findOneBy(['slug' => $effect['slug']])) {
                    $this->skills[$item->getId()] = $skill;

                    return $skill;
                }
            }
        }

        return null;
    }

    /**
     * Le domaine qu'un parchemin ouvre, ou `null` si l'objet n'en ouvre aucun
     * (ONB-08).
     *
     * Un slug qui ne designe aucun domaine rend `null` plutot que de lever :
     * un parchemin oublie en base apres un renommage de domaine doit rester
     * inerte, pas rendre l'inventaire inutilisable.
     */
    public function getItemDomainOpening(Item $item): ?Domain
    {
        if (\array_key_exists($item->getId(), $this->domains)) {
            return $this->domains[$item->getId()];
        }

        $domain = null;
        if ($item->getEffect()) {
            $effect = json_decode($item->getEffect(), true, 512, JSON_THROW_ON_ERROR);
            if (ItemEffectEncoder::ACTION_OPEN_DOMAIN === ($effect['action'] ?? false)) {
                $domain = $this->domainCatalog->findBySlug((string) ($effect['slug'] ?? ''));
            }
        }

        return $this->domains[$item->getId()] = $domain;
    }

    public function isUsable(Item $item): bool
    {
        return $this->getItemSpell($item) !== null
            || $this->getItemSkillLearning($item) !== null
            || $this->getItemDomainOpening($item) !== null;
    }

    public function getItemBuildItem(Item $item): ?Item
    {
        return null;
    }

    public function getItemSpellModifiers(Item $item, ?Player $character = null): array
    {
        $modifiers = [
            'hit' => 0,
            'critical' => 0,
            'damage' => 0,
            'heal' => 0,
        ];

        if ($domain = $item->getDomain()) {
            $player = $character instanceof Player ? $character : $this->playerHelper->getPlayer();
            if ($domainExperience = $this->playerDomainHelper->getDomainExperience($domain, $player)) {
                $modifiers['hit'] += $domainExperience->getHit();
                $modifiers['critical'] += $domainExperience->getCritical();
                $modifiers['damage'] += $domainExperience->getDamage();
                $modifiers['heal'] += $domainExperience->getHeal();
            }
        }

        return $modifiers;
    }

    public function reset(): void
    {
        $this->spells = [];
        $this->skills = [];
        $this->domains = [];
    }
}
