<?php

namespace App\GameEngine\Dungeon;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\GameEngine\Fight\CombatCapacityResolver;
use App\GameEngine\Fight\CombatSkillResolver;

/**
 * L'action reelle d'un membre en donjon (DON-02).
 *
 * Avant : `damage = max(1, $player->getHit())` — ni arme, ni sort, ni materia,
 * ni passif n'entrait dans le calcul, et deux builds finis faisaient le meme
 * donjon au point de degat pres. L'action devient celle du build :
 *
 *  - **l'attaque de base** lit le geste de l'arme equipee (le `spell` de la
 *    piece — `none-attack-2` sur une lame trempee) plus les passifs de degats
 *    des arbres ; mains nues, le geste vaut 1 (ONB-20a : aucun chemin de
 *    combat n'echoue faute d'arme) ;
 *  - **un sort de materia sertie** (via `CombatCapacityResolver`) applique le
 *    degat du sort, les memes passifs, et le bonus d'accord d'element de
 *    l'emplacement. Un sort verrouille (accord non appris) ou sans degat
 *    retombe sur l'attaque de base plutot que d'echouer.
 */
class DungeonActionResolver
{
    public const BARE_HANDS_DAMAGE = 1;

    public function __construct(
        private readonly CombatCapacityResolver $capacityResolver,
        private readonly CombatSkillResolver $skillResolver,
    ) {
    }

    /**
     * @return array{damage: int, spellSlug: ?string}
     */
    public function resolve(Player $player, ?string $spellSlug = null): array
    {
        $bonuses = $this->skillResolver->getCombatBonuses($player);
        $skillDamage = max(0, $bonuses['damage']);

        if (null !== $spellSlug && '' !== $spellSlug) {
            $entry = $this->capacityResolver->findMateriaSpell($player, $spellSlug);
            if (null !== $entry && !$entry['locked'] && (int) ($entry['spell']->getDamage() ?? 0) > 0) {
                $damage = (int) $entry['spell']->getDamage() + $skillDamage;
                $damage = (int) round($damage * $this->capacityResolver->getElementMatchDamageMultiplier($entry['slot'], $entry['materia']));

                return ['damage' => max(1, $damage), 'spellSlug' => $spellSlug];
            }
        }

        return ['damage' => max(1, $this->weaponDamage($player) + $skillDamage), 'spellSlug' => null];
    }

    /**
     * Le degat du geste de l'arme equipee — 1 a mains nues.
     */
    private function weaponDamage(Player $player): int
    {
        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if (PlayerItem::GEAR_MAIN_WEAPON !== $playerItem->getGear()) {
                    continue;
                }

                $spell = $playerItem->getGenericItem()->getSpell();
                if (null !== $spell && (int) ($spell->getDamage() ?? 0) > 0) {
                    return (int) $spell->getDamage();
                }
            }
        }

        return self::BARE_HANDS_DAMAGE;
    }
}
