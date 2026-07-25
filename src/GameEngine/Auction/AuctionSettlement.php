<?php

namespace App\GameEngine\Auction;

/**
 * Repartition des Gils d'une vente a l'hotel des ventes (ECO-04).
 *
 * Quatre parties peuvent toucher a la somme : l'acheteur qui paie, le vendeur
 * qui encaisse, la guilde qui controle la region qui preleve sa taxe, et le
 * neant quand personne ne controle la region.
 *
 * Deux invariants tiennent tout le modele :
 *
 * 1. **Le vendeur ne depend jamais de l'identite de l'acheteur.** Il touche
 *    toujours `totalPrice - taxAmount`. Sans cela, un vendeur verrait son
 *    revenu varier selon l'appartenance de guilde de l'acheteur — impossible a
 *    anticiper au moment de fixer un prix.
 * 2. **La ristourne membre est plafonnee par la taxe.** La guilde ne peut
 *    reverser que ce qu'elle prelevait ; au-dela, elle financerait la remise
 *    sur son tresor a chaque transaction, ce qui en ferait une fuite et non un
 *    avantage.
 */
final readonly class AuctionSettlement
{
    private function __construct(
        public int $totalPrice,
        public int $taxAmount,
        public int $memberRebate,
        public int $sellerRevenue,
        public int $buyerCharge,
        public int $treasuryAmount,
        public int $burnedAmount,
    ) {
    }

    /**
     * @param float $taxRate          taux de taxe de la region du marche
     * @param bool  $regionHasRuler   une guilde controle-t-elle la region ?
     * @param bool  $buyerIsMember    l'acheteur appartient-il a cette guilde ?
     * @param float $memberRebateRate remise accordee aux membres (coherence GCC)
     */
    public static function compute(
        int $totalPrice,
        float $taxRate,
        bool $regionHasRuler,
        bool $buyerIsMember,
        float $memberRebateRate,
    ): self {
        $taxAmount = $totalPrice > 0 ? (int) floor($totalPrice * max(0.0, $taxRate)) : 0;

        // Une region sans guilde controlante ne redistribue rien : la taxe est
        // **detruite**. C'est le gold sink du canal — les Gils sortent du jeu
        // au lieu de s'accumuler sans contrepartie.
        $treasury = $regionHasRuler ? $taxAmount : 0;
        $burned = $regionHasRuler ? 0 : $taxAmount;

        $rebate = 0;
        if ($regionHasRuler && $buyerIsMember && $taxAmount > 0) {
            $rebate = min($taxAmount, (int) floor($totalPrice * max(0.0, $memberRebateRate)));
            $treasury -= $rebate;
        }

        return new self(
            totalPrice: $totalPrice,
            taxAmount: $taxAmount,
            memberRebate: $rebate,
            sellerRevenue: $totalPrice - $taxAmount,
            buyerCharge: $totalPrice - $rebate,
            treasuryAmount: $treasury,
            burnedAmount: $burned,
        );
    }
}
