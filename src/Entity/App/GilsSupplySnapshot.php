<?php

namespace App\Entity\App;

use App\Repository\GilsSupplySnapshotRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Masse monetaire du jeu a un instant donne (ECO-15).
 *
 * ECO-15 demande une « alerte d'inflation, ratio entrees/sorties de Gils ».
 * Mesurer ce **flux** supposerait que toute creation et toute destruction de
 * Gils passe par un point unique — or 26 fichiers appellent `addGils()` ou
 * `removeGils()` directement. Les canaliser serait une refonte, et une refonte
 * ne mesure rien tant qu'elle n'est pas finie.
 *
 * Le **stock** repond a la meme question sans toucher a un seul appelant :
 * l'inflation, c'est la masse monetaire qui gonfle. Le stock a meme un avantage
 * que le flux n'a pas — il est naturellement insensible a la velocite. Cent
 * ventes entre joueurs deplacent des Gils sans en creer un seul, et ne bougent
 * donc pas d'un centieme.
 *
 * Encore faut-il compter **tout** le stock. Les Gils en escrow ont quitte la
 * bourse du joueur sans etre detruits : ils vivent comme un nombre sur une
 * enchere ou une commande, et ressortiront a la resolution. Les omettre ferait
 * lire une deflation a chaque fois que le marche se remplit.
 */
#[ORM\Entity(repositoryClass: GilsSupplySnapshotRepository::class)]
#[ORM\Table(name: 'gils_supply_snapshot')]
#[ORM\Index(name: 'idx_gils_supply_captured', columns: ['captured_at'])]
class GilsSupplySnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'captured_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $capturedAt;

    /** Bourses des joueurs. */
    #[ORM\Column(name: 'player_gils', type: 'bigint')]
    private string $playerGils;

    /** Tresors de guilde (`Guild::gilsTreasury`). */
    #[ORM\Column(name: 'guild_gils', type: 'bigint')]
    private string $guildGils;

    /** Caisses d'echoppe non relevees (`PlayerShop::vaultGils`). */
    #[ORM\Column(name: 'shop_gils', type: 'bigint')]
    private string $shopGils;

    /**
     * Gils immobilises : mises d'enchere en cours et commissions de commande
     * vivante. Sortis d'une bourse, pas encore arrives dans une autre.
     */
    #[ORM\Column(name: 'escrow_gils', type: 'bigint')]
    private string $escrowGils;

    /**
     * Personnages comptes.
     *
     * Une masse en hausse parce que la population double n'est pas de
     * l'inflation. Le ratio par tete est le seul qui se compare dans le temps.
     */
    #[ORM\Column(name: 'player_count', type: 'integer')]
    private int $playerCount;

    public function __construct(
        int $playerGils,
        int $guildGils,
        int $shopGils,
        int $escrowGils,
        int $playerCount,
        ?\DateTimeImmutable $capturedAt = null,
    ) {
        $this->playerGils = (string) $playerGils;
        $this->guildGils = (string) $guildGils;
        $this->shopGils = (string) $shopGils;
        $this->escrowGils = (string) $escrowGils;
        $this->playerCount = $playerCount;
        $this->capturedAt = $capturedAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCapturedAt(): \DateTimeImmutable
    {
        return $this->capturedAt;
    }

    public function getPlayerGils(): int
    {
        return (int) $this->playerGils;
    }

    public function getGuildGils(): int
    {
        return (int) $this->guildGils;
    }

    public function getShopGils(): int
    {
        return (int) $this->shopGils;
    }

    public function getEscrowGils(): int
    {
        return (int) $this->escrowGils;
    }

    public function getPlayerCount(): int
    {
        return $this->playerCount;
    }

    public function getTotal(): int
    {
        return $this->getPlayerGils() + $this->getGuildGils() + $this->getShopGils() + $this->getEscrowGils();
    }

    /**
     * Masse par personnage — la seule grandeur comparable dans le temps.
     */
    public function getPerCapita(): float
    {
        return $this->playerCount > 0 ? $this->getTotal() / $this->playerCount : 0.0;
    }
}
