<?php

namespace App\Entity\Game;

use App\Repository\CodexEntryRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Entree de Codex (NAR-05) : foyer de la trame de monde. Chaque entree
 * (region, faction, lore de bestiaire, fait de monde) se debloque par la
 * decouverte — visite de zone, kill de boss, fin d'arc — via un couple
 * declaratif (`unlockType`, `unlockKey`). Une entree `manual` n'est jamais
 * debloquee par un declencheur (reservee aux faits de monde publics, NAR-07).
 */
#[ORM\Entity(repositoryClass: CodexEntryRepository::class)]
#[ORM\Table(name: 'game_codex_entries')]
#[ORM\Index(name: 'idx_codex_entries_category', columns: ['category'])]
#[ORM\Index(name: 'idx_codex_entries_unlock', columns: ['unlock_type', 'unlock_key'])]
class CodexEntry
{
    use TimestampableEntity;

    public const CATEGORY_REGION = 'region';
    public const CATEGORY_FACTION = 'faction';
    public const CATEGORY_BESTIARY_LORE = 'bestiary_lore';
    public const CATEGORY_WORLD_FACT = 'world_fact';

    public const UNLOCK_ZONE_VISIT = 'zone_visit';
    public const UNLOCK_BOSS_KILL = 'boss_kill';
    public const UNLOCK_ARC_COMPLETED = 'arc_completed';
    public const UNLOCK_MANUAL = 'manual';
    // FAC-04b : lire une materia inscrit ce qu'elle portait. La cle est
    // l'element du flux lu — la premiere lecture de chaque flux ouvre sa page.
    public const UNLOCK_MATERIA_READ = 'materia_read';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'slug', type: 'string', length: 255, unique: true)]
    private string $slug = '';

    #[ORM\Column(name: 'category', type: 'string', length: 50)]
    private string $category = self::CATEGORY_REGION;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private string $title = '';

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'title_translations', type: 'json', nullable: true)]
    private ?array $titleTranslations = null;

    #[ORM\Column(name: 'description', type: 'text')]
    private string $description = '';

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'description_translations', type: 'json', nullable: true)]
    private ?array $descriptionTranslations = null;

    #[ORM\Column(name: 'unlock_type', type: 'string', length: 50)]
    private string $unlockType = self::UNLOCK_MANUAL;

    /**
     * Cle de deblocage : slug de zone (`zone_visit`), slug de monstre (`boss_kill`)
     * ou slug d'arc (`arc_completed`). null pour `manual`.
     */
    #[ORM\Column(name: 'unlock_key', type: 'string', length: 255, nullable: true)]
    private ?string $unlockKey = null;

    #[ORM\Column(name: 'illustration_path', type: 'string', length: 255, nullable: true)]
    private ?string $illustrationPath = null;

    /**
     * Nom de la guilde creditee pour un fait de monde (NAR-07/NAR-11). null si
     * aucun credit de guilde ne s'applique.
     */
    #[ORM\Column(name: 'credited_guild_name', type: 'string', length: 255, nullable: true)]
    private ?string $creditedGuildName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Un fait de monde (`world_fact`) est public : visible de tous les joueurs,
     * sans deblocage individuel (NAR-07).
     */
    public function isPublic(): bool
    {
        return $this->category === self::CATEGORY_WORLD_FACT;
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

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getLocalizedTitle(?string $locale): string
    {
        if ($locale === null || $locale === '' || $this->titleTranslations === null) {
            return $this->title;
        }
        $translation = $this->titleTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->title;
    }

    /**
     * @return array<string, string>
     */
    public function getTitleTranslations(): array
    {
        return $this->titleTranslations ?? [];
    }

    /**
     * @param array<string, mixed>|null $translations
     */
    public function setTitleTranslations(?array $translations): self
    {
        $this->titleTranslations = $this->normalizeTranslations($translations);

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
        if ($locale === null || $locale === '' || $this->descriptionTranslations === null) {
            return $this->description;
        }
        $translation = $this->descriptionTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->description;
    }

    /**
     * @return array<string, string>
     */
    public function getDescriptionTranslations(): array
    {
        return $this->descriptionTranslations ?? [];
    }

    /**
     * @param array<string, mixed>|null $translations
     */
    public function setDescriptionTranslations(?array $translations): self
    {
        $this->descriptionTranslations = $this->normalizeTranslations($translations);

        return $this;
    }

    public function getUnlockType(): string
    {
        return $this->unlockType;
    }

    public function setUnlockType(string $unlockType): self
    {
        $this->unlockType = $unlockType;

        return $this;
    }

    public function getUnlockKey(): ?string
    {
        return $this->unlockKey;
    }

    public function setUnlockKey(?string $unlockKey): self
    {
        $normalized = $unlockKey !== null ? trim($unlockKey) : null;
        $this->unlockKey = $normalized === '' ? null : $normalized;

        return $this;
    }

    public function getIllustrationPath(): ?string
    {
        return $this->illustrationPath;
    }

    public function setIllustrationPath(?string $illustrationPath): self
    {
        $this->illustrationPath = $illustrationPath;

        return $this;
    }

    public function getCreditedGuildName(): ?string
    {
        return $this->creditedGuildName;
    }

    public function setCreditedGuildName(?string $creditedGuildName): self
    {
        $normalized = $creditedGuildName !== null ? trim($creditedGuildName) : null;
        $this->creditedGuildName = $normalized === '' ? null : $normalized;

        return $this;
    }

    /**
     * @param array<string, mixed>|null $translations
     *
     * @return array<string, string>|null
     */
    private function normalizeTranslations(?array $translations): ?array
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && \is_string($value) && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }

        return $normalized === [] ? null : $normalized;
    }
}
