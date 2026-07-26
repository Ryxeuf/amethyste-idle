<?php

namespace App\Entity\App;

use App\Repository\TimeTrialRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Parcours chronometre (tache 133).
 *
 * Reformulation PBBG des « courses entre joueurs » : le pivot ayant supprime
 * le deplacement en tuiles, une course en temps reel n'a plus de sens. Reste
 * ce qui en faisait l'interet — **comparer des trajets** — sous forme
 * asynchrone : rallier une suite de zones dans l'ordre, le plus vite possible,
 * et se mesurer au tableau des temps.
 *
 * Le chrono ne recompense pas les reflexes mais la **preparation** : une
 * monture raccourcit chaque liaison (tache 130), l'energie conditionne les
 * detours, et le graphe offre plusieurs routes de longueurs differentes.
 */
#[ORM\Entity(repositoryClass: TimeTrialRepository::class)]
#[ORM\Table(name: 'time_trial')]
class TimeTrial
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'slug', type: 'string', length: 60, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'name', type: 'string', length: 120)]
    private string $name;

    /** @var array<string, string>|null */
    #[ORM\Column(name: 'name_translations', type: 'json', nullable: true)]
    private ?array $nameTranslations = null;

    #[ORM\Column(name: 'description', type: 'text')]
    private string $description = '';

    /** @var array<string, string>|null */
    #[ORM\Column(name: 'description_translations', type: 'json', nullable: true)]
    private ?array $descriptionTranslations = null;

    /**
     * Zone de depart : le parcours ne se lance que sur place.
     */
    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'start_zone_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Zone $startZone;

    /**
     * Slugs des zones a rallier, dans l'ordre.
     *
     * Des slugs et non des cles etrangeres : un parcours est du contenu, il
     * doit pouvoir etre decrit dans une fixture ou une configuration sans
     * resoudre d'identifiants.
     *
     * @var list<string>
     */
    #[ORM\Column(name: 'checkpoints', type: 'json')]
    private array $checkpoints = [];

    #[ORM\Column(name: 'energy_cost', type: 'integer', options: ['default' => 5])]
    private int $energyCost = 5;

    /**
     * Duree au-dela de laquelle une tentative est perdue.
     *
     * Sans limite, une tentative oubliee resterait ouverte indefiniment et
     * empecherait toute nouvelle course. La limite ferme la boucle sans
     * demander de cron : elle est constatee au prochain passage.
     */
    #[ORM\Column(name: 'time_limit_seconds', type: 'integer', options: ['default' => 86400])]
    private int $timeLimitSeconds = 86_400;

    #[ORM\Column(name: 'enabled', type: 'boolean', options: ['default' => true])]
    private bool $enabled = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLocalizedName(?string $locale): string
    {
        return $this->nameTranslations[$locale] ?? $this->name;
    }

    /** @param array<string, string>|null $translations */
    public function setNameTranslations(?array $translations): self
    {
        $this->nameTranslations = $translations;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLocalizedDescription(?string $locale): string
    {
        return $this->descriptionTranslations[$locale] ?? $this->description;
    }

    /** @param array<string, string>|null $translations */
    public function setDescriptionTranslations(?array $translations): self
    {
        $this->descriptionTranslations = $translations;

        return $this;
    }

    public function getStartZone(): Zone
    {
        return $this->startZone;
    }

    public function setStartZone(Zone $startZone): self
    {
        $this->startZone = $startZone;

        return $this;
    }

    /** @return list<string> */
    public function getCheckpoints(): array
    {
        return $this->checkpoints;
    }

    /** @param list<string> $checkpoints */
    public function setCheckpoints(array $checkpoints): self
    {
        $this->checkpoints = array_values($checkpoints);

        return $this;
    }

    public function countCheckpoints(): int
    {
        return \count($this->checkpoints);
    }

    /**
     * Slug attendu apres `$reached` etapes franchies, ou null si le parcours
     * est acheve.
     */
    public function checkpointAt(int $reached): ?string
    {
        return $this->checkpoints[$reached] ?? null;
    }

    public function getEnergyCost(): int
    {
        return $this->energyCost;
    }

    public function setEnergyCost(int $energyCost): self
    {
        $this->energyCost = max(0, $energyCost);

        return $this;
    }

    public function getTimeLimitSeconds(): int
    {
        return $this->timeLimitSeconds;
    }

    public function setTimeLimitSeconds(int $seconds): self
    {
        $this->timeLimitSeconds = max(60, $seconds);

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
}
