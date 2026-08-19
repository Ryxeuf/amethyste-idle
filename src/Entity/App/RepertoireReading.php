<?php

namespace App\Entity\App;

use App\Enum\Element;
use App\Enum\SettlementRank;
use App\Repository\RepertoireReadingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ce dont le serveur se souvient, semaine par semaine (REP-01).
 *
 * GAME_WORLD § 12.3 b : *« ce qu'un serveur lit est ce dont il se souvient »*.
 * Une ligne n'est pas une lecture : c'est un **decompte**. Le Repertoire ne
 * garde pas la trace des lectures une a une, il tient un tableau de batons par
 * contexte — et ce choix porte la doctrine du jalon.
 *
 * **Aucune colonne ne nomme un joueur, et c'est voulu.** *Le Repertoire est la
 * memoire du serveur, pas un journal de joueurs.* Le canon ne demande jamais
 * de savoir qui a lu quoi ; il demande de savoir ce que le monde a lu. Une
 * table d'evenements par joueur repondrait aux deux questions, dont une que
 * personne n'a posee — et elle grossirait sans fin.
 *
 * La consequence est structurelle et vaut d'etre dite : **le plafond
 * anti-forcage ne peut pas vivre ici**, puisqu'ici on ne distingue pas les
 * joueurs. Il vit sur le joueur (`Player::repertoireReadingsOn()`), au meme
 * endroit et sous la meme forme que le plafond des gestes de faction.
 *
 * **Les trois axes du canon, et pourquoi la provenance peut manquer.** Les
 * dominantes se lisent sur l'element, la **provenance** de la materia et le
 * **lieu** de lecture. La provenance n'est connue que lorsque le monde l'a
 * donnee — une materia tombee d'un monstre sait de quelle zone elle vient. Une
 * materia achetee, fabriquee ou recue en quete ne le sait pas, et sa
 * provenance reste **nulle**.
 *
 * Le plan proposait de retomber sur la zone de lecture ; ce serait une erreur,
 * et une erreur invisible : *deux axes remplis par la meme colonne ne font
 * qu'un axe*, et la dominante de provenance dirait exactement ce que dit la
 * dominante de lieu. **Un inconnu reste inconnu** — REP-03 saura ce qu'il
 * mesure.
 *
 * **L'unicite du contexte n'est pas declaree ici**, et c'est une limite de
 * l'outil plutot qu'un choix : PostgreSQL traite deux `NULL` comme distincts
 * dans une contrainte unique, si bien qu'une contrainte ordinaire ouvrirait une
 * ligne neuve a **chaque** lecture de provenance inconnue au lieu
 * d'incrementer celle des inconnues. La migration pose donc un index unique sur
 * `COALESCE(...)`, qu'aucun attribut Doctrine ne sait exprimer. La deduplication
 * elle-meme ne depend pas de l'index : elle se fait par
 * `RepertoireReadingRepository::findContext()`, ou `null` devient `IS NULL`.
 */
#[ORM\Entity(repositoryClass: RepertoireReadingRepository::class)]
#[ORM\Table(name: 'repertoire_reading')]
#[ORM\Index(name: 'idx_repertoire_reading_week', columns: ['week_key'])]
class RepertoireReading
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    /** Semaine ISO (`WeekKey`) — l'horloge unique du projet (RET-07). */
    #[ORM\Column(name: 'week_key', type: 'string', length: 16)]
    private string $weekKey;

    #[ORM\Column(name: 'element', type: 'string', length: 32)]
    private string $element;

    /**
     * D'ou vient la materia, quand le monde l'a dit. `null` = inconnu, jamais
     * un repli sur la zone de lecture.
     */
    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'provenance_zone_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Zone $provenanceZone = null;

    /** Ou la lecture a eu lieu. Toujours connue : on lit quelque part. */
    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'reading_zone_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Zone $readingZone;

    /**
     * Le rang du foyer du lieu de lecture, s'il en a un.
     *
     * Le canon range le **type de foyer** parmi ce qui oriente le souvenir :
     * lire au Sanctuaire et lire au Bastion ne disent pas la meme chose du
     * monde. Toutes les zones n'ont pas de foyer (`settlements.yaml` le
     * documente zone par zone), d'ou le `null`.
     */
    #[ORM\Column(name: 'settlement_rank', type: 'string', length: 32, nullable: true)]
    private ?string $settlementRank = null;

    #[ORM\Column(name: 'tally', type: 'integer', options: ['default' => 0])]
    private int $tally = 0;

    public function __construct(string $weekKey, Element $element, Zone $readingZone)
    {
        $this->weekKey = $weekKey;
        $this->element = $element->value;
        $this->readingZone = $readingZone;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getWeekKey(): string
    {
        return $this->weekKey;
    }

    public function getElement(): Element
    {
        return Element::from($this->element);
    }

    public function getProvenanceZone(): ?Zone
    {
        return $this->provenanceZone;
    }

    public function setProvenanceZone(?Zone $zone): self
    {
        $this->provenanceZone = $zone;

        return $this;
    }

    public function getReadingZone(): Zone
    {
        return $this->readingZone;
    }

    public function getSettlementRank(): ?SettlementRank
    {
        return $this->settlementRank === null ? null : SettlementRank::from($this->settlementRank);
    }

    public function setSettlementRank(?SettlementRank $rank): self
    {
        $this->settlementRank = $rank?->value;

        return $this;
    }

    public function getTally(): int
    {
        return $this->tally;
    }

    public function increment(int $by = 1): self
    {
        $this->tally += $by;

        return $this;
    }
}
