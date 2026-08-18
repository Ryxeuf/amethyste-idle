<?php

namespace App\GameEngine\Dungeon;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\Enum\CombatLever;
use App\GameEngine\Fight\CombatCapacityResolver;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Fight\TransferLaw;
use App\GameEngine\Progression\CombatLeverScale;
use Doctrine\ORM\EntityManagerInterface;

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
        private readonly CombatLeverScale $leverScale,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{damage: int, spellSlug: ?string, transfer?: array{share: float, turns: int}}
     */
    public function resolve(Player $player, ?string $spellSlug = null): array
    {
        $bonuses = $this->skillResolver->getCombatBonuses($player);
        $skillDamage = max(0, $bonuses['damage']);

        // ARC-03b — `power` vaut dans un donjon ce qu'il vaut en zone.
        //
        // Le donjon calcule son degat a part (DON-02) : sans cette ligne, un
        // arbre converti aux leviers serait pertinent en zone et muet en
        // donjon, ce qui est exactement le genre d'ecart qu'on ne remarque
        // qu'une fois le contenu ecrit.
        $power = $this->skillResolver->getLeverEffects($player)->multiplierFor(CombatLever::Power, $this->leverScale);

        if (null !== $spellSlug && '' !== $spellSlug) {
            $entry = $this->capacityResolver->findMateriaSpell($player, $spellSlug);

            // ARC-18d — **le donjon ne savait jouer que des degats.** Tout
            // geste dont `damage` valait zero retombait silencieusement sur
            // l'attaque d'arme, si bien que le seul contenu de groupe du jeu
            // n'acceptait aucun geste d'entretien ni d'encaisse — c'est-a-dire
            // exactement les deux fonctions dont le canon dit qu'elles gagnent
            // au groupe (§ 7 bis).
            //
            // Le transfert est le premier geste non offensif que le donjon
            // sache jouer. Il est traite **avant** le degat, parce qu'un geste
            // de protection qui blesserait aussi serait rendu par le degat et
            // sa protection perdue en route.
            $transfer = null !== $entry && !$entry['locked']
                ? $this->transferOf($entry['spell'])
                : null;
            if (null !== $transfer) {
                return ['damage' => 0, 'spellSlug' => $spellSlug, 'transfer' => $transfer];
            }

            if (null !== $entry && !$entry['locked'] && (int) ($entry['spell']->getDamage() ?? 0) > 0) {
                $damage = (int) $entry['spell']->getDamage() + $skillDamage;
                $damage = (int) round($damage * $this->capacityResolver->getElementMatchDamageMultiplier($entry['slot'], $entry['materia']) * $power);

                return ['damage' => max(1, $damage), 'spellSlug' => $spellSlug];
            }
        }

        return ['damage' => max(1, (int) round(($this->weaponDamage($player) + $skillDamage) * $power)), 'spellSlug' => null];
    }

    /**
     * Ce geste pose-t-il un transfert, et pour combien de temps ?
     *
     * La **duree** vient du statut, comme partout ailleurs. La **part**, elle,
     * ne vient de nulle part : le canon en fixe une — *au plus la moitie* — et
     * laisser chaque geste choisir la sienne rendrait le curseur qu'on vient de
     * fermer. Deux transferts different donc par leur duree, jamais par leur
     * force.
     *
     * @return array{share: float, turns: int}|null
     */
    private function transferOf(Spell $spell): ?array
    {
        $slug = $spell->getStatusEffectSlug();
        if (null === $slug || '' === $slug) {
            return null;
        }

        $effect = $this->entityManager->getRepository(StatusEffect::class)->findOneBy(['slug' => $slug]);
        if (null === $effect || StatusEffect::TYPE_TRANSFER !== $effect->getType()) {
            return null;
        }

        return [
            'share' => TransferLaw::MAX_SHARE,
            'turns' => TransferLaw::durationFor($effect->getDuration()),
        ];
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
