<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\Enum\CombatLever;
use App\Enum\Element;
use App\Enum\SpellScope;
use App\Event\Fight\MobDeadEvent;
use App\Event\Fight\PlayerDeadEvent;
use App\GameEngine\Balance\MendingAnchor;
use App\GameEngine\Fight\Calculator\CriticalCalculator;
use App\GameEngine\Fight\Calculator\DamageCalculator;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\World\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SpellApplicator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly StatusEffectManager $statusEffectManager,
        private readonly CombatLogger $combatLogger,
        private readonly DamageCalculator $damageCalculator,
        private readonly CriticalCalculator $criticalCalculator,
        private readonly WeatherService $weatherService,
        private readonly PlayerEffectiveStatsCalculator $playerEffectiveStatsCalculator,
        private readonly CombatLeverScale $leverScale,
        private readonly DeferredQueue $deferredQueue,
        private readonly ArmorMitigationResolver $armorMitigation,
    ) {
    }

    public function apply(Spell $spell, CharacterInterface $sender, CharacterInterface $target, array $options = []): array
    {
        $domainHeal = $options['heal'] ?? 0;
        $domainDamage = $options['damage'] ?? 0;
        $domainCritical = $options['critical'] ?? 0;
        $fight = $options['fight'] ?? null;

        // ARC-03b — les leviers traversent le calcul, chacun n'etant lu qu'a
        // sa place. Deux porteurs, parce que les leviers offensifs sont ceux
        // de l'attaquant et les defensifs ceux de la cible : les confondre
        // ferait profiter un attaquant de la garde de son adversaire.
        $levers = $options['levers'] ?? CombatLeverEffects::none();
        $targetLevers = $options['targetLevers'] ?? CombatLeverEffects::none();

        $messages = [];

        $effectiveMaxLife = $target instanceof Player
            ? $this->playerEffectiveStatsCalculator->getEffectiveMaxLife($target)
            : null;

        $damage = $this->damageCalculator->computeBaseDamage($spell, $domainDamage, $target, $effectiveMaxLife);
        $heal = $this->damageCalculator->computeBaseHeal($spell, $domainHeal, $target, $effectiveMaxLife);

        // ARC-17b — ce qu'un monstre frappe vient de sa case, pas de son geste.
        //
        // Pose **ici**, sur la valeur de base et avant tout modificateur : les
        // leviers, le critique, la resistance, la meteo et la garde continuent
        // de s'appliquer par-dessus, exactement comme avant. Le jalon change ce
        // que le geste vaut, jamais ce que le combat en fait.
        //
        // Et pose **du cote de l'attaquant** : c'est la seule condition, un
        // monstre porte sa case qu'il frappe un joueur, un autre monstre ou une
        // invocation.
        if ($sender instanceof Mob) {
            $damage = MonsterDamageLaw::damageFor($sender->getMonster(), $spell, $damage);
        }

        // `dodge` — **avant tout calcul de degats**. Poser l'esquive ici et non
        // apres la resistance est la moitie de ce qui la distingue de `guard` :
        // ce qui est evite n'est pas reduit, il n'a pas lieu. Le soin passe :
        // on n'esquive pas ce qui vous soigne.
        if ($damage > 0 && $this->damageCalculator->isDodged($targetLevers, $this->leverScale)) {
            $damage = 0;
            $messages[] = sprintf('%s esquive !', $target->getName());
        }

        // `power` et `mending` — multiplicatifs sur la valeur de base.
        $damage = $this->damageCalculator->applyPower($damage, $levers, $this->leverScale);
        $heal = $this->damageCalculator->applyMending($heal, $levers, $this->leverScale);

        // Dungeon difficulty: scale mob damage output
        if ($damage > 0 && $sender instanceof Mob && $fight !== null) {
            $damageMultiplier = (float) $fight->getMetadataValue('difficulty_damage_multiplier', 1.0);
            if ($damageMultiplier > 1.0) {
                $damage = (int) round($damage * $damageMultiplier);
            }
        }

        // Critical hit check
        if ($this->criticalCalculator->isCritical($spell, $domainCritical, $levers, $this->leverScale)) {
            $heal = $this->criticalCalculator->applyCriticalModifier($heal, $levers, $this->leverScale);
            $damage = $this->criticalCalculator->applyCriticalModifier($damage, $levers, $this->leverScale);
            $messages[] = 'Coup critique !';
            if ($fight !== null) {
                $this->combatLogger->logCritical($fight, $sender);
            }
        }

        // Elemental resistance (reduce damage if target is a mob with resistances)
        // `pierce` en ignore une part — avant elle, jamais apres.
        if ($damage > 0 && $target instanceof Mob) {
            $pierce = $levers->isEmpty() ? 0.0 : $levers->pointsFor(CombatLever::Pierce, $this->leverScale);
            $result = $this->damageCalculator->applyElementalResistance($damage, $spell, $target, $pierce);
            $damage = $result['damage'];
            if ($result['resisted']) {
                $messages[] = sprintf('%s resiste a %s !', $target->getName(), $spell->getElement()->value);
                if ($fight !== null) {
                    $this->combatLogger->logResist($fight, $target, $spell->getElement()->value);
                }
            } elseif ($result['weak']) {
                $messages[] = sprintf('%s est faible face a %s !', $target->getName(), $spell->getElement()->value);
            }
        }

        // Weather elemental modifier
        if ($damage > 0 && $fight !== null && $spell->getElement() !== Element::None) {
            $weather = $this->resolveWeather($fight);
            if ($weather !== null) {
                $modifier = $this->weatherService->getElementalModifier($weather, $spell->getElement());
                if ($modifier !== 1.0) {
                    $damage = $this->damageCalculator->applyWeatherModifier($damage, $modifier);
                    if ($modifier > 1.0) {
                        $messages[] = sprintf('La meteo renforce %s !', $spell->getElement()->label());
                    } else {
                        $messages[] = sprintf('La meteo affaiblit %s !', $spell->getElement()->label());
                    }
                }
            }
        }

        // Berserk damage modifier on sender
        if ($fight !== null && $this->statusEffectManager->isCharacterBerserk($fight, $sender)) {
            $damage = $this->damageCalculator->applyBerserkModifier($damage);
        }

        // Burn damage reduction on sender
        if ($fight !== null && $this->hasBurnEffect($fight, $sender)) {
            $damage = $this->damageCalculator->applyBurnReduction($damage);
        }

        // `guard` — **apres** la resistance, et apres tout ce que l'attaquant
        // apporte (meteo, berserk, brulure) : c'est la derniere reduction que
        // le corps de la cible oppose, avant le bouclier qui est un tampon
        // exterieur. La borner ici plutot que dans le bloc de resistance la
        // rend valable aussi quand la cible est un joueur, qui n'a pas de
        // resistance elementaire.
        $damage = $this->damageCalculator->applyGuard($damage, $targetLevers, $this->leverScale);

        // ARC-19 — **la mitigation d'armure, a la meme place que `guard` et
        // juste apres lui.** Les deux se multiplient plutot que de s'ajouter :
        // additionner des reductions les ferait atteindre 100 %, et une cible
        // invulnerable n'est plus une cible. C'est ici que vit la moitie que le
        // canon refuse a l'arbre — *la mitigation d'un tank vient de son
        // armure, pas de son arbre* (decision 21).
        $damage = $this->armorMitigation->mitigate($damage, $target);

        // Invariant métier : les dégâts ne peuvent jamais être négatifs
        $damage = max(0, $damage);

        // Shield absorption on target
        if ($damage > 0 && $fight !== null) {
            $shieldAbsorb = $this->getShieldAbsorb($fight, $target);
            if ($shieldAbsorb > 0) {
                $result = $this->damageCalculator->applyShieldAbsorption($damage, $shieldAbsorb);
                $damage = $result['damage'];
                $messages[] = sprintf('Le bouclier absorbe %d degats !', $result['absorbed']);
                $this->combatLogger->logShield($fight, $target, $result['absorbed']);
            }
        }

        $life = $target->getLife() - $damage + $heal;
        $capMax = $target instanceof Player
            ? $this->playerEffectiveStatsCalculator->getEffectiveMaxLife($target)
            : $target->getMaxLife();
        $life = min($capMax, $life);
        $life = max(0, $life);

        // ONB-11 — le second mannequin **ne peut pas tuer**.
        //
        // Le joueur doit voir sa barre descendre pour comprendre a quoi servent
        // les soins, mais l'apprentissage ne peut pas se solder par une mort. Le
        // plancher est pose **ici** et non dans le calcul des degats : il doit
        // valoir quel que soit le chemin — attaque, effet de statut, riposte —
        // sans quoi il suffirait d'un chemin oublie pour tuer un debutant.
        //
        // Un plancher a 1 et non a 0 : a 0 le personnage est mort, et le combat
        // se terminerait exactement comme on voulait l'eviter.
        if ($life < 1 && $this->isStruckByTrainingDummy($sender)) {
            $life = 1;
        }

        // ARC-18f — **le differe**. Le geste est calcule entierement — degats
        // du sort, passifs, resistance, garde, tout —, puis **mis de cote au
        // lieu d'etre applique**. Differer apres le calcul et non avant est ce
        // qui fait qu'un differe reste le geste qu'il est : le calculer a
        // l'echeance ferait dependre son resultat de l'etat du monde deux tours
        // plus tard, c'est-a-dire d'une garde qu'on n'avait pas vue et d'une
        // cible qui a peut-etre change.
        if ($fight !== null && $spell->isDeferred() && $sender instanceof Player && $damage > 0) {
            $this->deferredQueue->defer($fight, $sender, $damage, $spell->getDeferredTurns());
            $messages[] = sprintf('%s frappera dans %d tours.', $spell->getName(), DeferredLaw::delayFor($spell->getDeferredTurns()));

            return $messages;
        }

        $damageActuallyDealt = max(0, $target->getLife() - $life);

        $target->setLife($life);

        // ARC-18a — **la riposte**. Elle se lit sur ce que le coup a
        // *reellement* retire, jamais sur ce qu'il annoncait : un coup esquive,
        // absorbe par un bouclier ou ramene a zero par la garde retire zero, et
        // zero ne riposte pas.
        //
        // Poser la question sur le **resultat** plutot que sur la cause ferme
        // d'un coup tous les chemins d'evitement, y compris ceux qui n'existent
        // pas encore — et c'est le garde-fou d'admission de la forme : si
        // l'esquive declenchait la riposte, l'encaisse optimale consisterait a
        // se faire toucher expres.
        if ($fight !== null && $damageActuallyDealt > 0) {
            $riposte = $this->resolveRiposte($fight, $target, $damageActuallyDealt);
            if ($riposte > 0) {
                $sender->setLife(max(0, $sender->getLife() - $riposte));
                $messages[] = sprintf('La riposte rend %d degats !', $riposte);
                $this->combatLogger->logDamage($fight, $sender, $riposte, 'riposte');
            }
        }

        if ($target->getLife() > 0) {
            $target->setDiedAt(null);
        } else {
            $target->setDiedAt(new \DateTime());
        }

        // Log damage and heal
        if ($fight !== null) {
            if ($damage > 0) {
                $this->combatLogger->logDamage($fight, $target, $damage, $spell->getName());
            }
            if ($heal > 0) {
                $this->combatLogger->logHeal($fight, $target, $heal, $spell->getName());
            }
        }

        $this->entityManager->persist($target);
        $this->entityManager->flush();
        $this->entityManager->refresh($target);

        // Apply status effect from spell
        if ($fight !== null && $spell->getStatusEffectSlug() !== null) {
            $statusEffect = $this->entityManager->getRepository(StatusEffect::class)->findOneBy([
                'slug' => $spell->getStatusEffectSlug(),
            ]);

            if ($statusEffect !== null) {
                $intent = $spell->resolveIntent($statusEffect->getType());
                $scope = $spell->resolveScope($statusEffect->getType());

                // ARC-11b-b — l'intention complete est **ici**, et nulle part
                // avant : c'est le type du statut qui distingue une entrave
                // d'une protection, et il vient d'etre charge. `grip` et `ward`
                // ne se croisent donc que sur une entrave — un bouclier ne se
                // rallonge pas parce que son porteur a achete le levier
                // principal du controle.
                $aimedLevers = $levers->aimedAt($intent);
                $aimedTargetLevers = $targetLevers->aimedAt($intent);

                if (DepositLaw::deposits($intent, $scope)) {
                    // ARC-11b — la loi du depot. Le geste ne reagit pas : il
                    // pose une duree qui court **que son lanceur soit connecte
                    // ou non**, sur tous les allies s'il touche le groupe.
                    // C'est ce qui rend l'entretien jouable dans un donjon
                    // semi-synchrone, ou un soin reactif est une mecanique
                    // morte.
                    $allies = $scope === SpellScope::Group
                        ? $this->alliesOf($fight, $sender)
                        : [$target];

                    $total = $this->depositedValue($statusEffect, $spell);
                    $deposited = $this->statusEffectManager->deposit($fight, $allies, $statusEffect, $total, $aimedLevers);

                    if ($deposited > 0) {
                        $messages[] = $scope === SpellScope::Group
                            ? sprintf('%s se depose sur le groupe.', $statusEffect->getName())
                            : sprintf('%s se depose sur %s.', $statusEffect->getName(), $target->getName());
                        $this->combatLogger->logStatusApply($fight, $target, $statusEffect->getName());
                    }
                } else {
                    // `grip` de celui qui applique, `ward` de celui qui subit :
                    // les deux se croisent sur le jet d'application, et nulle part
                    // ailleurs (ARC-03b) — ni sur autre chose qu'une entrave
                    // (ARC-11b-b).
                    $this->statusEffectManager->applyStatusEffect($fight, $target, $statusEffect, $aimedLevers, $aimedTargetLevers);
                    $messages[] = sprintf('%s est affecte par %s !', $target->getName(), $statusEffect->getName());
                    $this->combatLogger->logStatusApply($fight, $target, $statusEffect->getName());
                }
            }
        }

        if ($target->isDead()) {
            if ($fight !== null) {
                $this->combatLogger->logDeath($fight, $target);
            }
            if ($target instanceof Mob) {
                $this->eventDispatcher->dispatch(new MobDeadEvent($target), MobDeadEvent::NAME);
            }
            if ($target instanceof Player) {
                $this->eventDispatcher->dispatch(new PlayerDeadEvent($target), PlayerDeadEvent::NAME);
            }
        }

        return $messages;
    }

    /**
     * Check if character has burn status effect.
     */
    private function hasBurnEffect(Fight $fight, CharacterInterface $character): bool
    {
        $effects = $this->statusEffectManager->getActiveEffects($fight, $character);
        foreach ($effects as $fightEffect) {
            if ($fightEffect->getStatusEffect()->getType() === StatusEffect::TYPE_BURN) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the current weather from the fight's map.
     */
    private function resolveWeather(Fight $fight): ?\App\Enum\WeatherType
    {
        $player = $fight->getPlayers()->first();
        if ($player === false) {
            return null;
        }

        $map = $player->getMap();

        return $map?->getCurrentWeather();
    }

    /**
     * Le coup vient-il d'un mannequin d'entrainement ? (ONB-11).
     *
     * Le plancher a 1 PV ne s'applique qu'aux coups d'un mannequin — jamais a
     * ceux d'un vrai monstre. Attacher la clemence a **l'agresseur** et non a la
     * cible est ce qui empeche la mesure de fuir : un joueur qui sort du Fanal
     * meurt comme tout le monde, des le premier loup.
     */
    private function isStruckByTrainingDummy(CharacterInterface $sender): bool
    {
        return $sender instanceof Mob && $sender->getMonster()->isTrainingDummy();
    }

    /**
     * Get the total shield absorption available for a character.
     */
    /**
     * Ce que les ripostes portees par cette cible rendent sur ce coup.
     *
     * La valeur vit sur l'**application** (`FightStatusEffect::valuePerTurn`,
     * le champ qu'ARC-11b-a a pose pour les depots) et non sur la fiche du
     * statut, qui est partagee par toutes ses applications.
     */
    private function resolveRiposte(Fight $fight, CharacterInterface $character, int $lifeActuallyLost): int
    {
        $returned = 0;

        foreach ($this->statusEffectManager->getActiveEffects($fight, $character) as $fightEffect) {
            if ($fightEffect->getStatusEffect()->getType() !== StatusEffect::TYPE_RIPOSTE) {
                continue;
            }

            $returned += RiposteLaw::returnedDamage($lifeActuallyLost, $fightEffect->getValuePerTurn());
        }

        return $returned;
    }

    private function getShieldAbsorb(Fight $fight, CharacterInterface $character): int
    {
        $effects = $this->statusEffectManager->getActiveEffects($fight, $character);
        $totalAbsorb = 0;

        foreach ($effects as $fightEffect) {
            if ($fightEffect->getStatusEffect()->getType() === StatusEffect::TYPE_SHIELD) {
                $modifiers = $fightEffect->getStatusEffect()->getStatModifier();
                if ($modifiers !== null && isset($modifiers['shield_absorb'])) {
                    $totalAbsorb += (int) $modifiers['shield_absorb'];
                }
            }
        }

        return $totalAbsorb;
    }

    /**
     * Les allies sur lesquels un depot de groupe se pose (ARC-11b).
     *
     * Un depot lance par un joueur couvre les joueurs de la rencontre ; lance
     * par un monstre, il couvre les monstres. Le lanceur en fait partie : un
     * geste qui protege le groupe protege celui qui le lance, sinon
     * l'archetype d'encaisse paierait un tour pour couvrir tout le monde sauf
     * lui.
     *
     * @return list<CharacterInterface>
     */
    private function alliesOf(Fight $fight, CharacterInterface $sender): array
    {
        $allies = [];

        if ($sender instanceof Mob) {
            foreach ($fight->getMobs() as $mob) {
                $allies[] = $mob;
            }

            return $allies;
        }

        foreach ($fight->getPlayers() as $player) {
            $allies[] = $player;
        }

        return $allies;
    }

    /**
     * La valeur **totale** que ce depot vaut, avant etalement.
     *
     * **Un depot de soin vaut le palier du geste qui le pose** (ARC-20c-b,
     * `MendingAnchor`) : la fiche de l'effet est **partagee** — la meme
     * `regeneration` sert des gestes de paliers differents —, donc lire son
     * `healPerTurn` donnerait la meme provision a la Maree (palier 2) et a la
     * Grande Maree (palier 4). C'est le defaut des gestes partages de monstre,
     * transpose aux soins, et la meme reponse : *la valeur vit sur le geste,
     * jamais sur la fiche commune*.
     *
     * Les depots de **degats** (DOT hostiles) gardent la lecture de fiche :
     * leur recalibration appartient a ARC-05c, et les melanger ici deplacerait
     * des valeurs que ce jalon ne mesure pas.
     */
    private function depositedValue(StatusEffect $effect, Spell $spell): int
    {
        if ($effect->isHealing()) {
            return MendingAnchor::depositTotalFor($spell->getLevel(), $effect->getDuration());
        }

        return (int) $effect->getDamagePerTurn() * max(1, $effect->getDuration());
    }
}
