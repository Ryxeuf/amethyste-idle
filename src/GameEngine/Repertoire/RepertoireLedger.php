<?php

namespace App\GameEngine\Repertoire;

use App\Entity\App\Player;
use App\Entity\App\RepertoireReading;
use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\Element;
use App\Repository\RepertoireReadingRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le versement d'une lecture au souvenir du serveur (REP-01).
 *
 * GAME_WORLD § 12.3 b : *« ce qu'un serveur retrouve depend de ce qu'il a lu —
 * quelles materias, ou, a quelle intensite, a quel moment »*. Les quatre
 * questions sont les quatre colonnes du contexte, et ce service est le seul
 * endroit qui les remplit.
 *
 * **Ce qu'il refuse de faire.** Il ne journalise pas. Une lecture ajoute un
 * baton a un decompte existant ou en ouvre un nouveau ; elle ne laisse jamais
 * de ligne a son nom, et le Repertoire ne peut donc pas dire qui a lu quoi.
 * C'est ce qui oblige le plafond a vivre sur le joueur.
 *
 * **Le plafond ne refuse jamais la lecture elle-meme.** Un joueur au-dela de
 * son plafond continue de lire — reputation, Codex, accord —, seule sa
 * contribution au souvenir s'arrete. Refuser le geste ferait du plafond une
 * borne de jeu ; il n'est qu'une borne de mesure.
 */
class RepertoireLedger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RepertoireReadingRepository $readings,
        private readonly RepertoireCatalog $catalog,
    ) {
    }

    /**
     * Verse une lecture, ou constate qu'elle ne compte plus aujourd'hui.
     *
     * Rend `true` si le souvenir du serveur a bouge.
     */
    public function record(Player $player, Element $element, ?int $provenanceZoneId, string $weekKey, string $dayKey): bool
    {
        $readingZone = $player->getCurrentZone();
        if (!$readingZone instanceof Zone) {
            // On lit quelque part. Un joueur sans zone est un etat transitoire
            // du modele, pas un lieu : le compter reviendrait a inventer un
            // lieu de lecture, et l'axe s'en trouverait fausse.
            return false;
        }

        if ($player->repertoireReadingsOn($dayKey) >= $this->catalog->dailyReadingsPerPlayer()) {
            return false;
        }

        $provenance = $provenanceZoneId === null
            ? null
            : $this->entityManager->getRepository(Zone::class)->find($provenanceZoneId);

        $rank = $this->entityManager->getRepository(Settlement::class)
            ->findOneBy(['zone' => $readingZone])?->getRank();

        $reading = $this->readings->findContext($weekKey, $element, $provenance, $readingZone, $rank?->value);

        if ($reading === null) {
            $reading = new RepertoireReading($weekKey, $element, $readingZone);
            $reading->setProvenanceZone($provenance);
            $reading->setSettlementRank($rank);
            $this->entityManager->persist($reading);
        }

        $reading->increment();
        $player->recordRepertoireReading($dayKey);

        $this->entityManager->flush();

        return true;
    }
}
