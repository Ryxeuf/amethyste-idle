<?php

namespace App\Tests\Integration\Fight;

use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\GameEngine\Fight\DepositLaw;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Ce que la loi du depot exige des gestes livres (ARC-11b).
 *
 * GAME_ARCHETYPES § 7 bis. Un geste qui se depose et qui ne dure pas n'a rien
 * depose : il a **reagi**, et c'est exactement ce que le modele semi-synchrone
 * interdit. Ce contrat tient l'invariant sur les vraies donnees, y compris
 * pour les gestes qui n'existent pas encore — il est ecrit **avant** qu'un
 * seul geste ne porte la portee `le groupe`, pour qu'aucun ne puisse naître
 * instantane.
 */
class DepositedGestureContractTest extends AbstractIntegrationTestCase
{
    /**
     * Aucun geste qui se depose n'est instantane.
     *
     * L'invariant nomme par le jalon. Il porte sur ce que le geste **est**,
     * pas sur ce qu'il fait : un geste de groupe ou une protection doit
     * s'appuyer sur un effet de statut, puisque c'est lui qui porte la duree.
     */
    public function testNoDepositedGestureIsInstant(): void
    {
        $offenders = [];

        foreach ($this->em->getRepository(Spell::class)->findAll() as $spell) {
            $effect = $this->statusEffectOf($spell);
            $intent = $spell->resolveIntent($effect?->getType());
            $scope = $spell->resolveScope($effect?->getType());

            if (!DepositLaw::deposits($intent, $scope)) {
                continue;
            }

            if ($effect === null) {
                $offenders[] = sprintf('%s (se depose, mais ne porte aucun effet de duree)', $spell->getSlug());
                continue;
            }

            // La duree **declaree**, pas la duree opposable : `durationFor()`
            // est un `max()`, donc la relire ici ne testerait que PHP.
            if ($effect->getDuration() < DepositLaw::MIN_DURATION) {
                $offenders[] = sprintf('%s (duree %d)', $spell->getSlug(), $effect->getDuration());
            }
        }

        self::assertSame(
            [],
            $offenders,
            "Ces gestes se deposent sans durer — un depot d'un tour est un nœud mort :\n" . implode("\n", $offenders)
        );
    }

    /**
     * Le jalon ne deplace aucune valeur de jeu, et c'est **mesure**.
     *
     * L'extension de la loi a toute `protection` fait basculer des gestes
     * **deja livres** vers le depot — les 15 gestes de bouclier. Ce n'est donc
     * pas un jalon inerte, et il faut verifier ce que le basculement change :
     *
     *  - la **duree** ne bouge que si elle etait sous le plancher ;
     *  - la **chance** disparait du chemin (on ne provisionne pas au hasard),
     *    ce qui ne change rien tant qu'elle vaut 100 ;
     *  - l'**etalement** ne s'applique qu'a ce qui a une valeur par tour, donc
     *    jamais a une absorption, qui vit dans `statModifier`.
     *
     * Si l'une de ces trois conditions cesse d'etre vraie, ce test tombe — et
     * c'est exactement quand il faut le savoir.
     */
    public function testTheLawDoesNotSilentlyMoveAnyDeliveredValue(): void
    {
        $moved = [];

        foreach ($this->em->getRepository(Spell::class)->findAll() as $spell) {
            $effect = $this->statusEffectOf($spell);
            if ($effect === null) {
                continue;
            }

            if (!DepositLaw::deposits($spell->resolveIntent($effect->getType()), $spell->resolveScope($effect->getType()))) {
                continue;
            }

            if ($effect->getDuration() !== DepositLaw::durationFor($effect->getDuration())) {
                $moved[] = sprintf('%s : duree relevee de %d a %d', $effect->getSlug(), $effect->getDuration(), DepositLaw::durationFor($effect->getDuration()));
            }

            if ($effect->getChance() !== 100) {
                $moved[] = sprintf('%s : chance %d contournee par le depot', $effect->getSlug(), $effect->getChance());
            }
        }

        self::assertSame(
            [],
            $moved,
            "Le depot deplace des valeurs livrees :\n" . implode("\n", array_unique($moved))
        );
    }

    private function statusEffectOf(Spell $spell): ?StatusEffect
    {
        $slug = $spell->getStatusEffectSlug();
        if ($slug === null) {
            return null;
        }

        return $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => $slug]);
    }
}
