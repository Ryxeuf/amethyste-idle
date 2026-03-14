<?php

namespace App\Controller\Game\Fight;

use App\Entity\App\Fight;
use App\Entity\CharacterInterface;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Fight\ElementalSynergyCalculator;
use App\GameEngine\Fight\FightCalculator;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Fight\StatusEffectManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight/spell', name: 'app_game_fight_spell')]
class FightSpellController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatSkillResolver $combatSkillResolver,
        private readonly SpellApplicator $spellApplicator,
        private readonly ElementalSynergyCalculator $synergyCalculator,
        private readonly StatusEffectManager $statusEffectManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (!$player || !$player->getFight()) {
            return new JsonResponse(['error' => 'No active fight'], Response::HTTP_NOT_FOUND);
        }

        $fight = $player->getFight();
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['spellSlug']) || !isset($data['targetId']) || !isset($data['targetType'])) {
            return new JsonResponse(['error' => 'Invalid request data'], Response::HTTP_BAD_REQUEST);
        }

        // Check paralysis/freeze
        if ($this->statusEffectManager->isCharacterParalyzed($fight, $player)
            || $this->statusEffectManager->isCharacterFrozen($fight, $player)) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas agir !', 'success' => false]);
        }

        // Check silence
        if ($this->statusEffectManager->isCharacterSilenced($fight, $player)) {
            return new JsonResponse(['error' => 'Vous êtes réduit au silence !', 'success' => false]);
        }

        // Verify player has this spell unlocked
        $spellSlug = $data['spellSlug'];
        if (!$this->combatSkillResolver->hasSkillWithSpell($player, $spellSlug)) {
            return new JsonResponse(['error' => 'Sort non débloqué'], Response::HTTP_FORBIDDEN);
        }

        // Find the spell
        $unlockedSpells = $this->combatSkillResolver->getUnlockedSpells($player);
        $spell = null;
        foreach ($unlockedSpells as $s) {
            if ($s->getSlug() === $spellSlug) {
                $spell = $s;
                break;
            }
        }

        if (!$spell) {
            return new JsonResponse(['error' => 'Sort introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Check cooldown
        $entityKey = 'player_' . $player->getId();
        if ($fight->isSpellOnCooldown($entityKey, $spellSlug)) {
            $remaining = $fight->getSpellCooldown($entityKey, $spellSlug);

            return new JsonResponse(['error' => "Sort en recharge ($remaining tours restants)", 'success' => false]);
        }

        // Check energy
        if (!$this->combatSkillResolver->consumeEnergy($player, $spell)) {
            return new JsonResponse(['error' => 'Énergie insuffisante', 'success' => false]);
        }

        // Find target
        $target = $this->findTarget($fight, (int) $data['targetId'], $data['targetType']);
        if (!$target) {
            return new JsonResponse(['error' => 'Cible introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Process status effects at start of turn
        $statusMessages = $this->statusEffectManager->processStartOfTurn($fight, $player);

        // Calculate combat bonuses from skills
        $bonuses = $this->combatSkillResolver->getCombatBonuses($player);

        // Check elemental synergy
        $synergyData = null;
        $lastElement = $fight->getLastElementUsed();
        if ($lastElement && $spell->getElement() !== 'none') {
            $synergyData = $this->synergyCalculator->checkSynergy($lastElement, $spell->getElement());
        }

        // Apply spell with bonuses
        $options = [
            'damage' => $bonuses['damage'],
            'heal' => $bonuses['heal'],
            'critical' => $bonuses['critical'],
            'fight' => $fight,
        ];

        // Apply synergy damage multiplier
        if ($synergyData) {
            $bonuses['damage'] = $this->synergyCalculator->applySynergyDamage($bonuses['damage'], $synergyData);
            $options['damage'] = $bonuses['damage'];

            // Self damage from Eclipse synergy
            $selfDamage = $this->synergyCalculator->getSelfDamage($player->getMaxLife(), $synergyData);
            if ($selfDamage > 0) {
                $player->setLife(max(0, $player->getLife() - $selfDamage));
                $this->entityManager->persist($player);
            }
        }

        // Apply the spell
        $hit = FightCalculator::hasAttackHit($spell->getHit() + $bonuses['hit']);
        $messages = $statusMessages;

        if ($hit) {
            $this->spellApplicator->apply($spell, $player, $target, $options);
            $messages[] = sprintf('%s lance %s !', $player->getName(), $spell->getName());
            if ($synergyData) {
                $messages[] = sprintf('Synergie %s activée !', $synergyData['label']);
            }
        } else {
            $messages[] = sprintf('%s a raté !', $spell->getName());
        }

        // Set cooldown
        if ($spell->getCooldown() && $spell->getCooldown() > 0) {
            $fight->setSpellCooldown($entityKey, $spellSlug, $spell->getCooldown());
        }

        // Track element for synergies
        $fight->setLastElementUsed($spell->getElement());

        // Decrement cooldowns and advance step
        $fight->decrementAllCooldowns();
        $fight->setStep($fight->getStep() + 1);

        // Regen energy (small amount per turn)
        $energyRegen = max(1, (int) ($player->getMaxEnergy() * 0.05));
        $player->setEnergy(min($player->getMaxEnergy(), $player->getEnergy() + $energyRegen));

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'hit' => $hit,
            'messages' => $messages,
            'synergy' => $synergyData ? $synergyData['label'] : null,
            'fight' => [
                'step' => $fight->getStep(),
                'terminated' => $fight->isTerminated(),
                'victory' => $fight->isVictory(),
            ],
        ]);
    }

    private function findTarget(Fight $fight, int $targetId, string $targetType): ?CharacterInterface
    {
        if ($targetType === 'mob') {
            foreach ($fight->getMobs() as $mob) {
                if ($mob->getId() === $targetId) {
                    return $mob;
                }
            }
        } elseif ($targetType === 'player') {
            foreach ($fight->getPlayers() as $player) {
                if ($player->getId() === $targetId) {
                    return $player;
                }
            }
        }

        return null;
    }
}
