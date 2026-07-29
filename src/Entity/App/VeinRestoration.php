<?php

namespace App\Entity\App;

use App\Repository\VeinRestorationRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Un chantier de restauration ouvert sur un filon pali (FOY-12).
 *
 * Mecanique de Wakfu : la sanction devient une **depense politique**, pas une
 * perte seche. Un filon abime n'attend plus qu'on l'oublie — une guilde peut
 * payer pour qu'il se refasse plus vite, et ce paiement se voit.
 *
 * **L'effet est etale**, jamais instantane : on n'achete pas un monde propre.
 * Le chantier court sur plusieurs jours et accelere la recuperation naturelle
 * a chaque tick ; il ne la remplace pas.
 *
 * Cette ligne **est** la trace. Le plan disait « trace au `GuildVaultLog` »,
 * mais ce registre exige un `Item` non nul : c'est un journal d'objets, pas de
 * Gils. L'enregistrement porte donc lui-meme la guilde, le montant et la date,
 * et la **mention publique** passe par le journal de monde — la ou se lisent
 * deja les actes de gouvernement (FOY-14).
 */
#[ORM\Entity(repositoryClass: VeinRestorationRepository::class)]
#[ORM\Table(name: 'vein_restoration')]
#[ORM\Index(columns: ['zone_id', 'vein_slug'], name: 'idx_vein_restoration_vein')]
class VeinRestoration
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'zone_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Zone $zone;

    #[ORM\Column(name: 'vein_slug', type: 'string', length: 64)]
    private string $veinSlug;

    #[ORM\ManyToOne(targetEntity: Guild::class)]
    #[ORM\JoinColumn(name: 'guild_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Guild $guild;

    /** Ce que le tresor a verse. Immuable : c'est la trace comptable du chantier. */
    #[ORM\Column(name: 'cost_gils', type: 'integer')]
    private int $costGils;

    /** Paleur au moment de l'ouverture — ce sur quoi le cout a ete indexe. */
    #[ORM\Column(name: 'paleness_at_start', type: 'float')]
    private float $palenessAtStart;

    #[ORM\Column(name: 'ends_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $endsAt;

    public function __construct(Zone $zone, string $veinSlug, Guild $guild, int $costGils, float $palenessAtStart, \DateTimeImmutable $endsAt)
    {
        $this->zone = $zone;
        $this->veinSlug = $veinSlug;
        $this->guild = $guild;
        $this->costGils = $costGils;
        $this->palenessAtStart = $palenessAtStart;
        $this->endsAt = $endsAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getZone(): Zone
    {
        return $this->zone;
    }

    public function getVeinSlug(): string
    {
        return $this->veinSlug;
    }

    public function getGuild(): Guild
    {
        return $this->guild;
    }

    public function getCostGils(): int
    {
        return $this->costGils;
    }

    public function getPalenessAtStart(): float
    {
        return $this->palenessAtStart;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function isActiveAt(\DateTimeImmutable $now): bool
    {
        return $this->endsAt > $now;
    }
}
