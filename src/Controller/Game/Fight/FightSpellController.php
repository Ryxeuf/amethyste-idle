<?php

namespace App\Controller\Game\Fight;

use App\Entity\App\Fight;
use App\Entity\CharacterInterface;
use App\Enum\Element;
use App\GameEngine\Fight\CombatCapacityResolver;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Fight\ElementalSynergyCalculator;
use App\GameEngine\Fight\FightCalculator;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\LinkedMateriaResolver;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Fight\StatusEffectManager;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/fight/spell', name: 'app_game_fight_spell', methods: ['POST'])]
class FightSpellController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatSkillResolver $combatSkillResolver,
        private readonly CombatCapacityResolver $combatCapacityResolver,
        private readonly SpellApplicator $spellApplicator,
        private readonly ElementalSynergyCalculator $synergyCalculator,
        private readonly StatusEffectManager $statusEffectManager,
        private readonly MobActionHandler $mobActionHandler,
        private readonly CombatLogger $combatLogger,
        private readonly FightTurnResolver $turnResolver,
        private readonly GearHelper $gearHelper,
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

        // Priorite de vitesse : le mob agit en premier s'il est plus rapide
        $mobFirst = $this->turnResolver->isMobFirst($fight);
        $mobResult = ['messages' => [], 'dangerAlert' => null];

        if ($mobFirst && !$fight->isTerminated()) {
            $mobResult = $this->mobActionHandler->doAction($fight);
            $fight->setStep($fight->getStep() + 1);
        }

        // Si le joueur est mort apres l'action du mob, fin du combat
        if ($player->isDead()) {
            $this->combatLogger->logDefeat($fight);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'hit' => false,
                'messages' => $mobResult['messages'],
                'dangerAlert' => $mobResult['dangerAlert'],
                'synergy' => null,
                'fight' => [
                    'step' => $fight->getStep(),
                    'terminated' => true,
                    'victory' => false,
                ],
            ]);
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

        // Verify player has this spell via equipped materia
        $spellSlug = $data['spellSlug'];
        $materiaEntry = $this->combatCapacityResolver->findMateriaSpell($player, $spellSlug);
        if (!$materiaEntry) {
            return new JsonResponse(['error' => 'Sort non disponible (materia non équipée)'], Response::HTTP_FORBIDDEN);
        }

        // Verify player has unlocked the materia spell via skills
        if ($materiaEntry['locked']) {
            return new JsonResponse(['error' => 'Sort verrouillé (compétence materia requise)'], Response::HTTP_FORBIDDEN);
        }

        $spell = $materiaEntry['spell'];
        $elementMatch = $materiaEntry['elementMatch'];
        $linkedBonus = $materiaEntry['linkedBonus'];

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

        // Apply element match bonus from materia/slot matching (+25% damage)
        if ($elementMatch) {
            $bonuses['damage'] += (int) round($bonuses['damage'] * CombatCapacityResolver::ELEMENT_MATCH_DAMAGE_BONUS);
        }

        // Apply linked materia bonus (+15% damage when linked slots share element)
        if ($linkedBonus) {
            $bonuses['damage'] += max(1, (int) round($bonuses['damage'] * LinkedMateriaResolver::LINKED_DAMAGE_BONUS));
        }

        // Apply equipped gear elemental damage bonus (+10% per matching piece)
        $gearElementalBonus = $this->gearHelper->getEquippedElementalDamageBonus($spell->getElement());
        if ($gearElementalBonus > 0.0) {
            $bonuses['damage'] += max(1, (int) round($bonuses['damage'] * $gearElementalBonus));
        }

        // Check elemental synergy
        $synergyData = null;
        $lastElement = $fight->getLastElementUsed();
        if ($lastElement !== null && $spell->getElement() !== Element::None) {
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

        // Track heal usage for boss_challenge quests
        if ($spell->getHeal() > 0 && $data['targetType'] === 'player') {
            $fight->setMetadataValue('heal_used', true);
        }

        // Apply the spell
        $hit = FightCalculator::hasAttackHit($spell->getHit() + $bonuses['hit']);
        $messages = $statusMessages;

        $isCritical = false;
        if ($hit) {
            $targetLifeBefore = $target->getLife();
            $spellMessages = $this->spellApplicator->apply($spell, $player, $target, $options);
            $isCritical = in_array('Coup critique !', $spellMessages, true);

            // Tracker la contribution pour les combats world boss
            if ($fight->isWorldBossFight()) {
                $damageDealt = max(0, $targetLifeBefore - $target->getLife());
                if ($damageDealt > 0) {
                    $fight->addContribution($player->getId(), $damageDealt);
                }
            }

            $messages[] = sprintf('%s lance %s !', $player->getName(), $spell->getName());
            $messages = array_merge($messages, $spellMessages);
            $this->combatLogger->logSpell($fight, $player, $target, $spell->getName(), true);
            if ($synergyData) {
                $messages[] = sprintf('Synergie %s activée !', $synergyData['label']);
                $this->combatLogger->logSynergy($fight, $synergyData['label']);
            }
        } else {
            $messages[] = sprintf('%s a raté !', $spell->getName());
            $this->combatLogger->logSpell($fight, $player, $target, $spell->getName(), false);
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

        // Tour du mob — apres le joueur si le joueur est plus rapide
        $mobResult = ['messages' => [], 'dangerAlert' => null];
        if (!$mobFirst && !$fight->isTerminated()) {
            $mobResult = $this->mobActionHandler->doAction($fight);
            $fight->setStep($fight->getStep() + 1);
        }

        // Log victoire/defaite
        if ($fight->isTerminated()) {
            if ($fight->isVictory()) {
                $this->combatLogger->logVictory($fight);
            } else {
                $this->combatLogger->logDefeat($fight);
            }
        }

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'hit' => $hit,
            'messages' => $mobFirst
                ? array_merge($mobResult['messages'], $messages)
                : array_merge($messages, $mobResult['messages']),
            'dangerAlert' => $mobResult['dangerAlert'],
            'synergy' => $synergyData ? $synergyData['label'] : null,
            'spellElement' => $spell->getElement()->value,
            'critical' => $isCritical,
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
