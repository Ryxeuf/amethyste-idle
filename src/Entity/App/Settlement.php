<?php

namespace App\Entity\App;

use App\Enum\SettlementDoctrine;
use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\Enum\SettlementType;
use App\Repository\SettlementRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Foyer d'une zone (FOY-01).
 *
 * Le monde cesse d'etre un decor traverse : l'activite des joueurs y depose du
 * **sediment**, le foyer monte en rang et ouvre des services, et l'oubli le
 * fait redescendre. Conception : [GAME_WORLD.md](../../../docs/GAME_WORLD.md)
 * §3, chiffrage : [BALANCE.md § 23](../../../docs/BALANCE.md).
 *
 * **Quatre indices, pas un compteur.** Ils decroissent independamment : le rang
 * se lit sur leur somme, le type sur le dominant. C'est ce qui permet a deux
 * villes de meme rang de ne pas se ressembler.
 *
 * **Toutes les zones n'ont pas de foyer.** le Fanal et les Jardins sont batis sur
 * la Voute — rien ne s'y depose (GAME_WORLD §3.4) ; la Cite ensevelie est un
 * donjon, on la fouille, elle ne monte pas. L'absence d'un `Settlement` est donc
 * une decision, pas un oubli : `config/game/settlements.yaml` la documente zone
 * par zone.
 */
