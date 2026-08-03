<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\GameEngine\Economy\PurityDrawer;
use App\GameEngine\Retention\WeekKey;
use App\GameEngine\Zone\GatherService;
use App\Repository\WeeklyOutcropRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le reseau d'oreilles — les rumeurs des Ruelles (FAC-06).
 *
 * L'information est deja une marchandise dans ce monde (le savoir du
 * prospecteur, l'Affleurement jamais annonce) ; la Confrerie en est le
 * marche. Trois rumeurs se vendent : ou la bande de purete tire haut, quel
 * filon repose a pleine vitalite, ou l'Affleurement de la semaine s'est
 * ouvert (RET-06 — la seule autre voie est de le decouvrir soi-meme).
 *
 * **Hostile chez la Confrerie : les rumeurs qu'on vous vend sont fausses.**
 * Le crochet `poisoned_rumors` de FAC-03 prend vie ici — elle ne vous attaque
 * pas, elle vous ment : la rumeur a la meme forme, le meme prix, et une zone
 * qui n'est pas la bonne. Jamais la boucle cœur : une fausse piste coute du
 * temps, pas un droit.
 */
class ShadowsRumors
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ShadowsMarketCatalog $catalog,
        private readonly ShadowsApproach $approach,
        private readonly HostileConsequenceResolver $hostileConsequences,
        private readonly WeeklyOutcropRepository $outcropRepository,
        private readonly PurityDrawer $purityDrawer,
        private readonly GatherService $gatherService,
    ) {
    }

    /**
     * Le guichet parle-t-il a ce joueur ? Vrai au comptoir d'un agent, apres
     * le premier contact seulement — avant, l'echoppe est une echoppe.
     */
    public function isAvailableFor(Player $player, ?Pnj $pnj): bool
    {
        return null !== $pnj
            && $this->catalog->isCounter($pnj->getSlug())
            && $this->approach->hasMet($player);
    }

    public function priceGils(): int
    {
        return $this->catalog->rumorPriceGils();
    }

    /**
     * Achete une rumeur au guichet. Rend la cle de traduction et ses
     * parametres — l'appelant affiche, le reseau ne parle qu'une fois.
     *
     * @return array{key: string, params: array<string, string>}
     *
     * @throws ShadowsMarketException si le guichet ou la bourse refuse (cle en message)
     */
    public function buy(Player $player, ?Pnj $pnj): array
    {
        if (null === $pnj || !$this->catalog->isCounter($pnj->getSlug())) {
            throw new ShadowsMarketException('game.shadows.rumor.error.counter');
        }

        if (!$this->approach->hasMet($player)) {
            // Le reseau ne parle pas aux inconnus : avant le premier contact,
            // le guichet est une echoppe comme une autre — meme refus neutre.
            throw new ShadowsMarketException('game.shadows.rumor.error.counter');
        }

        if (!$player->removeGils($this->catalog->rumorPriceGils())) {
            throw new ShadowsMarketException('game.shadows.rumor.error.gils');
        }

        $poisoned = $this->hostileConsequences->areRumorsPoisoned($player);

        $rumor = match ($this->roll(3)) {
            1 => $this->outcropRumor($poisoned),
            2 => $this->signatureRumor($poisoned),
            default => $this->restedVeinRumor($poisoned),
        };

        $this->entityManager->flush();

        return $rumor;
    }

    /**
     * L'Affleurement de la semaine : la zone et le filon — ou, empoisonnee,
     * une zone qui n'est pas la bonne.
     *
     * @return array{key: string, params: array<string, string>}
     */
    private function outcropRumor(bool $poisoned): array
    {
        $outcrop = $this->outcropRepository->findForWeek(WeekKey::of(new \DateTimeImmutable()));
        if (null === $outcrop) {
            return ['key' => 'game.shadows.rumor.silence', 'params' => []];
        }

        $zoneName = $poisoned
            ? $this->wrongZoneName($outcrop->getZone()->getSlug())
            : $outcrop->getZone()->getName();

        return [
            'key' => 'game.shadows.rumor.outcrop',
            'params' => ['%zone%' => $zoneName, '%vein%' => $outcrop->getVeinSlug()],
        ];
    }

    /**
     * Ou la bande tire haut cette nuit : la meilleure signature, jour et nuit
     * confondus — la signature deplace les poids, jamais le plafond, et la
     * rumeur le dit comme le reseau le sait.
     *
     * @return array{key: string, params: array<string, string>}
     */
    private function signatureRumor(bool $poisoned): array
    {
        $best = null;
        $bestShift = null;
        foreach ($this->purityDrawer->signatures() as $slug => $signature) {
            $shift = max($signature['weight_shift'], $signature['night_weight_shift']);
            if (null === $bestShift || $shift > $bestShift) {
                $best = $slug;
                $bestShift = $shift;
            }
        }

        if (null === $best) {
            return ['key' => 'game.shadows.rumor.silence', 'params' => []];
        }

        $zoneName = $poisoned ? $this->wrongZoneName($best) : $this->zoneNameOf($best);

        return ['key' => 'game.shadows.rumor.signature', 'params' => ['%zone%' => $zoneName]];
    }

    /**
     * Le filon le plus repose du monde connu — stock effectif contre
     * capacite, lu par le meme chemin que l'ecran de zone (le stock stocke
     * est perime, la regeneration s'applique a la lecture).
     *
     * @return array{key: string, params: array<string, string>}
     */
    private function restedVeinRumor(bool $poisoned): array
    {
        $bestZone = null;
        $bestVein = null;
        $bestRatio = -1.0;

        foreach ($this->entityManager->getRepository(Zone::class)->findAll() as $zone) {
            if (!$zone->isEnabled()) {
                continue;
            }
            foreach ($this->gatherService->getGatherables($zone) as $resource) {
                if ($resource->capacity < 1) {
                    continue;
                }
                $ratio = $resource->stock / $resource->capacity;
                if ($ratio > $bestRatio) {
                    $bestRatio = $ratio;
                    $bestZone = $zone;
                    $bestVein = $resource->slug;
                }
            }
        }

        if (null === $bestZone || null === $bestVein) {
            return ['key' => 'game.shadows.rumor.silence', 'params' => []];
        }

        $zoneName = $poisoned ? $this->wrongZoneName($bestZone->getSlug()) : $bestZone->getName();

        return [
            'key' => 'game.shadows.rumor.rested_vein',
            'params' => ['%zone%' => $zoneName, '%vein%' => $bestVein],
        ];
    }

    /**
     * Une zone qui n'est pas celle de la verite — le mensonge a la meme forme
     * que l'information.
     */
    private function wrongZoneName(string $truthSlug): string
    {
        $candidates = [];
        foreach ($this->entityManager->getRepository(Zone::class)->findAll() as $zone) {
            if ($zone->isEnabled() && $zone->getSlug() !== $truthSlug) {
                $candidates[] = $zone;
            }
        }

        if ([] === $candidates) {
            return $truthSlug;
        }

        return $candidates[($this->roll(\count($candidates)) - 1) % \count($candidates)]->getName();
    }

    private function zoneNameOf(string $slug): string
    {
        $zone = $this->entityManager->getRepository(Zone::class)->findOneBy(['slug' => $slug]);

        return $zone?->getName() ?? $slug;
    }

    /**
     * Protegee pour que les tests fixent la rumeur tiree.
     */
    protected function roll(int $max): int
    {
        return random_int(1, max(1, $max));
    }
}
