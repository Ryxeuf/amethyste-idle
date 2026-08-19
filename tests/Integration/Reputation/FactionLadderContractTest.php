<?php

namespace App\Tests\Integration\Reputation;

use App\Entity\App\Zone;
use App\Entity\Game\Faction;
use App\Entity\Game\FactionReward;
use App\Enum\FactionRewardForm;
use App\Enum\ReputationTier;
use App\GameEngine\Reputation\PatronageBonusResolver;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * La loi laterale et les cinq portes (FAC-09).
 *
 * GAME_WORLD § 6.4 c : *« un palier de reputation ouvre des portes ; il n'empile
 * jamais de la puissance »* — et § 12.5 : *« chaque Exalte ouvre une porte
 * quelque part »*.
 */
class FactionLadderContractTest extends AbstractIntegrationTestCase
{
    /**
     * **Seul le patronage nomme une statistique.**.
     *
     * C'est la loi du jalon, et elle se lit sur les donnees plutot que sur le
     * code : `rewardData` est un JSON libre, donc rien n'empeche techniquement
     * une remise d'y ecrire `stat: damage`. Ce qui l'empeche est ce test —
     * et il porte sur la **forme**, pas sur la liste des recompenses livrees :
     * une dixieme forme ajoutee demain sera laterale par defaut, et elle sera
     * verifiee sans qu'on revienne ici.
     */
    public function testOnlyPatronageMayNameAStat(): void
    {
        $offenders = [];

        foreach ($this->em->getRepository(FactionReward::class)->findAll() as $reward) {
            $form = $reward->getForm();
            if ($form->mayNameAStat()) {
                continue;
            }

            foreach (['stat', 'extra_stat'] as $key) {
                $named = $reward->getRewardData()[$key] ?? null;
                if (\is_string($named) && \in_array($named, PatronageBonusResolver::STATS, true)) {
                    $offenders[] = sprintf('%s (%s) nomme « %s »', $reward->getLabel(), $form->value, $named);
                }
            }
        }

        self::assertSame([], $offenders, "Une recompense laterale accorde une statistique :\n" . implode("\n", $offenders));
    }

    /**
     * **La forme se lit, ou elle leve.**.
     *
     * Une forme inconnue serait affichee sur l'ecran des factions sans jamais
     * rien faire — la lecon d'ARC-12a : un cadeau muet se lit comme un choix de
     * design.
     */
    public function testEveryShippedRewardHasAReadableForm(): void
    {
        $rewards = $this->em->getRepository(FactionReward::class)->findAll();
        self::assertNotSame([], $rewards, 'Aucune recompense chargee : la loi ne verifierait rien.');

        foreach ($rewards as $reward) {
            self::assertContains($reward->getForm()->value, FactionRewardForm::values());
        }
    }

    /**
     * **Chaque maison a au moins une recompense laterale.**.
     *
     * C'est ce que la mesure a trouve, et c'est pire que l'ecart annonce : les
     * 12 recompenses livrees etaient 3 remises et 9 statistiques, si bien que
     * quatre maisons sur cinq n'avaient **rien** a offrir a qui ne portait pas
     * leurs couleurs — et la Fonderie n'avait rien du tout. Les cinq portes le
     * referment d'un coup, et ce test empeche d'y revenir.
     */
    public function testEveryHouseOffersSomethingLateral(): void
    {
        $empty = [];

        foreach ($this->em->getRepository(Faction::class)->findAll() as $faction) {
            $lateral = array_filter(
                $this->em->getRepository(FactionReward::class)->findBy(['faction' => $faction]),
                static fn (FactionReward $reward): bool => $reward->getForm()->isLateral(),
            );

            if ($lateral === []) {
                $empty[] = $faction->getSlug();
            }
        }

        self::assertSame([], $empty, sprintf(
            'Ces maisons n\'offrent que de la puissance, donc rien a qui ne porte pas leurs couleurs : %s.',
            implode(', ', $empty),
        ));
    }

    /**
     * **Les cinq portes, et l'exclusivite.**.
     *
     * Une porte par maison, jamais deux pour la meme : deux portes chez le meme
     * patron feraient de l'exaltation une monnaie, quand elle doit etre un
     * aboutissement. Et **aucune maison sans porte** — la symetrie est ce que
     * § 12.5 appelle « voulue ».
     */
    public function testEachHouseOpensExactlyOneDoor(): void
    {
        $doors = array_filter(
            $this->em->getRepository(Zone::class)->findAll(),
            static fn (Zone $zone): bool => $zone->isGuarded(),
        );

        self::assertCount(5, $doors, 'Le monde ne porte pas exactement cinq portes.');

        $byFaction = [];
        foreach ($doors as $door) {
            self::assertSame(Zone::TYPE_INTERIOR, $door->getType(), sprintf('« %s » n\'est pas un interieur.', $door->getName()));
            self::assertSame(ReputationTier::Exalte, $door->getRequiredTier(), sprintf('« %s » s\'ouvre avant l\'exaltation.', $door->getName()));
            self::assertTrue($door->isSafe(), sprintf('« %s » n\'est pas sure : une porte est un quartier, pas un terrain de chasse.', $door->getName()));
            self::assertNull($door->getMapX(), sprintf('« %s » figure sur la carte du monde : elle n\'est plus cachee.', $door->getName()));

            $byFaction[(string) $door->getRequiredFaction()][] = $door->getSlug();
        }

        foreach ($byFaction as $slug => $slugs) {
            self::assertCount(1, $slugs, sprintf('La maison « %s » ouvre deux portes : %s.', $slug, implode(', ', $slugs)));
        }

        $houses = array_map(
            static fn (Faction $faction): string => $faction->getSlug(),
            $this->em->getRepository(Faction::class)->findAll(),
        );
        sort($houses);
        $guarding = array_keys($byFaction);
        sort($guarding);

        self::assertSame($houses, $guarding, 'Une maison au moins n\'ouvre aucune porte.');
    }

    /**
     * La garde d'une zone et la recompense qui l'annonce **designent la meme
     * porte**.
     *
     * La zone porte la regle, la recompense l'annonce a l'ecran des factions :
     * deux ecritures pour une seule verite, donc deux ecritures qui peuvent
     * diverger. Elles ne le peuvent plus.
     */
    public function testTheAnnouncedDoorIsTheGuardedOne(): void
    {
        $announced = [];
        foreach ($this->em->getRepository(FactionReward::class)->findAll() as $reward) {
            if ($reward->getForm() !== FactionRewardForm::Access) {
                continue;
            }

            $slug = $reward->getRewardData()['zone'] ?? null;
            self::assertIsString($slug, sprintf('La recompense « %s » ouvre un acces sans nommer de zone.', $reward->getLabel()));

            $zone = $this->em->getRepository(Zone::class)->findOneBy(['slug' => $slug]);
            self::assertNotNull($zone, sprintf('La recompense « %s » ouvre « %s », qui n\'existe pas.', $reward->getLabel(), $slug));
            self::assertSame(
                $reward->getFaction()->getSlug(),
                $zone->getRequiredFaction(),
                sprintf('« %s » est annoncee par %s et gardee par %s.', $slug, $reward->getFaction()->getSlug(), (string) $zone->getRequiredFaction()),
            );
            self::assertSame($reward->getRequiredTier(), $zone->getRequiredTier());

            $announced[] = $slug;
        }

        self::assertCount(5, $announced, 'Les cinq portes ne sont pas toutes annoncees a l\'ecran des factions.');
    }
}
