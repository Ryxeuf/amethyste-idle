<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Pose les foyers du monde livre (FOY-01, decision A).
 *
 * **Rien n'est retro-gate.** Chaque zone deja peuplee demarre au rang
 * correspondant a ce qu'elle offre aujourd'hui : la Foret et les Mines ont des
 * PNJ, des ateliers et des quetes d'acte, elles ne peuvent pas naitre en Ruine.
 * Les Vallons (ZON-30), eux, demarreront a zero — zone neuve, tout est a batir,
 * et c'est le premier chantier collectif offert aux joueurs.
 *
 * Le seed est **narratif, pas protecteur** : si personne ne frequente les
 * Mines, leur foyer redescendra. Ce qu'il garantit, c'est que le pilier ne
 * s'ouvre pas sur un monde qui aurait l'air abandonne.
 *
 * **Le seed ne pose aucun type.** Reparti egalement sur les quatre indices,
 * aucun ne domine — conforme a BALANCE §23.4, ou le type ne s'installe qu'apres
 * une maree d'avance tenue. L'identite d'une ville se gagne en jouant, elle ne
 * se decrete pas dans un fichier.
 *
 * Idempotent : re-jouer le seed ne cree pas de doublon et ne rejoue pas un
 * foyer que les joueurs ont deja fait bouger.
 */
class SettlementSeeder
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementDefinitionLoader $loader,
    ) {
    }

    /**
     * @return array{created: int, skipped: int, unknown: list<string>}
     */
    public function seed(bool $flush = true): array
    {
        $definition = $this->loader->load();
        $created = 0;
        $skipped = 0;
        $unknown = [];

        foreach ($definition['seed'] as $slug => $entry) {
            $zone = $this->entityManager->getRepository(Zone::class)->findOneBy(['slug' => $slug]);
            if ($zone === null) {
                // Une zone du seed qui n'existe pas encore n'est pas une erreur
                // fatale — les Vallons arriveront avec ZON-30 — mais elle doit
                // se voir dans le rapport plutot que de disparaitre.
                $unknown[] = $slug;
                continue;
            }

            if ($this->settlementRepository->findOneByZone($zone) !== null) {
                ++$skipped;
                continue;
            }

            $this->entityManager->persist($this->build($zone, $entry['rank'], $entry['stock']));
            ++$created;
        }

        if ($flush) {
            $this->entityManager->flush();
        }

        return ['created' => $created, 'skipped' => $skipped, 'unknown' => $unknown];
    }

    private function build(Zone $zone, SettlementRank $rank, int $stock): Settlement
    {
        $settlement = new Settlement($zone);
        $settlement->setRank($rank);
        $settlement->setRankedAt(new \DateTimeImmutable());
        $settlement->setCreatedAt(new \DateTime());
        $settlement->setUpdatedAt(new \DateTime());

        // Reparti a parts egales : aucun indice ne domine, donc aucun type.
        $indices = SettlementIndex::cases();
        $share = intdiv($stock, \count($indices));
        foreach ($indices as $index) {
            $settlement->setSediment($index, $share);
        }

        return $settlement;
    }
}
