<?php

namespace App\Controller\Game\Fight;

use App\Entity\App\Fight;
use App\Entity\CharacterInterface;
use App\Enum\Element;
use App\GameEngine\Enchantment\EnchantmentManager;
use App\GameEngine\Fight\Calculator\DamageMultiplierNormalizer;
use App\GameEngine\Fight\ChargeLedger;
use App\GameEngine\Fight\CombatCapacityResolver;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\CombatScope;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Fight\ElementalSynergyCalculator;
use App\GameEngine\Fight\FightCalculator;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\LinkedMateriaResolver;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Fight\QuiverResolver;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Fight\StatusEffectManager;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;
use App\GameEngine\Progression\CombatGestureCase;
use App\GameEngine\Progression\CombatGestureLedger;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\Realtime\Fight\FightTurnPublisher;
use App\GameEngine\Reputation\CounterfeitService;
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
        private readonly EnchantmentManager $enchantmentManager,
        private readonly PlayerEffectiveStatsCalculator $playerEffectiveStatsCalculator,
        private readonly FightTurnPublisher $fightTurnPublisher,
        private readonly DamageMultiplierNormalizer $damageMultiplierNormalizer,
        private readonly CounterfeitService $counterfeitService,
        private readonly CombatLeverScale $leverScale,
        private readonly QuiverResolver $quiverResolver,
        private readonly CombatGestureCase $gestureCase,
        private readonly CombatGestureLedger $gestureLedger,
        private readonly ChargeLedger $chargeLedger,
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
        $isCoop = $fight->isCoopFight();
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['spellSlug']) || !isset($data['targetId']) || !isset($data['targetType'])) {
            return new JsonResponse(['error' => 'Invalid request data'], Response::HTTP_BAD_REQUEST);
        }

        // Coop turn validation
        if ($isCoop && !$this->turnResolver->isPlayerTurn($fight, $player->getId())) {
            return new JsonResponse(['error' => 'Ce n\'est pas votre tour !', 'success' => false]);
        }

        // Solo: Priorite de vitesse : le mob agit en premier s'il est plus rapide
        $mobFirst = !$isCoop && $this->turnResolver->isMobFirst($fight);
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

        // ARC-04b : la ressource du registre distance. Le refus precede la
        // consommation des PM — un geste refuse ne doit rien couter, et un
        // carquois vide n'est jamais un mur : l'attaque d'arme reste gratuite
        // (regle 10) et tout geste a `ammoCost` nul passe.
        if (!$this->quiverResolver->canAfford($fight, $player, $spell)) {
            return new JsonResponse(['error' => 'Carquois vide', 'success' => false]);
        }

        // ARC-18e : la charge. Le refus se place ici, au meme rang que le
        // carquois et **avant** la consommation des PM : un geste qu'on ne peut
        // pas payer ne doit rien couter. ***Un geste qui consomme plus qu'on ne
        // possede ne se joue pas du tout***, il ne se joue pas en moins fort —
        // c'est ce qui fait de la charge une decision plutot qu'un bonus.
        if (!$this->chargeLedger->affords($fight, $player, $spell)) {
            return new JsonResponse(['error' => 'Charge insuffisante', 'success' => false]);
        }

        // Check energy
        if (!$this->combatSkillResolver->consumeEnergy($player, $spell)) {
            return new JsonResponse(['error' => 'Énergie insuffisante', 'success' => false]);
        }

        $this->quiverResolver->consume($fight, $player, $spell);
        $this->chargeLedger->apply($fight, $player, $spell);

        // Find target
        $target = $this->findTarget($fight, (int) $data['targetId'], $data['targetType']);
        if (!$target) {
            return new JsonResponse(['error' => 'Cible introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Process status effects at start of turn
        $statusMessages = $this->statusEffectManager->processStartOfTurn($fight, $player);

        // Calculate combat bonuses from skills.
        // DOM-01 : bornes a la case du geste — l'element du sort et le registre
        // « sorts ». Sans cette portee, l'arbre du berserker (feu x melee)
        // ajoutait ses degats a un sort d'eau, et le build n'avait plus de sens.
        $bonuses = $this->combatSkillResolver->getCombatBonuses($player, CombatScope::ofSpell($spell));

        // ARC-03b — les leviers du geste, bornes par la meme case que les
        // statistiques plates, et convertis une seule fois pour toute l'action.
        //
        // ARC-11b-b — l'intention du geste ferme la borne : `mending` ne suit
        // pas une boule de feu, `grip` ne prolonge pas un bouclier. Elle se
        // resout sans le statut, parce que le statut n'est charge que plus bas
        // dans `SpellApplicator` ; l'ordre des questions de la derivation fait
        // que le degat et le soin repondent avant lui, et ce sont les seules
        // intentions que les 253 gestes livres portent.
        $levers = $this->combatSkillResolver->getLeverEffects($player, CombatScope::ofSpell($spell), $spell->getRegister(), $spell->resolveIntent(), $fight);

        // Apply enchantment bonuses from equipped items
        $enchantBonuses = $this->enchantmentManager->getEnchantmentBonuses($player);
        foreach (['damage', 'heal', 'hit', 'critical'] as $stat) {
            if (isset($enchantBonuses[$stat])) {
                $bonuses[$stat] += (int) $enchantBonuses[$stat];
            }
        }

        // Stacking additif des bonus d'équipement + soft cap (évite les écarts > 30% entre builds)
        $equipBonusPercent = 0.0;
        if ($elementMatch) {
            $equipBonusPercent += CombatCapacityResolver::ELEMENT_MATCH_DAMAGE_BONUS;
        }
        if ($linkedBonus) {
            $equipBonusPercent += LinkedMateriaResolver::LINKED_DAMAGE_BONUS;
        }
        $gearElementalBonus = $this->gearHelper->getEquippedElementalDamageBonus($spell->getElement());
        if ($gearElementalBonus > 0.0) {
            $equipBonusPercent += $gearElementalBonus;
        }
        if ($equipBonusPercent > 0.0) {
            $normalizedBonus = $this->damageMultiplierNormalizer->normalizeBonus($equipBonusPercent);
            $bonuses['damage'] += max(1, (int) round($bonuses['damage'] * $normalizedBonus));
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
            'levers' => $levers,
            'fight' => $fight,
        ];

        // Apply synergy damage multiplier (avec soft cap)
        if ($synergyData) {
            $rawMultiplier = $synergyData['damageMultiplier'] ?? 1.0;
            $cappedMultiplier = $this->damageMultiplierNormalizer->normalizeSynergy($rawMultiplier);
            $bonuses['damage'] = (int) round($bonuses['damage'] * $cappedMultiplier);
            $options['damage'] = $bonuses['damage'];

            // Self damage from Eclipse synergy
            $selfDamage = $this->synergyCalculator->getSelfDamage(
                $this->playerEffectiveStatsCalculator->getEffectiveMaxLife($player),
                $synergyData
            );
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
        $hit = FightCalculator::hasAttackHitWithLevers($spell->getHit() + $bonuses['hit'], $levers, $this->leverScale);
        $messages = $statusMessages;

        // FAC-07 — la trahison : une contrefacon marche neuf fois et trahit a
        // la dixieme. Le compteur cache se decremente a chaque lancement ; au
        // declenchement, le sort echoue au pire moment — le tour est perdu,
        // le contrecoup frappe, la materia se brise, et le monstre joue.
        $betrayed = $this->counterfeitService->consumeCharge($materiaEntry['materia']);
        if ($betrayed) {
            $hit = false;
        }

        // ARC-06b — la case du geste, retenue **avant** que le sort ne parte :
        // c'est `SpellApplicator` qui dispatche `MobDeadEvent`, et l'evenement
        // ne porte que le monstre. Le geste rate compte comme le geste
        // reussi : ce qui designe la case est ce qu'on a joue, pas ce qui a
        // touche — sinon un joueur malchanceux serait credite de l'arbre du
        // tour precedent.
        $this->gestureLedger->record($fight, $player, $this->gestureCase->forSpell($player, $spell));

        $isCritical = false;
        $damageDealt = 0;
        $healAmount = 0;
        if ($hit) {
            $targetLifeBefore = $target->getLife();
            $playerLifeBefore = $player->getLife();
            $spellMessages = $this->spellApplicator->apply($spell, $player, $target, $options);
            $isCritical = in_array('Coup critique !', $spellMessages, true);

            // Calculate actual damage dealt or heal amount
            if ($spell->getDamage() > 0) {
                $damageDealt = max(0, $targetLifeBefore - $target->getLife());
            }
            if ($spell->getHeal() > 0) {
                $healAmount = max(0, $target->getLife() - $targetLifeBefore);
                if ($healAmount === 0 && $data['targetType'] === 'player') {
                    // Heal on self (player is caster and target)
                    $healAmount = max(0, $player->getLife() - $playerLifeBefore);
                }
            }

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
        } elseif ($betrayed) {
            $messages[] = sprintf('%s échoue !', $spell->getName());
            $messages = array_merge($messages, $this->counterfeitService->betray(
                $player,
                $materiaEntry['materia'],
                $materiaEntry['slot'],
                $this->playerEffectiveStatsCalculator->getEffectiveMaxLife($player),
            ));
            $this->combatLogger->logSpell($fight, $player, $target, $spell->getName(), false);
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

        // Tour du mob / avancement coop
        if ($isCoop) {
            // Coop: advance turn (mobs auto-resolve in advanceCoopTurn)
            if (!$fight->isTerminated()) {
                $turnResult = $this->turnResolver->advanceCoopTurn($fight, $this->mobActionHandler);
                $messages = array_merge($messages, $turnResult['messages']);
                $mobResult['dangerAlert'] = $turnResult['dangerAlert'];
            }
        } else {
            // Solo: mob acts after player if player was faster
            $mobResult = ['messages' => [], 'dangerAlert' => null];
            if (!$mobFirst && !$fight->isTerminated()) {
                $mobResult = $this->mobActionHandler->doAction($fight);
                $fight->setStep($fight->getStep() + 1);
            }
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

        // Publish turn change via Mercure for coop
        if ($isCoop) {
            if ($fight->isTerminated()) {
                $this->fightTurnPublisher->publishFightEnd($fight);
            } else {
                $this->fightTurnPublisher->publishTurnChange($fight);
            }
        }

        return new JsonResponse([
            'success' => true,
            'hit' => $hit,
            'damage' => $damageDealt,
            'heal' => $healAmount,
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
