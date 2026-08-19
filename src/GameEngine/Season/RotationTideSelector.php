<?php

namespace App\GameEngine\Season;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\Settlement;
use App\Enum\SettlementIndex;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Quel gabarit le monde **reclame** (NAR-15).
 *
 * GAME_SEASONS § 2 : *« la rotation choisit le gabarit dont l'indice de sediment
 * mondial est le plus faible — le monde equilibre son propre regime : trop de
 * guerre ? la maree suivante nourrit le commerce ou le savoir »*. C'est l'indice
 * decroissant d'EVE applique au calendrier : **le monde prescrit ce qui lui
 * manque**.
 *
 * ## L'indice se lit sur le monde, jamais sur un foyer
 *
 * La somme court sur **tous** les foyers. Lire le foyer dominant ferait dependre
 * la partition du serveur de l'humeur d'une seule ville, et un serveur qui a une
 * grande cite marchande et dix hameaux guerriers verrait des foires jusqu'a la
 * fin de l'annee.
 *
 * ## Le tirage n'en est pas un
 *
 * Rien n'est tire au sort, ici pas plus qu'au Repertoire (REP-03) : *le monde ne
 * joue pas aux des avec sa propre partition*. Un hasard rendrait le systeme
 * illisible pour les joueurs — qui ne pourraient plus deduire de leur propre
 * activite ce qui les attend — et intestable pour nous.
 *
 * A egalite d'indice, deux gabarits nourrissent la meme chose (la Contrefacon et
 * la Foire Franche nourrissent toutes deux `trade`). Le departage est alors **la
 * derniere fois qu'on les a joues** : passe celui qu'on a le moins vu. La
 * variete vient donc de l'histoire du serveur, pas d'un de.
 *
 * ## Un gabarit a deux indices est eligible par le plus bas des deux
 *
 * La Fonte nourrit `lore` **et** `rite`. Son score est le **minimum** des deux :
 * elle repond des que l'un ou l'autre manque, ce qui est exactement ce que
 * « nourrit les deux » veut dire.
 */
class RotationTideSelector
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TideDefinitionLoader $loader,
    ) {
    }

    /**
     * La clef du gabarit a jouer, ou `null` si le fichier n'en declare aucun.
     *
     * `null` ne devrait jamais arriver — le chargeur refuse un bloc `rotation`
     * vide —, mais un creneau sans gabarit vaut mieux qu'une exception au
     * milieu du tick de saison : la partition n'est pas ce qui doit casser le
     * calendrier.
     */
    public function select(): ?string
    {
        $templates = $this->loader->load()['rotation'];
        if ([] === $templates) {
            return null;
        }

        $world = $this->worldSediment();
        $lastPlayed = $this->lastPlayedByTheme();

        $best = null;
        $bestScore = null;
        $bestLastPlayed = null;

        foreach ($templates as $key => $template) {
            $score = min(array_map(
                static fn (SettlementIndex $index): int => $world[$index->value],
                $template['feeds'],
            ));

            // Jamais joue = jamais vu : il passe avant tout ce qui l'a ete.
            $seen = $lastPlayed[$template['theme']] ?? -1;

            if ($bestScore === null
                || $score < $bestScore
                || ($score === $bestScore && $seen < $bestLastPlayed)) {
                $best = (string) $key;
                $bestScore = $score;
                $bestLastPlayed = $seen;
            }
        }

        return $best;
    }

    /**
     * La somme de chaque indice sur tous les foyers du monde.
     *
     * @return array<string, int>
     */
    public function worldSediment(): array
    {
        $totals = [];
        foreach (SettlementIndex::cases() as $index) {
            $totals[$index->value] = 0;
        }

        foreach ($this->entityManager->getRepository(Settlement::class)->findAll() as $settlement) {
            foreach (SettlementIndex::cases() as $index) {
                $totals[$index->value] += $settlement->getSediment($index);
            }
        }

        return $totals;
    }

    /**
     * Le numero de la derniere saison ou chaque theme a ete joue.
     *
     * On indexe par **theme** et non par clef de gabarit : c'est le theme que la
     * saison porte en base. Passer par une colonne « clef de gabarit » aurait
     * demande une migration pour une information que le theme porte deja.
     *
     * @return array<string, int>
     */
    private function lastPlayedByTheme(): array
    {
        $seen = [];

        foreach ($this->entityManager->getRepository(InfluenceSeason::class)->findAll() as $season) {
            $theme = $season->getTheme();
            if ($theme === null) {
                continue;
            }

            $number = $season->getSeasonNumber();
            if (!isset($seen[$theme]) || $seen[$theme] < $number) {
                $seen[$theme] = $number;
            }
        }

        return $seen;
    }
}
