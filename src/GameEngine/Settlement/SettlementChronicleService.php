<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\Settlement;
use App\Enum\SettlementRank;
use App\GameEngine\Codex\WorldFactService;
use App\Repository\SettlementContributionRepository;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le serveur garde la trace de qui a bati quoi (FOY-14).
 *
 * A la cloture d'une maree, chaque foyer dont le rang a bouge depuis
 * l'ouverture laisse un **fait de monde** public et horodate (NAR-07), credite
 * a la guilde qui a le plus depose de sediment. En bien comme en mal : une
 * ville qui s'endort s'inscrit au meme titre qu'une ville qui grandit — un
 * journal qui ne raconterait que les reussites ne serait pas une chronique,
 * mais une vitrine.
 *
 * **Le repere se compare, il ne se recalcule pas.** `Settlement::tideStartRank`
 * garde le rang tenu a l'ouverture de la maree en cours ; la cloture compare,
 * ecrit, puis repose le repere sur le rang courant. C'est le seul etat
 * historique du pilier — tout le reste (plafond de Crue, vassalite, rang) se
 * derive, et doit continuer a se deriver.
 *
 * **La premiere cloture n'ecrit rien** : le repere est nul, et le seed du monde
 * livre n'est l'œuvre de personne. Le crediter serait un mensonge, et un
 * mensonge du premier jour est celui qu'on ne rattrape plus.
 */
class SettlementChronicleService
{
    public function __construct(
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementContributionRepository $contributionRepository,
        private readonly SettlementDefinitionLoader $loader,
        private readonly WorldFactService $worldFactService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Ecrit la chronique de la maree qui s'acheve, puis repose les reperes.
     *
     * @return int nombre de faits de monde enregistres
     */
    public function recordTide(InfluenceSeason $season): int
    {
        // Marqueur « canon » (NAR-12) : une maree non-canon ne laisse pas de
        // trace durable. Le repere avance quand meme — sinon la maree suivante
        // crediterait a une guilde des mouvements survenus pendant une maree
        // que le monde a decide d'oublier.
        $canon = $season->isCanon();
        $floor = $this->loader->load()['minimum_type_rank'];

        $recorded = 0;

        foreach ($this->settlementRepository->findAllRanked() as $settlement) {
            $before = $settlement->getTideStartRank();
            $after = $settlement->getRank();

            $settlement->setTideStartRank($after);

            if (!$canon || $before === null || $before === $after) {
                continue;
            }

            if (!$this->isNotable($before, $after, $floor)) {
                continue;
            }

            $this->record($season, $settlement, $before, $after);
            ++$recorded;
        }

        $this->entityManager->flush();

        return $recorded;
    }

    /**
     * Ce qui merite la chronique.
     *
     * Le plancher est celui de l'identite (`type.minimum_rank`, le Hameau) :
     * en dessous, un foyer n'a pas encore de nom, et « Ruine devenue
     * Campement » est du bruit dans un journal que les joueurs lisent. Une
     * **chute** se juge sur le rang perdu, une **montee** sur le rang atteint —
     * sinon un Bourg retombe au Campement sortirait du journal exactement au
     * moment ou sa disparition compte le plus.
     */
    private function isNotable(SettlementRank $before, SettlementRank $after, SettlementRank $floor): bool
    {
        $judged = $after->level() > $before->level() ? $after : $before;

        return $judged->isAtLeast($floor);
    }

    private function record(InfluenceSeason $season, Settlement $settlement, SettlementRank $before, SettlementRank $after): void
    {
        $zone = $settlement->getZone();
        $guildName = $this->contributionRepository->findLeadingGuildName($settlement);
        $rose = $after->level() > $before->level();

        $this->worldFactService->recordWorldFact(
            // Idempotent par slug (convention NAR-07) : rejouer la cloture met a
            // jour le fait, elle n'en empile pas un second. Le slug porte l'arc
            // et la zone — une maree, un foyer, un fait.
            sprintf('%s_%s_foyer', $season->getStoryArc(), $zone->getSlug()),
            sprintf('%s — %s', $season->getName(), $zone->getName()),
            $this->narrate($zone->getName(), $before, $after, $rose, $guildName),
            $guildName,
        );
    }

    private function narrate(string $zoneName, SettlementRank $before, SettlementRank $after, bool $rose, ?string $guildName): string
    {
        if ($rose) {
            return null !== $guildName
                ? sprintf(
                    'Au terme de la marée, %s passe de %s à %s. La guilde « %s » y a déposé le plus, et la chronique retient son nom.',
                    $zoneName,
                    mb_strtolower($before->label()),
                    mb_strtolower($after->label()),
                    $guildName,
                )
                : sprintf(
                    'Au terme de la marée, %s passe de %s à %s. Nul nom de guilde ne s\'attache à ce chantier : la ville s\'est bâtie de mains dispersées.',
                    $zoneName,
                    mb_strtolower($before->label()),
                    mb_strtolower($after->label()),
                );
        }

        // La chute se raconte sans accuser personne. Le message du pilier est
        // « ce lieu s'endort », jamais « vous avez perdu » (FOY-10) — et la
        // guilde citee reste celle qui a bati, pas une coupable.
        return null !== $guildName
            ? sprintf(
                'Au terme de la marée, %s retombe de %s à %s. Ce que la guilde « %s » y avait bâti s\'amincit faute d\'être fréquenté.',
                $zoneName,
                mb_strtolower($before->label()),
                mb_strtolower($after->label()),
                $guildName,
            )
            : sprintf(
                'Au terme de la marée, %s retombe de %s à %s. Le lieu s\'endort, et personne n\'est venu le tenir éveillé.',
                $zoneName,
                mb_strtolower($before->label()),
                mb_strtolower($after->label()),
            );
    }
}
