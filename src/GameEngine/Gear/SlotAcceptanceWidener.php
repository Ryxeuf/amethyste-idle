<?php

namespace App\GameEngine\Gear;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Domain;
use App\Enum\AccointanceForm;
use App\GameEngine\Progression\SynergyCalculator;

/**
 * Ce qu'un emplacement de materia accepte **en plus** (ARC-16b).
 *
 * Le lecteur de la forme `slot_acceptance` du canon (§ 9.7) : *« Guerisseur +
 * Pretre — Liturgie : un emplacement accepte une materia de l'ecole voisine »*.
 * La paire suffit, aucun sujet : un emplacement qui refuserait une materia par
 * son **genre** (DOM-03 — la robe n'accepte que les sorts, la plaque que les
 * techniques) l'accepte quand le geste de la materia est **ouvert par l'une des
 * deux ecoles** de l'accointance.
 *
 * Ce que ca ne change pas, et c'est la ligne de la decision 15 : la materia
 * sertie ne rend ni un point ni un levier de plus — elle se sertit dans une
 * tenue qui, sans l'accointance, l'aurait refusee. *De la souplesse, jamais de
 * la puissance.* Et le port n'est jamais touche : le refus elargi ici porte sur
 * le **sertissage**, comme le refus qu'il elargit (GAME_DOMAINS § 3,
 * garde-fou 1).
 */
class SlotAcceptanceWidener
{
    public function __construct(
        private readonly SynergyCalculator $synergyCalculator,
    ) {
    }

    /**
     * Une accointance active fait-elle accepter cette materia a cet emplacement ?
     *
     * Appele la ou le refus vit — apres que `MateriaSlotType::accepts()` a dit
     * non, jamais a sa place : un emplacement libre n'a rien a elargir.
     */
    public function widens(Player $player, PlayerItem $materia): bool
    {
        $spell = $materia->getGenericItem()->getSpell();
        if ($spell === null) {
            return false;
        }

        $slug = $spell->getSlug();
        foreach ($this->synergyCalculator->activeOfForm($player, AccointanceForm::SlotAcceptance) as $synergy) {
            if ($this->domainUnlocks($synergy->getDomainA(), $slug) || $this->domainUnlocks($synergy->getDomainB(), $slug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cette ecole ouvre-t-elle ce geste ?
     *
     * La meme lecture que `CombatSkillResolver::getUnlockedMateriaSpellSlugs`,
     * cote arbre plutot que cote joueur : un nœud `actions.materia.unlock` de
     * l'arbre nomme le sort.
     */
    private function domainUnlocks(Domain $domain, string $spellSlug): bool
    {
        foreach ($domain->getSkills() as $skill) {
            $actions = $skill->getActions();
            if ($actions !== null && (string) ($actions['materia']['unlock'] ?? '') === $spellSlug) {
                return true;
            }
        }

        return false;
    }
}
