<?php

namespace App\GameEngine\Shop;

use App\Entity\App\Player;
use App\Entity\App\PlayerShop;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Region\PlayerRegionResolver;
use App\Repository\PlayerShopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Etals de place : les emplacements supplementaires sont un actif de cite
 * (ECO-13).
 *
 * `GAME_PRINCIPLES` §6 laissait la question ouverte : les emplacements
 * d'echoppe sont-ils **lies au housing** du joueur, ou un **actif de ville**
 * attribue par la guilde controlante ? Les deux reponses ont un defaut.
 *
 * Tout adosser au housing prive le controle de cite de toute prise sur le
 * commerce — la guilde encaisse la taxe des ventes, mais ne decide de rien. Ne
 * rien adosser au housing rendrait l'echoppe sans adresse, et la demeure sans
 * usage.
 *
 * La reponse retenue **coupe la poire selon ce que chaque partie apporte** :
 *
 * - La **demeure** donne l'echoppe et ses six emplacements de base. C'est
 *   l'adresse : elle appartient au joueur, et nul ne peut la lui retirer.
 * - La **cite** loue les etals au-dela. Ils sont **en nombre limite par
 *   ville** et se paient a la guilde controlante. C'est la que le controle de
 *   cite prend des dents : la place du marche est finie, et celui qui tient la
 *   ville touche le bail.
 *
 * Une echoppe n'est donc jamais fermee par une guilde hostile — elle est
 * simplement contenue. La difference compte : perdre son gagne-pain parce
 * qu'une guilde adverse a gagne une saison serait une punition qu'on ne peut
 * pas anticiper.
 */
class ShopStallService
{
    /**
     * Etals disponibles a la location dans une cite, tous artisans confondus.
     *
     * La rarete **est** l'actif. Sans plafond, louer un etal ne serait qu'un
     * gold sink de plus, et la guilde controlante n'aurait rien a arbitrer.
     *
     * Le plafond par echoppe (`PlayerShop::MAX_SLOTS`) fait le reste : une
     * seule enseigne ne peut louer que 18 des 24 etals, donc **aucun artisan
     * ne peut monopoliser la place a lui seul**. Il en faut au moins deux pour
     * la saturer — la propriete tombe du croisement des deux bornes, et un
     * test la verrouille pour qu'un futur reglage ne la perde pas en silence.
     */
    public const STALLS_PER_CITY = 24;

    /**
     * Cout du bail du n-ieme etal d'une echoppe, en Gils.
     *
     * Croissant : le premier etal supplementaire est accessible, le dixieme se
     * merite. Une progression lineaire aurait laisse les plus riches rafler la
     * place du marche en une transaction.
     */
    public const STALL_BASE_PRICE = 5_000;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerShopRepository $shopRepository,
        private readonly PlayerRegionResolver $regionResolver,
        private readonly TownControlManager $townControlManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Prix du prochain etal pour cette echoppe.
     */
    public function nextStallPrice(PlayerShop $shop): int
    {
        $bought = max(0, $shop->getSlotCount() - PlayerShop::DEFAULT_SLOTS);

        return self::STALL_BASE_PRICE * ($bought + 1);
    }

    /**
     * Etals encore libres sur la place, toutes echoppes confondues.
     */
    public function remainingStalls(PlayerShop $shop): int
    {
        $taken = 0;
        foreach ($this->shopRepository->findInZone($shop->getZone()) as $other) {
            $taken += max(0, $other->getSlotCount() - PlayerShop::DEFAULT_SLOTS);
        }

        return max(0, self::STALLS_PER_CITY - $taken);
    }

    /**
     * Loue un etal supplementaire.
     *
     * @throws \InvalidArgumentException si le bail est refuse
     */
    public function leaseStall(Player $player, PlayerShop $shop): int
    {
        if ($shop->getOwner()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Cette echoppe n\'est pas la votre.');
        }

        if ($shop->getSlotCount() >= PlayerShop::MAX_SLOTS) {
            throw new \InvalidArgumentException('Votre echoppe a atteint sa taille maximale.');
        }

        if ($this->remainingStalls($shop) < 1) {
            throw new \InvalidArgumentException('Il ne reste aucun etal libre sur cette place.');
        }

        $price = $this->nextStallPrice($shop);
        if (!$player->removeGils($price)) {
            throw new \InvalidArgumentException(sprintf('Il vous faut %s Gils pour ce bail.', number_format($price, 0, ',', ' ')));
        }

        $shop->setSlotCount($shop->getSlotCount() + 1);
        $this->payLease($shop, $price);

        $this->entityManager->flush();

        return $price;
    }

    /**
     * Verse le bail a la guilde controlante — ou constate la destruction des
     * Gils quand la cite n'a pas de maitre.
     *
     * Meme regle qu'a l'hotel des ventes (ECO-04) et en echoppe (ECO-11) : une
     * cite sans maitre ne redistribue rien, et les Gils **sortent du jeu**. On
     * le journalise, sans quoi une refonte pourrait croire a une fuite.
     */
    private function payLease(PlayerShop $shop, int $price): void
    {
        $region = $this->regionResolver->resolveForZone($shop->getZone());
        $ruler = null !== $region ? $this->townControlManager->getControllingGuild($region) : null;

        if (null === $ruler) {
            $this->logger->info('Stall lease burned (city has no ruling guild)', [
                'zone' => $shop->getZone()->getSlug(),
                'amount' => $price,
            ]);

            return;
        }

        $ruler->addGilsTreasury($price);

        $this->logger->info('Stall lease paid to ruling guild', [
            'zone' => $shop->getZone()->getSlug(),
            'guild' => $ruler->getName(),
            'amount' => $price,
        ]);
    }
}
