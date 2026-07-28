<?php

namespace App\GameEngine\Crafting;

use App\Entity\App\CraftOrder;
use App\Entity\App\Guild;
use App\Entity\App\Player;
use App\Entity\Game\Recipe;
use App\Enum\GuildRank;
use App\GameEngine\Guild\GuildManager;
use App\Repository\CraftOrderRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * La commande de la semaine (RET-03).
 *
 * « On compte sur moi » a cadence fixe, pour le prix d'un canal *guilde* sur un
 * systeme deja livre : l'escrow, la reputation d'artisan et l'expiration de
 * `CraftOrderManager` sont reutilises tels quels. On ne reecrit rien.
 *
 * Trois regles font la difference avec une commande ordinaire, et chacune
 * repond a une facon de rater le rendez-vous :
 *
 * 1. **Une seule commande vivante par semaine.** C'est un rendez-vous, pas un
 *    tableau infini. Un tableau qui se remplit ne se regarde plus.
 * 2. **Le tresor peut payer.** Une commande que seul un officier riche peut
 *    poser n'est pas une commande de guilde ; c'est un service qu'il rend.
 * 3. **Elle n'est prenable que par un membre**, et n'apparait jamais au tableau
 *    regional. Un rendez-vous interne visible de tous n'est plus interne.
 */
class GuildCraftOrderManager
{
    /**
     * Commandes de guilde **vivantes** autorisees sur la fenetre.
     *
     * Une seule : le rendez-vous tient sa valeur de sa rarete. Le jour ou une
     * guilde tres active en demandera deux, ce nombre bougera ici — pas dans
     * une condition perdue au milieu d'une methode.
     */
    public const WEEKLY_CAP = 1;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CraftOrderManager $orderManager,
        private readonly CraftOrderRepository $orderRepository,
        private readonly GuildManager $guildManager,
    ) {
    }

    /**
     * Poste la commande de la semaine.
     *
     * @param list<\App\Entity\App\PlayerItem> $materials
     * @param bool                             $fromTreasury la commission sort du tresor plutot que de la bourse de l'officier
     *
     * @throws \InvalidArgumentException si le poste, la cadence ou les fonds ne suivent pas
     */
    public function createGuildOrder(
        Player $officer,
        Recipe $recipe,
        array $materials,
        int $commission,
        bool $fromTreasury = false,
        ?string $minQuality = null,
        ?\DateTimeImmutable $now = null,
    ): CraftOrder {
        $guild = $this->guildManager->getPlayerGuild($officer);
        if ($guild === null) {
            throw new \InvalidArgumentException('Seul un membre de guilde peut poster une commande de guilde.');
        }

        if (!$this->canPost($officer, $guild)) {
            throw new \InvalidArgumentException('Seul un officier ou le chef de guilde peut poster la commande de la semaine.');
        }

        $now ??= new \DateTimeImmutable();
        if ($this->activeThisWeek($guild, $now) >= self::WEEKLY_CAP) {
            throw new \InvalidArgumentException('La commande de la semaine est deja posee.');
        }

        // Le tresor paie **avant** la creation, exactement comme la bourse du
        // commanditaire : si les fonds ne suivent pas, rien n'est engage. Les
        // Gils sont ensuite reverses a l'officier, qui les remet aussitot en
        // escrow — le detour garde `createOrder()` seul maitre de l'escrow,
        // plutot que d'en ouvrir un second chemin qu'il faudrait maintenir.
        if ($fromTreasury) {
            if ($guild->getGilsTreasury() < $commission) {
                throw new \InvalidArgumentException('Le tresor de guilde ne couvre pas cette commission.');
            }
            $guild->addGilsTreasury(-$commission);
            $officer->addGils($commission);
        }

        try {
            $order = $this->orderManager->createOrder($officer, $recipe, $materials, $commission, $minQuality);
        } catch (\InvalidArgumentException $e) {
            // Le refus vient d'ailleurs (materiaux, plafond personnel, fonds) :
            // le tresor doit retrouver ses Gils, sinon un echec de validation
            // deviendrait une fuite silencieuse de monnaie de guilde.
            if ($fromTreasury) {
                $officer->removeGils($commission);
                $guild->addGilsTreasury($commission);
            }

            throw $e;
        }

        $order->setGuild($guild);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * Un membre, et seulement un membre, peut servir la commande de sa guilde.
     */
    public function canClaim(Player $crafter, CraftOrder $order): bool
    {
        $guild = $order->getGuild();
        if ($guild === null) {
            return true;
        }

        return $this->guildManager->getPlayerGuild($crafter) === $guild;
    }

    public function canPost(Player $player, Guild $guild): bool
    {
        $membership = $this->guildManager->getPlayerMembership($player);
        if ($membership === null || $membership->getGuild() !== $guild) {
            return false;
        }

        return \in_array($membership->getRank(), [GuildRank::Leader, GuildRank::Officer], true);
    }

    /**
     * Commandes de guilde vivantes sur les sept derniers jours.
     *
     * La fenetre est **glissante**, pas calee sur la semaine calendaire : une
     * commande posee un dimanche soir ne doit pas liberer la place douze heures
     * plus tard.
     */
    public function activeThisWeek(Guild $guild, ?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();

        return $this->orderRepository->countActiveForGuildSince($guild, $now->modify('-7 days'));
    }

    /**
     * @return list<CraftOrder>
     */
    public function openOrdersFor(Guild $guild): array
    {
        /** @var list<CraftOrder> $orders */
        $orders = $this->orderRepository->findOpenForGuild($guild);

        return $orders;
    }
}
