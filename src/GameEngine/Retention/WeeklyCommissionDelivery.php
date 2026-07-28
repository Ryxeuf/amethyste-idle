<?php

namespace App\GameEngine\Retention;

use App\Entity\App\Player;
use App\Entity\App\PlayerWeeklyCommission;
use App\Enum\WeeklyCommissionReward;
use App\Enum\WeeklyCommissionStatus;
use App\GameEngine\Settlement\SettlementDepositService;
use App\Repository\PlayerWeeklyCommissionRepository;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * La livraison de la commission au foyer (RET-02b).
 *
 * **Ce qu'on rapporte se depose quelque part.** C'est toute la difference entre
 * une quete hebdomadaire et une commission : le travail de la semaine ne
 * disparait pas dans un guichet abstrait, il fait monter une ville que d'autres
 * font monter aussi. C'est ce qui branche le joueur **solo** sur le chantier
 * collectif sans lui demander de rejoindre une guilde (GAME_PROGRESSION § 3).
 *
 * **Il faut y aller.** La livraison exige d'etre dans la zone visee. Sans ce
 * deplacement, la zone de livraison ne serait qu'une decoration sur une carte,
 * et le rendez-vous n'aurait pas de lieu.
 *
 * **Le refus n'est jamais muet.** Chaque blocage rend une clef de traduction :
 * un bouton grise sans explication est la facon la plus sure de faire croire a
 * un bug — meme regle que le verdict de `SettlementGate` (FOY-05).
 */
class WeeklyCommissionDelivery
{
    public function __construct(
        private readonly PlayerWeeklyCommissionRepository $commissionRepository,
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementDepositService $depositService,
        private readonly WeeklyCommissionTemplateLoader $loader,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * La commission en cours du joueur, ou `null` s'il n'en a pas cette semaine.
     */
    public function current(Player $player, ?\DateTimeImmutable $now = null): ?PlayerWeeklyCommission
    {
        $now ??= new \DateTimeImmutable();

        return $this->commissionRepository->findCurrent($player, WeeklyCommissionGenerator::weekKey($now));
    }

    /**
     * Ce qui empeche la livraison, ou `null` si elle est possible.
     *
     * @return string|null clef de traduction
     */
    public function blocker(Player $player, ?PlayerWeeklyCommission $commission = null): ?string
    {
        $commission ??= $this->current($player);

        if ($commission === null) {
            return 'game.commission.error.none';
        }

        if ($commission->getStatus() !== WeeklyCommissionStatus::Open) {
            return 'game.commission.error.closed';
        }

        if (!$commission->isComplete()) {
            return 'game.commission.error.incomplete';
        }

        $zone = $commission->getDeliveryZone();
        if ($zone === null) {
            // Aucun foyer n'existait au tirage. La commission n'est pas perdue :
            // elle attend qu'une ville en ait un, et le joueur n'a pas a payer
            // pour un etat du monde qui ne le regarde pas.
            return 'game.commission.error.no_settlement';
        }

        if ($player->getCurrentZone()?->getId() !== $zone->getId()) {
            return 'game.commission.error.elsewhere';
        }

        if ($this->settlementRepository->findOneByZone($zone) === null) {
            // Le foyer a disparu entre le tirage et la livraison. Cas theorique,
            // mais le refuser ici vaut mieux qu'un depot silencieusement perdu.
            return 'game.commission.error.no_settlement';
        }

        return null;
    }

    /**
     * Livre la commission et rend la recompense choisie.
     *
     * @throws WeeklyCommissionException si la livraison est refusee (clef de traduction en message)
     */
    public function deliver(Player $player, WeeklyCommissionReward $reward, ?\DateTimeImmutable $now = null): WeeklyCommissionDeliveryResult
    {
        $now ??= new \DateTimeImmutable();
        $commission = $this->current($player, $now);

        $blocker = $this->blocker($player, $commission);
        if ($blocker !== null || $commission === null) {
            throw new WeeklyCommissionException($blocker ?? 'game.commission.error.none');
        }

        $rewards = $this->loader->load()['rewards'];

        // Le depot d'abord : c'est la livraison elle-meme, et elle a lieu quelle
        // que soit la recompense demandee. Le Tribut ne l'ajoute pas, il le
        // multiplie — le joueur ne donne pas en plus, il donne **a la place**.
        //
        // Le nombre de grains vit dans `settlements.yaml` avec le reste de la
        // table de depot ; seul le facteur du Tribut appartient a la commission.
        $deposited = $this->depositService->deposit(
            $player,
            'commission',
            $commission->getDeliveryZone(),
            $now,
            $reward === WeeklyCommissionReward::Tribute ? (float) $rewards['tribute_multiplier'] : 1.0,
        );

        $gils = 0;
        $energy = 0;
        if ($reward === WeeklyCommissionReward::Purse) {
            $gils = $rewards['purse_gils'];
            $player->addGils($gils);
        } elseif ($reward === WeeklyCommissionReward::Vigour) {
            $before = $player->getActionEnergy();
            $player->setActionEnergy(min($player->getMaxActionEnergy(), $before + $rewards['vigour_energy']));
            $energy = $player->getActionEnergy() - $before;
        }

        $commission->setStatus(WeeklyCommissionStatus::Delivered);
        $commission->setDeliveredAt($now);
        $commission->setReward($reward);

        $this->entityManager->flush();

        return new WeeklyCommissionDeliveryResult($reward, $deposited, $gils, $energy);
    }
}
