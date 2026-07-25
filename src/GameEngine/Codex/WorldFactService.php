<?php

namespace App\GameEngine\Codex;

use App\Entity\Game\CodexEntry;
use App\Repository\CodexEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Journal de monde (NAR-07) : enregistre des faits de monde publics, horodates,
 * visibles de tous. Alimente par les resolutions de saison marquees « canon »
 * (branche en NAR-11/12). Idempotent par slug.
 */
class WorldFactService
{
    public function __construct(
        private readonly CodexEntryRepository $codexEntryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Enregistre (ou met a jour) un fait de monde public. Le slug garantit
     * l'idempotence : un slug existant est mis a jour, jamais duplique. La date
     * du fait est l'horodatage de creation de l'entree (`createdAt`).
     *
     * @param array<string, string> $titleTranslations
     * @param array<string, string> $descriptionTranslations
     */
    public function recordWorldFact(
        string $slug,
        string $title,
        string $description,
        ?string $creditedGuildName = null,
        array $titleTranslations = [],
        array $descriptionTranslations = [],
    ): CodexEntry {
        $entry = $this->codexEntryRepository->findBySlug($slug) ?? new CodexEntry();
        $entry
            ->setSlug($slug)
            ->setCategory(CodexEntry::CATEGORY_WORLD_FACT)
            ->setUnlockType(CodexEntry::UNLOCK_MANUAL)
            ->setUnlockKey(null)
            ->setTitle($title)
            ->setDescription($description)
            ->setCreditedGuildName($creditedGuildName)
            ->setTitleTranslations($titleTranslations)
            ->setDescriptionTranslations($descriptionTranslations);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return $entry;
    }
}
