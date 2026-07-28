<?php

namespace App\Entity\App;

use App\Repository\WeeklyOutcropRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * L'Affleurement de la semaine (RET-06).
 *
 * Un filon du monde, tire chaque lundi, dont la bande maximale monte d'un cran
 * pendant sept jours. La rotation hebdomadaire du monde a **cout d'ecriture nul**
 * (levier Ryzom) : rien n'est cree, rien n'est deplace — une seule ligne change
 * ce que la carte vaut cette semaine.
 *
 * **Rien de tout cela ne s'annonce.** L'information se decouvre par prospection
 * sur place, ou s'achete a qui l'a trouvee. C'est tout le point de la brique :
 * l'afficher la tuerait, parce qu'un affleurement annonce devient une ruee et
 * cesse d'etre une decouverte. Le savoir du prospecteur redevient monnayable a
 * cadence fixe, et c'est la seule chose que ce jalon produit.
 *
 * L'unicite porte sur la semaine : un affleurement a la fois, et rejouer la
 * rotation ne le deplace pas.
 */
#[ORM\Entity(repositoryClass: WeeklyOutcropRepository::class)]
#[ORM\Table(name: 'weekly_outcrop')]
#[ORM\UniqueConstraint(name: 'uq_weekly_outcrop_week', columns: ['week_key'])]
class WeeklyOutcrop
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    /**
     * Semaine ISO (`2026-W31`) — la meme clef que tous les autres rendez-vous
     * hebdomadaires (contrat RET-07).
     */
    #[ORM\Column(name: 'week_key', type: 'string', length: 10)]
    private string $weekKey;

    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'zone_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Zone $zone;

    #[ORM\Column(name: 'vein_slug', type: 'string', length: 64)]
    private string $veinSlug;

    public function __construct(string $weekKey, Zone $zone, string $veinSlug)
    {
        $this->weekKey = $weekKey;
        $this->zone = $zone;
        $this->veinSlug = $veinSlug;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWeekKey(): string
    {
        return $this->weekKey;
    }

    public function getZone(): Zone
    {
        return $this->zone;
    }

    public function getVeinSlug(): string
    {
        return $this->veinSlug;
    }
}
