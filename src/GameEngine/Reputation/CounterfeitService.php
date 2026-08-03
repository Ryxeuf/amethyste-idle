<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Entity\Game\Faction;
use App\Entity\Game\Item;
use App\Enum\Purity;
use App\Enum\ReputationTier;
use App\Helper\InventoryHelper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * La contrefacon des Ruelles (FAC-07).
 *
 * GAME_WORLD § 12.4 : « une contrefacon marche neuf fois et vous trahit a la
 * dixieme. » Le compteur est tire a la creation, cache, decremente a chaque
 * lancement ; au declenchement le sort echoue au pire moment, le contrecoup
 * frappe le lanceur, et la materia se brise en amethyste Trouble.
 *
 * Les trois capacites de l'echelle : l'**œil du faussaire** (Honore) voit une
 * contrefacon sans la toucher ; le **desamorcage** (Revere) la demonte en
 * composants ; la **main du faussaire** (Revere) en fabrique — et son seul
 * debouche est un contact PNJ (les contrats de placement, FAC-08) : jamais un
 * joueur, tous les canaux entre joueurs sont verrouilles.
 */
class CounterfeitService
{
    /**
     * Ce que laisse une materia brisee, en plus de l'amethyste Trouble : la
     * matiere premiere de la main du faussaire.
     */
    public const SHARDS_SLUG = 'materia-shards';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ShadowsMarketCatalog $catalog,
        private readonly InventoryHelper $inventoryHelper,
    ) {
    }

    /**
     * Marque une instance comme contrefaite et tire son compteur cache.
     * `identified` : le faussaire connait son œuvre ; le butin, non.
     */
    public function mark(PlayerItem $materia, bool $identified): void
    {
        $materia->setCounterfeit(true);
        $materia->setCounterfeitIdentified($identified);
        $materia->setCounterfeitCharges(
            $this->roll($this->catalog->counterfeitChargesMin(), $this->catalog->counterfeitChargesMax()),
        );
    }

    /**
     * Le butin sort-il contrefait ? Tire la chance du catalogue et marque
     * l'instance, non identifiee — l'unique source involontaire du monde.
     */
    public function maybeMarkLoot(PlayerItem $materia): bool
    {
        if ($this->roll(1, 100) > $this->catalog->counterfeitLootChancePercent()) {
            return false;
        }

        $this->mark($materia, false);

        return true;
    }

    /**
     * L'œil du faussaire : ce joueur voit-il que cette instance est fausse ?
     * L'etat identifie se voit de tous (le faussaire connait son œuvre) ;
     * au-dela, il faut le palier Honore chez la Confrerie.
     */
    public function eyeSees(Player $player, PlayerItem $item): bool
    {
        if (!$item->isCounterfeit()) {
            return false;
        }

        return $item->isCounterfeitIdentified()
            || $this->tierAtLeast($player, $this->catalog->counterfeitEyeTier());
    }

    /**
     * Un lancement de plus au compteur cache. Rend `true` la fois ou la
     * trahison se declenche — l'appelant orchestre l'echec, le contrecoup et
     * le bris via {@see betray()}.
     */
    public function consumeCharge(PlayerItem $materia): bool
    {
        if (!$materia->isCounterfeit()) {
            return false;
        }

        $charges = $materia->getCounterfeitCharges() ?? 1;
        --$charges;
        $materia->setCounterfeitCharges(max(0, $charges));

        return $charges <= 0;
    }

    /**
     * La trahison : la materia quitte son emplacement, se brise en amethyste
     * Trouble (plus les eclats — la matiere de la main du faussaire), et le
     * contrecoup frappe le lanceur. Rend les messages de combat.
     *
     * La vie max effective vient de l'appelant : le calculateur de stats
     * traverse l'enchantement puis l'artisanat, et l'artisanat a besoin de ce
     * service (la main du faussaire) — l'injecter ici bouclerait le conteneur.
     *
     * @return list<string>
     */
    public function betray(Player $player, PlayerItem $materia, ?Slot $slot, int $effectiveMaxLife): array
    {
        $name = $materia->getGenericItem()->getName();

        if (null !== $slot) {
            $slot->setItemSet(null);
            $this->entityManager->persist($slot);
        }
        $this->entityManager->remove($materia);

        $this->grantItem($player, CrystalBuybackFloor::CRYSTAL_SLUG, Purity::Trouble);
        $this->grantItem($player, self::SHARDS_SLUG, null);

        $backlash = max(1, intdiv(
            $effectiveMaxLife * $this->catalog->counterfeitBacklashPercent(),
            100,
        ));
        $player->setLife(max(0, $player->getLife() - $backlash));
        $this->entityManager->persist($player);

        return [
            sprintf('%s se fige... et se brise ! C\'etait une contrefacon.', $name),
            sprintf('Le contrecoup frappe %s (-%d PV).', $player->getName(), $backlash),
            'Il ne reste qu\'un cristal trouble et des eclats.',
        ];
    }

    /**
     * Le desamorcage (Revere) : demonte une contrefacon **vue** — identifiee,
     * ou percee par l'œil — en amethyste Trouble et essence. Demonter
     * proprement vaut mieux que laisser trahir.
     *
     * @return array{essence: int}
     *
     * @throws ShadowsMarketException si le palier, l'etat ou l'objet refuse (cle en message)
     */
    public function defuse(Player $player, PlayerItem $materia): array
    {
        if (!$this->tierAtLeast($player, $this->catalog->counterfeitDefuseTier())) {
            throw new ShadowsMarketException('game.shadows.counterfeit.error.tier');
        }
        if (!$this->eyeSees($player, $materia)) {
            // Ni identifiee ni percee a jour : on ne desamorce pas ce qu'on
            // ne voit pas — et le refus ne revele rien.
            throw new ShadowsMarketException('game.shadows.counterfeit.error.not_seen');
        }
        if (0 !== $materia->getGear() || null !== $materia->getSlotSet()) {
            throw new ShadowsMarketException('game.shadows.counterfeit.error.socketed');
        }

        $this->entityManager->remove($materia);
        $this->grantItem($player, CrystalBuybackFloor::CRYSTAL_SLUG, Purity::Trouble);

        $essence = $this->catalog->counterfeitDefuseEssence();
        $player->addEssence($essence);
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return ['essence' => $essence];
    }

    /**
     * La main du faussaire fabrique-t-elle pour ce joueur ? Le savoir ne
     * s'apprend qu'aux Ruelles : le palier Revere, jamais un arbre.
     */
    public function canForge(Player $player): bool
    {
        return $this->tierAtLeast($player, $this->catalog->counterfeitForgeTier());
    }

    public function isForgeRecipe(string $recipeSlug): bool
    {
        return $recipeSlug === $this->catalog->counterfeitForgeRecipeSlug();
    }

    private function tierAtLeast(Player $player, ReputationTier $tier): bool
    {
        $faction = $this->entityManager->getRepository(Faction::class)
            ->findOneBy(['slug' => ShadowsApproach::FACTION_SLUG]);
        if (null === $faction) {
            return false;
        }

        $line = $this->entityManager->getRepository(PlayerFaction::class)->findOneBy([
            'player' => $player,
            'faction' => $faction,
        ]);

        return null !== $line && $line->getReputation() >= $tier->threshold();
    }

    private function grantItem(Player $player, string $slug, ?Purity $purity): void
    {
        $item = $this->entityManager->getRepository(Item::class)->findOneBy(['slug' => $slug]);
        if (null === $item) {
            // Fixture absente (base partielle) : le bris ne doit jamais
            // casser le combat — la contrepartie manque, le jeu continue.
            return;
        }

        $playerItem = new PlayerItem();
        $playerItem->setGenericItem($item);
        $playerItem->setNbUsages($item->getNbUsages());
        if (null !== $purity) {
            $playerItem->setPurity($purity);
        }
        $this->inventoryHelper->addItem($playerItem, false);
    }

    /**
     * Protegee pour que les tests fixent le tirage.
     */
    protected function roll(int $min, int $max): int
    {
        return random_int($min, max($min, $max));
    }
}
