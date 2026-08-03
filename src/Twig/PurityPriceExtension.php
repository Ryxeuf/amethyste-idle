<?php

namespace App\Twig;

use App\GameEngine\Economy\PurityPricer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * La valeur d'un lot, portee a sa bande (MET-01).
 *
 * Les gabarits lisaient `gi.price` directement, donc le prix generique de la
 * matiere : un lot parfait s'affichait au prix du trouble. Deux fonctions,
 * une par usage — la valeur de reference (inventaire, banque, formulaire de
 * vente HV) et le rachat PNJ (l'onglet vente de la boutique) — toutes deux
 * deleguees a `PurityPricer`, pour que l'ecran dise exactement ce que le
 * guichet paiera.
 */
final class PurityPriceExtension extends AbstractExtension
{
    public function __construct(
        private readonly PurityPricer $pricer,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('purity_unit_value', [$this->pricer, 'unitValueOf']),
            new TwigFunction('purity_buyback_value', [$this->pricer, 'buybackValueOf']),
            new TwigFunction('purity_lots_value', [$this->pricer, 'lotsValueOf']),
        ];
    }
}
