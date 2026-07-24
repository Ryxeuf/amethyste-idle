<?php

namespace App\Tests\Integration\Quest;

use App\Entity\Game\Item;
use App\Entity\Game\Quest;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Garantie d'onboarding (NAR-04) : l'arc `intro` accorde un kit T1 **echangeable**
 * (non lie) qui rend la boucle coeur accessible a un joueur solo, sans dependre
 * d'un autre joueur (protection cold-start).
 */
final class OnboardingKitTest extends AbstractIntegrationTestCase
{
    /**
     * Tous les slugs d'items accordes en recompense sur l'ensemble de l'arc intro.
     *
     * @return string[]
     */
    private function introRewardSlugs(): array
    {
        $questRepository = $this->em->getRepository(Quest::class);

        $slugs = [];
        foreach ($questRepository->findByStoryArc('intro') as $quest) {
            $items = $quest->getRewards()['items'] ?? [];
            foreach ($items as $key => $entry) {
                if (\is_array($entry) && isset($entry['genericItemSlug'])) {
                    $slugs[] = $entry['genericItemSlug'];
                } elseif (\is_string($key)) {
                    // Forme heritee : slug => quantite.
                    $slugs[] = $key;
                }
            }
        }

        return array_values(array_unique($slugs));
    }

    public function testIntroGrantsAnExchangeableWeaponWithAttackSpell(): void
    {
        self::assertContains('short-sword', $this->introRewardSlugs(), 'L\'arc intro doit accorder une arme T1.');

        $weapon = $this->em->getRepository(Item::class)->findOneBy(['slug' => 'short-sword']);
        self::assertNotNull($weapon);

        // Arme principale equipable des le depart (aucun prerequis d'equipement).
        self::assertSame(Item::GEAR_LOCATION_MAIN_WEAPON, $weapon->getGearLocation());
        // Elle porte un sort : l'action d'attaque de la boucle coeur devient resolvable
        // (le PlayerAttackHandler exige une arme equipee dotee d'un sort).
        self::assertNotNull($weapon->getSpell(), 'L\'arme d\'onboarding doit porter un sort d\'attaque.');
        // Echangeable : la definition n'est pas liee.
        self::assertFalse($weapon->isBoundToPlayer(), 'Le kit T1 doit rester echangeable.');
    }

    public function testIntroGrantsAnExchangeableHealConsumable(): void
    {
        self::assertContains('life-potion', $this->introRewardSlugs(), 'L\'arc intro doit accorder un consommable de soin T1.');

        $potion = $this->em->getRepository(Item::class)->findOneBy(['slug' => 'life-potion']);
        self::assertNotNull($potion);
        self::assertFalse($potion->isBoundToPlayer());
    }

    public function testEveryIntroRewardItemIsExchangeable(): void
    {
        $itemRepository = $this->em->getRepository(Item::class);
        $slugs = $this->introRewardSlugs();

        self::assertNotEmpty($slugs, 'L\'arc intro doit accorder au moins un objet.');

        foreach ($slugs as $slug) {
            $item = $itemRepository->findOneBy(['slug' => $slug]);
            self::assertNotNull($item, sprintf('Item de recompense intro introuvable : %s', $slug));
            self::assertFalse(
                $item->isBoundToPlayer(),
                sprintf('La recompense intro « %s » doit rester echangeable (non liee).', $slug)
            );
        }
    }
}
