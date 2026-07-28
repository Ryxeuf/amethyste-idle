<?php

namespace App\GameEngine\Economy;

use App\Entity\App\WeeklyOutcrop;
use App\Entity\App\Zone;
use App\GameEngine\Retention\WeeklyCommissionGenerator;
use App\Repository\WeeklyOutcropRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le tirage de l'Affleurement de la semaine (RET-06).
 *
 * **Cout d'ecriture nul** (levier Ryzom) : rien n'est cree, rien n'est deplace.
 * Une seule ligne change ce que la carte vaut cette semaine, et le savoir du
 * prospecteur redevient monnayable a cadence fixe.
 *
 * **Le tirage est deterministe** pour une semaine donnee. Rejouer la rotation —
 * un `--force`, un incident — ne doit pas deplacer l'affleurement : ce serait un
 * reroll, et le prospecteur qui l'a trouve le matin aurait perdu son
 * information l'apres-midi.
 *
 * **Jamais deux semaines de suite la meme zone.** Sans cette regle, un tirage
 * malchanceux immobiliserait la rotation sur une seule region et retirerait a la
 * brique la seule chose qu'elle produit : une raison de bouger.
 *
 * **Le perimetre de la purete fait loi** : seuls les filons dont la matiere
 * porte une bande sont eligibles. Faire monter d'un cran la bande d'une botte
 * d'herbes n'aurait aucun effet, et l'affleurement serait muet une semaine sur
 * deux sans que rien ne le dise.
 */
class WeeklyOutcropSelector
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ZoneRepository $zoneRepository,
        private readonly WeeklyOutcropRepository $outcropRepository,
        private readonly PurityScope $scope,
    ) {
    }

    /**
     * @return array{selected: ?WeeklyOutcrop, skipped: bool, candidates: int}
     */
    public function select(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $weekKey = WeeklyCommissionGenerator::weekKey($now);

        $existing = $this->outcropRepository->findForWeek($weekKey);
        if ($existing !== null) {
            return ['selected' => $existing, 'skipped' => true, 'candidates' => 0];
        }

        $previous = $this->outcropRepository->findPrevious($weekKey);
        $candidates = $this->candidates($previous?->getZone());

        if ($candidates === []) {
            return ['selected' => null, 'skipped' => false, 'candidates' => 0];
        }

        // Deterministe : la clef de semaine seule decide. Deux executions rendent
        // le meme affleurement, et la rotation reste rejouable sans consequence.
        $index = abs(crc32($weekKey)) % \count($candidates);
        [$zone, $veinSlug] = $candidates[$index];

        $outcrop = new WeeklyOutcrop($weekKey, $zone, $veinSlug);
        $this->entityManager->persist($outcrop);
        $this->entityManager->flush();

        return ['selected' => $outcrop, 'skipped' => false, 'candidates' => \count($candidates)];
    }

    /**
     * Filons eligibles, tries pour que le tirage soit reproductible.
     *
     * @return list<array{0: Zone, 1: string}>
     */
    private function candidates(?Zone $excludedZone): array
    {
        $candidates = [];

        foreach ($this->zoneRepository->findAll() as $zone) {
            if ($excludedZone !== null && $zone->getSlug() === $excludedZone->getSlug()) {
                continue;
            }

            foreach ($zone->getGatherResources() as $resource) {
                // `getGatherResources()` rend deja une liste de tableaux : le
                // verifier ici serait une garde morte, et PHPStan le dit.
                $slug = isset($resource['slug']) ? (string) $resource['slug'] : '';
                $item = isset($resource['item']) ? (string) $resource['item'] : '';
                if ($slug === '' || !$this->scope->coversSlug($item)) {
                    continue;
                }

                $candidates[] = [$zone, $slug];
            }
        }

        // L'ordre de la base n'est pas un contrat : sans tri, le meme index
        // designerait un filon different d'une execution a l'autre, et le tirage
        // cesserait d'etre deterministe sans que rien ne le signale.
        usort($candidates, static fn (array $a, array $b): int => [$a[0]->getSlug(), $a[1]] <=> [$b[0]->getSlug(), $b[1]]);

        return $candidates;
    }
}
