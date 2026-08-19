<?php

namespace App\Tests\Integration\Fight;

use App\Entity\Game\Item;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\Enum\SpellIntent;
use App\Enum\SpellScope;
use App\GameEngine\Balance\MendingAnchor;
use App\GameEngine\Balance\VitalityLaw;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * La grille des soins, tenue sur les vraies donnees (ARC-20c-b).
 *
 * GAME_VITALITY § 8, invariant 10 : *un soin de palier n rend la meme part de
 * barre a tous les paliers*. ARC-20a a pose l'ancre et mesure l'ecart (les
 * soins livres valaient 1 a 12 points sur une barre de 96 a 880) ; ce contrat
 * verifie que la grille est **appliquee** — et qu'elle le reste, parce qu'un
 * soin ajoute demain a la main serait exactement le dosage que la derivation
 * existe pour empecher.
 */
class MendingGridContractTest extends AbstractIntegrationTestCase
{
    /**
     * Tout geste dont l'intention est le soin rend le quart de la barre de son
     * palier — et la part ne depend donc pas du palier (invariant 10).
     *
     * Les exclusions sont les regles du canon, pas des exceptions de confort :
     * un geste qui blesse et rend un peu de vie n'est **pas** un soin (l'ordre
     * des questions d'ARC-11a — le degat d'abord), et une provision de groupe
     * n'a pas de composante directe (§ 7 bis : le direct est l'urgence, le
     * depot la provision — un geste ne fait pas les deux).
     */
    public function testEveryDirectHealSitsOnTheAnchor(): void
    {
        $offenders = [];
        $measured = 0;

        foreach ($this->em->getRepository(Spell::class)->findAll() as $spell) {
            if ($this->intentOf($spell) !== SpellIntent::Heal) {
                continue;
            }

            if ($spell->resolveScope($this->statusTypeOf($spell)) === SpellScope::Group) {
                continue;
            }

            ++$measured;
            $expected = MendingAnchor::directHealFor($this->tierOf($spell));
            if ((int) $spell->getHeal() !== $expected) {
                $offenders[] = sprintf('%s (palier %d) : rend %d, la grille dit %d', $spell->getSlug(), $spell->getLevel(), (int) $spell->getHeal(), $expected);
            }
        }

        self::assertGreaterThan(30, $measured, 'Presque aucun soin mesure : le contrat ne tient rien.');
        self::assertSame([], $offenders, "Ces soins ne sont pas sur la grille :\n" . implode("\n", $offenders));
    }

    /**
     * Une provision de groupe n'a pas de composante directe.
     *
     * Sa valeur entiere est le depot — derive au lancer, du palier du geste
     * (`SpellApplicator::depositedValue`), parce que la fiche de statut est
     * **partagee** entre paliers. Lire `healPerTurn` sur la fiche donnerait la
     * meme provision a la Maree (palier 2) et a la Grande Maree (palier 4) —
     * le defaut des gestes partages de monstre, transpose aux soins.
     */
    public function testAGroupProvisionCarriesNoDirectHeal(): void
    {
        $measured = 0;

        foreach ($this->em->getRepository(Spell::class)->findAll() as $spell) {
            if ($this->intentOf($spell) !== SpellIntent::Heal) {
                continue;
            }
            if ($spell->resolveScope($this->statusTypeOf($spell)) !== SpellScope::Group) {
                continue;
            }

            ++$measured;
            self::assertSame(
                0,
                (int) $spell->getHeal(),
                sprintf('%s : une provision de groupe qui soigne aussi en direct fait l\'urgence et la provision a la fois.', $spell->getSlug()),
            );
        }

        self::assertGreaterThan(0, $measured, 'Aucune provision de groupe chargee : le contrat ne mesure rien.');
    }

    /**
     * Ce que la Maree et la Grande Maree deposent n'est plus la meme chose.
     *
     * C'est la mesure qui justifie la derivation au lancer : deux gestes de
     * paliers differents qui partagent leur fiche de statut doivent quand meme
     * provisionner differemment.
     */
    public function testTwoTiersOfTheSameProvisionDepositDifferently(): void
    {
        $maree = $this->em->getRepository(Spell::class)->findOneBy(['slug' => 'maree']);
        $grandeMaree = $this->em->getRepository(Spell::class)->findOneBy(['slug' => 'grande-maree']);
        self::assertNotNull($maree);
        self::assertNotNull($grandeMaree);

        $marejPerTurn = MendingAnchor::depositPerTurnFor($maree->getLevel());
        $grandePerTurn = MendingAnchor::depositPerTurnFor(min($grandeMaree->getLevel(), VitalityLaw::LAST_TIER));

        self::assertGreaterThan(
            $marejPerTurn * 2,
            $grandePerTurn,
            'La Grande Maree ne provisionne plus sensiblement davantage que la Maree : la fiche partagee a repris la main.',
        );
    }

    /**
     * L'echelle de potions : un produit par palier, et chacun sur la grille.
     *
     * *L'obsolescence est une fonctionnalite* — et l'echelle est sa reponse,
     * comme les paliers d'outil d'OBJ-06. L'alchimiste a desormais un produit
     * a chaque palier au lieu d'un seul qui se perime.
     */
    public function testThePotionLadderCoversTheFourTiersOnTheGrid(): void
    {
        $ladder = [
            1 => 'healing-potion-small',
            2 => 'healing-potion-medium',
            3 => 'healing-potion-major',
            4 => 'healing-potion-supreme',
        ];

        foreach ($ladder as $tier => $slug) {
            $item = $this->em->getRepository(Item::class)->findOneBy(['slug' => $slug]);
            self::assertNotNull($item, sprintf('Le barreau %d de l\'echelle de potions (%s) manque.', $tier, $slug));

            self::assertSame(
                MendingAnchor::directHealFor($tier),
                $this->healAmountOf($item),
                sprintf('%s ne rend pas ce que la grille dit au palier %d.', $slug, $tier),
            );
        }
    }

    private function intentOf(Spell $spell): ?SpellIntent
    {
        return $spell->resolveIntent($this->statusTypeOf($spell));
    }

    private function statusTypeOf(Spell $spell): ?string
    {
        $slug = $spell->getStatusEffectSlug();
        if ($slug === null) {
            return null;
        }

        return $this->em->getRepository(StatusEffect::class)->findOneBy(['slug' => $slug])?->getType();
    }

    /**
     * Le palier d'un geste, borne comme la barre l'est (ARC-20a) : le
     * palier 5 des materia n'est pas un cinquieme palier de contenu.
     */
    private function tierOf(Spell $spell): int
    {
        return max(VitalityLaw::FIRST_TIER, min(VitalityLaw::LAST_TIER, $spell->getLevel()));
    }

    /**
     * Ce qu'une potion rend — par son sort, ou par son effet direct.
     */
    private function healAmountOf(Item $item): int
    {
        $spell = $item->getSpell();
        if ($spell !== null && (int) $spell->getHeal() > 0) {
            return (int) $spell->getHeal();
        }

        $effect = json_decode((string) $item->getEffect(), true);
        if (\is_array($effect) && ($effect['action'] ?? null) === 'heal') {
            return (int) ($effect['amount'] ?? 0);
        }

        return 0;
    }
}