#[ORM\Entity(repositoryClass: SettlementRepository::class)]
#[ORM\Table(name: 'settlement')]
#[ORM\UniqueConstraint(name: 'uq_settlement_zone', columns: ['zone_id'])]
class Settlement
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'zone_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Zone $zone;

    #[ORM\Column(name: 'rank', type: 'string', length: 20, enumType: SettlementRank::class)]
    private SettlementRank $rank = SettlementRank::Ruin;

    /**
     * Identite du foyer. `null` tant qu'aucun indice n'a pris l'avantage assez
     * longtemps — un Campement n'a pas encore d'identite.
     */
    #[ORM\Column(name: 'type', type: 'string', length: 20, nullable: true, enumType: SettlementType::class)]
    private ?SettlementType $type = null;

    #[ORM\Column(name: 'sediment_trade', type: 'integer', options: ['default' => 0])]
    private int $sedimentTrade = 0;

    #[ORM\Column(name: 'sediment_war', type: 'integer', options: ['default' => 0])]
    private int $sedimentWar = 0;

    #[ORM\Column(name: 'sediment_lore', type: 'integer', options: ['default' => 0])]
    private int $sedimentLore = 0;

    #[ORM\Column(name: 'sediment_rite', type: 'integer', options: ['default' => 0])]
    private int $sedimentRite = 0;

    /**
     * Plus haut rang jamais atteint.
     *
     * Sert a la remontee acceleree de FOY-10 : un foyer qui retombe ne repart
     * pas de zero en effort. Ce qu'on a bati une fois se rebatit moins cher.
     */
    #[ORM\Column(name: 'highest_rank', type: 'string', length: 20, enumType: SettlementRank::class)]
    private SettlementRank $highestRank = SettlementRank::Ruin;

    #[ORM\Column(name: 'ranked_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rankedAt = null;

    #[ORM\Column(name: 'decayed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $decayedAt = null;

    /**
     * Depuis quand l'indice dominant tient son avance — l'hysteresis du type
     * (BALANCE § 23.4). Remis a zero des que le dominant change.
     */
    #[ORM\Column(name: 'dominant_since', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dominantSince = null;

    /**
     * **Quel** indice tient cette avance (FOY-03).
     *
     * Sans lui, `dominantSince` serait une date sans sujet : on saurait que
     * quelqu'un mene depuis vingt jours, sans savoir qui, et un changement de
     * meneur passerait pour une continuite. Le type s'installerait alors au nom
     * du mauvais indice.
     *
     * Distinct de `$type` : celui-ci est le **pretendant**, celui-la
     * l'identite installee. Un pretendant qui ne tient pas la maree repart sans
     * avoir rien change.
     */
    /**
     * Depuis quand le foyer est **sous** le seuil de son rang (FOY-10).
     *
     * L'etiage s'annonce avant de se payer : une maree entiere pour redresser.
     * `null` signifie que le foyer tient son rang — le champ se vide des qu'il
     * repasse au-dessus, ce qui redonne une maree pleine a la fois suivante.
     */
    #[ORM\Column(name: 'ebb_since', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $ebbSince = null;

    #[ORM\Column(name: 'dominant_candidate', type: 'string', length: 20, nullable: true, enumType: SettlementIndex::class)]
    private ?SettlementIndex $dominantCandidate = null;

    /**
     * Rang tenu a l'ouverture de la maree en cours (FOY-14).
     *
     * Le seul champ du foyer qui soit de **l'histoire** et non de l'etat : la
     * chronique compare ce rang au rang courant a la cloture, et c'est de la
     * qu'elle tire « ce lieu a grandi » ou « ce lieu s'est endormi ». Tout le
     * reste du pilier se derive (le plafond de Crue, la vassalite, le rang
     * lui-meme) ; une photographie datee, non.
     *
     * `null` tant qu'aucune maree ne s'est achevee depuis la creation du foyer.
     * La premiere cloture pose le repere sans rien ecrire — le seed du monde
     * livre n'est l'œuvre de personne, et le crediter serait un mensonge.
     */
    #[ORM\Column(name: 'tide_start_rank', type: 'string', length: 20, nullable: true, enumType: SettlementRank::class)]
    private ?SettlementRank $tideStartRank = null;

    /**
     * La doctrine adoptee par une guilde pour ce foyer (FOY-13).
     *
     * Une seule colonne, et c'est le jalon : les deux ateliers sont exclusifs
     * **par construction**, aucun chemin de code ne peut les cumuler.
     *
     * `null` est l'etat normal. Un foyer sans doctrine n'est pas un foyer
     * inacheve : c'est un lieu que personne n'a encore voulu orienter.
     */
    #[ORM\Column(name: 'doctrine', type: 'string', length: 20, nullable: true, enumType: SettlementDoctrine::class)]
    private ?SettlementDoctrine $doctrine = null;

    /**
     * Depuis quand la doctrine tient.
     *
     * Sert au verrou : on ne bascule pas d'un atelier a l'autre a la semaine.
     * Une doctrine qui se retourne au gre de l'humeur ne divise plus personne,
     * et l'axe Extraire / Preserver ne serait qu'un interrupteur.
     */
    #[ORM\Column(name: 'doctrine_since', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $doctrineSince = null;

    public function __construct(Zone $zone)
    {
        $this->zone = $zone;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getZone(): Zone
    {
        return $this->zone;
    }

    public function getRank(): SettlementRank
    {
        return $this->rank;
    }

    public function setRank(SettlementRank $rank): self
    {
        $this->rank = $rank;

        if ($rank->isAtLeast($this->highestRank)) {
            $this->highestRank = $rank;
        }

        return $this;
    }

    public function getHighestRank(): SettlementRank
    {
        return $this->highestRank;
    }

    public function setHighestRank(SettlementRank $rank): self
    {
        $this->highestRank = $rank;

        return $this;
    }

    public function getType(): ?SettlementType
    {
        return $this->type;
    }

    public function setType(?SettlementType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSediment(SettlementIndex $index): int
    {
        return match ($index) {
            SettlementIndex::Trade => $this->sedimentTrade,
            SettlementIndex::War => $this->sedimentWar,
            SettlementIndex::Lore => $this->sedimentLore,
            SettlementIndex::Rite => $this->sedimentRite,
        };
    }

    public function setSediment(SettlementIndex $index, int $value): self
    {
        $value = max(0, $value);

        match ($index) {
            SettlementIndex::Trade => $this->sedimentTrade = $value,
            SettlementIndex::War => $this->sedimentWar = $value,
            SettlementIndex::Lore => $this->sedimentLore = $value,
            SettlementIndex::Rite => $this->sedimentRite = $value,
        };

        return $this;
    }

    public function addSediment(SettlementIndex $index, int $grains): self
    {
        if ($grains !== 0) {
            $this->setSediment($index, $this->getSediment($index) + $grains);
        }

        return $this;
    }

    /**
     * Les quatre indices, indexes par leur enum.
     *
     * @return array<string, int>
     */
    public function getAllSediment(): array
    {
        $all = [];
        foreach (SettlementIndex::cases() as $index) {
            $all[$index->value] = $this->getSediment($index);
        }

        return $all;
    }

    /**
     * Somme des quatre indices — ce sur quoi se lit le rang.
     */
    public function getTotalSediment(): int
    {
        return $this->sedimentTrade + $this->sedimentWar + $this->sedimentLore + $this->sedimentRite;
    }

    /**
     * Indice le plus fourni, ou `null` si le foyer est vierge.
     *
     * En cas d'egalite parfaite, aucun ne domine : rendre le premier venu
     * ferait dependre l'identite d'une ville de l'ordre de declaration d'un enum.
     */
    public function getDominantIndex(): ?SettlementIndex
    {
        $best = null;
        $bestValue = 0;
        $tied = false;

        foreach (SettlementIndex::cases() as $index) {
            $value = $this->getSediment($index);
            if ($value > $bestValue) {
                $best = $index;
                $bestValue = $value;
                $tied = false;
            } elseif ($value === $bestValue && $bestValue > 0) {
                $tied = true;
            }
        }

        return $tied ? null : $best;
    }

    public function getRankedAt(): ?\DateTimeImmutable
    {
        return $this->rankedAt;
    }

    public function setRankedAt(?\DateTimeImmutable $rankedAt): self
    {
        $this->rankedAt = $rankedAt;

        return $this;
    }

    public function getDecayedAt(): ?\DateTimeImmutable
    {
        return $this->decayedAt;
    }

    public function setDecayedAt(?\DateTimeImmutable $decayedAt): self
    {
        $this->decayedAt = $decayedAt;

        return $this;
    }

    public function getDominantSince(): ?\DateTimeImmutable
    {
        return $this->dominantSince;
    }

    public function setDominantSince(?\DateTimeImmutable $dominantSince): self
    {
        $this->dominantSince = $dominantSince;

        return $this;
    }

    public function getEbbSince(): ?\DateTimeImmutable
    {
        return $this->ebbSince;
    }

    public function setEbbSince(?\DateTimeImmutable $ebbSince): self
    {
        $this->ebbSince = $ebbSince;

        return $this;
    }

    /**
     * Le foyer est sous son seuil et le sait : l'etiage est annonce.
     */
    public function isEbbWarned(): bool
    {
        return $this->ebbSince !== null;
    }

    /**
     * Le foyer rebatit ce qu'il a deja eu — les depots y comptent double.
     */
    public function isRebuilding(): bool
    {
        return $this->highestRank->level() > $this->rank->level();
    }

    public function getDominantCandidate(): ?SettlementIndex
    {
        return $this->dominantCandidate;
    }

    public function setDominantCandidate(?SettlementIndex $candidate): self
    {
        $this->dominantCandidate = $candidate;

        return $this;
    }

    public function getTideStartRank(): ?SettlementRank
    {
        return $this->tideStartRank;
    }

    public function setTideStartRank(?SettlementRank $rank): self
    {
        $this->tideStartRank = $rank;

        return $this;
    }

    public function getDoctrine(): ?SettlementDoctrine
    {
        return $this->doctrine;
    }

    public function getDoctrineSince(): ?\DateTimeImmutable
    {
        return $this->doctrineSince;
    }

    /**
     * Adopte une doctrine, et date le choix.
     *
     * Aucune regression ne passe par ici : un foyer qui retombe garde sa
     * doctrine. C'est la meme regle que le patrimoine (FOY-05 / FOY-10) — on
     * borne ce qui reste a acquerir, on ne reprend pas ce qui est acquis, et un
     * atelier paye ne s'efface pas parce que la frequentation a baisse.
     */
    public function adoptDoctrine(SettlementDoctrine $doctrine, \DateTimeImmutable $at): self
    {
        $this->doctrine = $doctrine;
        $this->doctrineSince = $at;

        return $this;
    }
}
