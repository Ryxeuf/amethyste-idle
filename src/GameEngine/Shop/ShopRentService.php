<?php

namespace App\GameEngine\Shop;

use App\Entity\App\Player;
use App\Entity\App\PlayerShop;
use App\Enum\ShopStatus;
use App\Repository\PlayerShopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Loyer d'echoppe (ECO-11).
 *
 * Le loyer n'est pas qu'un gold sink : c'est le **regulateur du nombre
 * d'echoppes**. Sans lui, chaque artisan en ouvrirait une et l'abandonnerait,
 * et la rue se remplirait de vitrines mortes ou l'on ne trouve rien. Avec lui,
 * tenir echoppe suppose de vendre — donc de tenir un stock a jour.
 *
 * L'impaye **ne confisque rien**, comme pour la demeure (HOU-04) : le rideau
 * tombe, le stock reste en escrow, et tout revient au paiement. On ne prend pas
 * les affaires d'un joueur parce qu'il a manque une echeance.
 */
class ShopRentService
{
    /**
     * Loyer par periode, en Gils.
     *
     * Cale sur celui de la demeure (500 par semaine) : une echoppe est un
     * commerce, pas un logement, et elle rapporte. Le double se justifie et
     * reste finançable par quelques ventes.
     */
    public const RENT_AMOUNT = 1_000;

    public const RENT_PERIOD_DAYS = 7;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerShopRepository $shopRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Premiere echeance, posee a l'ouverture.
     *
     * La premiere periode est offerte : on ne fait pas payer un loyer le jour
     * meme ou l'on vient d'ouvrir, sans avoir rien vendu.
     */
    public function scheduleFirstRent(PlayerShop $shop): void
    {
        $shop->setRentDueAt((new \DateTimeImmutable())->modify(sprintf('+%d days', self::RENT_PERIOD_DAYS)));
    }

    /**
     * Paiement volontaire par le proprietaire.
     *
     * L'echeance est reportee a partir de la **precedente** et non de
     * « maintenant » : payer en retard ne doit pas offrir une periode pleine,
     * sinon attendre serait rentable.
     */
    public function payRent(Player $player, PlayerShop $shop): void
    {
        if ($shop->getOwner()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Cette echoppe n\'est pas la votre.');
        }

        // La caisse paie d'abord : l'echoppe doit pouvoir s'entretenir seule,
        // sans que son proprietaire ait a puiser dans sa bourse a chaque
        // echeance.
        $fromVault = min(self::RENT_AMOUNT, $shop->getVaultGils());
        $remainder = self::RENT_AMOUNT - $fromVault;

        if ($remainder > 0 && !$player->removeGils($remainder)) {
            throw new \InvalidArgumentException(sprintf('Il vous faut %d Gils pour le loyer.', self::RENT_AMOUNT));
        }

        $this->debitVault($shop, $fromVault);
        $this->extend($shop);

        if (ShopStatus::Arrears === $shop->getStatus()) {
            $shop->setStatus(ShopStatus::Open);
        }

        $this->entityManager->flush();

        $this->logger->info('Shop rent paid', [
            'shop_id' => $shop->getId(),
            'from_vault' => $fromVault,
            'from_purse' => $remainder,
        ]);
    }

    /**
     * Preleve les loyers echus.
     *
     * Prelevement automatique tant que la caisse ou la bourse suit : un
     * proprietaire solvable ne doit pas fermer boutique pour avoir oublie un
     * bouton.
     *
     * @return array{charged: int, closed: int}
     */
    public function collectDueRents(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $charged = 0;
        $closed = 0;

        foreach ($this->shopRepository->findWithRentDue($now) as $shop) {
            $owner = $shop->getOwner();
            $fromVault = min(self::RENT_AMOUNT, $shop->getVaultGils());
            $remainder = self::RENT_AMOUNT - $fromVault;

            if (0 === $remainder || $owner->removeGils($remainder)) {
                $this->debitVault($shop, $fromVault);
                $this->extend($shop);
                ++$charged;
                continue;
            }

            $shop->setStatus(ShopStatus::Arrears);
            ++$closed;

            $this->logger->info('Shop rent unpaid, shutters down', [
                'shop_id' => $shop->getId(),
                'due_since' => $shop->getRentDueAt()?->format(\DateTimeInterface::ATOM),
            ]);
        }

        $this->entityManager->flush();

        return ['charged' => $charged, 'closed' => $closed];
    }

    private function debitVault(PlayerShop $shop, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $kept = $shop->emptyVault() - $amount;
        $shop->creditVault(max(0, $kept));
    }

    private function extend(PlayerShop $shop): void
    {
        $from = $shop->getRentDueAt() ?? new \DateTimeImmutable();
        $shop->setRentDueAt($from->modify(sprintf('+%d days', self::RENT_PERIOD_DAYS)));
    }
}
