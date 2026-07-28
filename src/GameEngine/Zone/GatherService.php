<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerJournalEntry;
use App\Entity\App\Zone;
use App\Entity\App\ZoneVein;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Enum\Purity;
use App\Event\Zone\ZoneGatherEvent;
use App\GameEngine\Economy\PurityDrawer;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Progression\ActionYieldResolver;
use App\GameEngine\World\WorldScaleService;
use App\Helper\InventoryHelper;
use App\Repository\PlayerJournalEntryRepository;
use App\Repository\ZoneVeinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Action Recolter (pivot PBBG, ZON-10).
 *
 * Coute de l'energie d'action puis puise dans un filon partage de la zone :
 * un stock collectif par ressource, commun a tous les joueurs presents, qui
 * s'epuise a mesure qu'on recolte puis respawn apres un delai (fenetre de
 * tension cooperative). Les ressources gagnees reutilisent les items existants
 * (minerais, plantes, poissons) et l'inventaire existant — comme Explorer et
 * Chasser, l'energie gate l'acces, jamais le combat.
 *
 * Definition declarative par zone via `Zone::gatherConfig` ; l'etat runtime du
 * stock partage vit dans `ZoneVein`, cree paresseusement a la premiere recolte.
 * Ajouter une ressource = ajouter de la donnee, pas du code.
 *
 * ECO-24c ajoute la seule condition d'acces du moteur : `requires_skill:` sur un
 * filon. Elle est **opt-in** — un filon qui ne la declare pas reste ouvert a
 * tous — et le refus tombe avant la depense d'energie.
 */
class GatherService
{
    public const DEFAULT_COST = 3;
    public const PARAM_COST = 'zone.energy.cost.gather';

    public const DEFAULT_CAPACITY = 20;
    public const DEFAULT_RESPAWN_SECONDS = 1800;
    public const DEFAULT_YIELD_MIN = 1;
    public const DEFAULT_YIELD_MAX = 2;

    private ?int $costCache = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActionEnergyManager $actionEnergyManager,
        private readonly ZoneTravelService $zoneTravelService,
        private readonly ZoneVeinRepository $veinRepository,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
        private readonly PlayerJournalEntryRepository $journalRepository,
        private readonly ActionYieldResolver $yieldResolver,
        private readonly WorldScaleService $worldScaleService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly PurityDrawer $purityDrawer,
    ) {
    }

    /**
     * Filons recoltables de la zone, avec l'etat du stock partage resolu a la
     * lecture (respawn applique en memoire, sans effet de bord).
     *
     * @return list<GatherableResource>
     */
    public function getGatherables(Zone $zone, ?Player $player = null): array
    {
        $now = $this->now();
        $gatherables = [];

        foreach ($zone->getGatherResources() as $resource) {
            $normalized = $this->normalize($resource);
            if (null === $normalized) {
                continue;
            }

            $item = $this->findItem($normalized['item']);
            if (null === $item) {
                continue;
            }

            $vein = $this->veinRepository->findOneByZoneAndSlug($zone, $normalized['slug']);
            $stock = $this->effectiveStock($vein, $normalized['capacity'], $normalized['respawn_seconds'], $now);

            $gatherables[] = new GatherableResource(
                $normalized['slug'],
                $item->getName(),
                $normalized['item'],
                $normalized['profession'],
                $stock,
                $normalized['capacity'],
                $stock > 0 ? 0 : $this->respawnRemaining($vein, $normalized['respawn_seconds'], $normalized['capacity'], $now),
                $this->readableCeiling($player, $zone, $normalized['slug'], $normalized['item'], $stock, $normalized['capacity']),
                $this->missingSkillName($player, $normalized['requires_skill']),
            );
        }

        return $gatherables;
    }

    /**
     * Nom lisible de la competence qui manque au joueur, ou `null` s'il l'a
     * (ou si le filon n'en demande aucune).
     *
     * Sans joueur — vue anonyme, admin — le filon n'est jamais annonce comme
     * verrouille : il n'y a personne dont on puisse dire ce qu'il sait.
     */
    private function missingSkillName(?Player $player, ?string $requiredSkill): ?string
    {
        if (null === $player || null === $requiredSkill || $this->hasSkillSlug($player, $requiredSkill)) {
            return null;
        }

        $skill = $this->entityManager->getRepository(Skill::class)->findOneBy(['slug' => $requiredSkill]);

        // Le slug sert de repli : une config qui cite une competence inexistante
        // doit se voir a l'ecran plutot que de s'afficher comme un blanc.
        return null !== $skill ? $skill->getTitle() : $requiredSkill;
    }

    private function hasSkillSlug(Player $player, string $slug): bool
    {
        foreach ($player->getSkills() as $skill) {
            if ($skill->getSlug() === $slug) {
                return true;
            }
        }

        return false;
    }

    /**
     * La bande maximale du filon — **seulement pour qui sait la lire** (ECO-22).
     *
     * L'information exclusive du prospecteur (GAME_ZONE_ACTIONS § 5.5) ne donne
     * ni energie, ni action, ni butin : elle donne de la decision. Elle a donc
     * sa place ici et nulle part ailleurs, et elle reste **nulle** pour qui n'a
     * rien travaille — sinon ce ne serait plus une information exclusive, juste
     * une colonne de plus.
     *
     * Le palier se lit sur le bonus de recolte que l'arbre accorde, faute des
     * quatre paliers de prospection de § 5.5, qui n'existent pas encore. C'est
     * le seul signal de progression de recolte livre a ce jour ; les paliers les
     * remplaceront sans changer le contrat de cette methode.
     */
    private function readableCeiling(?Player $player, Zone $zone, string $veinSlug, string $itemSlug, int $stock, int $capacity): ?Purity
    {
        if (null === $player || !$this->purityDrawer->coversSlug($itemSlug)) {
            return null;
        }

        if ($this->yieldResolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_GATHER) <= 0) {
            return null;
        }

        return $this->purityDrawer->ceiling($stock, $capacity, $zone, $veinSlug);
    }

    /**
     * Recolte une ressource ciblee dans la zone courante.
     *
     * @throws ZoneActionException            si la recolte est refusee (cle de traduction en message)
     * @throws NotEnoughActionEnergyException si l'energie est insuffisante
     */
    public function gather(Player $player, string $slug): GatherResult
    {
        $this->zoneTravelService->settleArrival($player, false);

        if ($player->isTraveling()) {
            throw new ZoneActionException('game.zone.gather.error.traveling');
        }
        if (null !== $player->getFight()) {
            throw new ZoneActionException('game.zone.gather.error.in_fight');
        }
        $zone = $player->getCurrentZone();
        if (null === $zone) {
            throw new ZoneActionException('game.zone.gather.error.no_zone');
        }

        $resource = $this->findResource($zone, $slug);
        if (null === $resource) {
            throw new ZoneActionException('game.zone.gather.error.unknown_resource');
        }

        $item = $this->findItem($resource['item']);
        if (null === $item) {
            // Config de zone incoherente (item inexistant) : refus sans cout.
            throw new ZoneActionException('game.zone.gather.error.unknown_resource');
        }

        // ECO-24c — le gate de competence, et le seul du moteur de recolte.
        //
        // Les six competences hautes de l'arbre du mineur declaraient des
        // `spots` de l'ancien systeme de carte : elles ne gataient plus rien
        // depuis le pivot PBBG, et le service rendait les filons d'une zone
        // sans jamais consulter le joueur (BALANCE §21.5). Un filon declare
        // etait donc accessible a quiconque avait l'energie.
        //
        // Le gate vit dans la **donnee de zone** (`requires_skill:`), pas dans
        // l'arbre : c'est le filon qui sait ce qu'il exige, et la meme
        // competence peut en ouvrir plusieurs sans qu'aucune liste n'ait a
        // rester d'accord avec une autre.
        //
        // Le refus arrive **avant** la depense d'energie : un joueur ne paie
        // jamais pour apprendre qu'il ne peut pas.
        if (null !== $resource['requires_skill'] && !$this->hasSkillSlug($player, $resource['requires_skill'])) {
            throw new ZoneActionException('game.zone.gather.error.missing_skill');
        }

        $now = $this->now();
        $vein = $this->resolveVein($zone, $resource, $now);

        // ZON-37 : **une recolte n'echoue jamais** (GAME_ZONE_ACTIONS, loi 5).
        // Le service refusait ici quand le stock etait a zero ; la loi dit que
        // la vitalite module le **rendement**, pas l'acces, avec un plancher
        // d'une unite. Un filon a sec rend donc peu, il ne ferme pas la porte —
        // c'est ce qui protege le joueur occasionnel de la saturation.

        // L'energie n'est prelevee qu'une fois la recolte garantie possible.
        $this->actionEnergyManager->spend($player, $this->getGatherCost(), false);

        $vitalityBefore = $vein->getStock();
        $quantity = $this->computeYield($player, $resource, $vitalityBefore);
        $remaining = $vitalityBefore - $quantity;
        $vein->setStock($remaining);
        if ($remaining <= 0) {
            $vein->setDepletedAt($now);
        }

        // ECO-22 : la bande se tire **une fois par lot**, sur la vitalite d'avant
        // la recolte. La tirer apres ferait payer au joueur l'epuisement qu'il
        // vient lui-meme de causer ; la tirer par unite ferait d'un seul coup de
        // pioche une poignee de bandes differentes, ce qui n'a aucun sens en
        // fiction et eclaterait l'inventaire.
        $purity = $this->purityDrawer->draw($player, $resource['item'], $vitalityBefore, $resource['capacity'], $zone, $resource['slug']);

        for ($i = 0; $i < $quantity; ++$i) {
            $playerItem = $this->playerItemGenerator->generateFromItemId($item->getId());
            $playerItem->setPurity($purity);
            $this->inventoryHelper->addItem($playerItem, false);
        }

        $entry = new PlayerJournalEntry();
        $entry->setPlayer($player);
        $entry->setType(PlayerJournalEntry::TYPE_GATHERING);
        $entry->setMessage(sprintf('Recolte : %dx %s (%s)', $quantity, $item->getName(), $zone->getName()));
        $entry->setMetadata([
            'zone' => $zone->getSlug(),
            'action' => 'gather',
            'vein' => $resource['slug'],
            'item' => $resource['item'],
            'quantity' => $quantity,
            'purity' => $purity?->value,
        ]);
        $this->entityManager->persist($entry);

        $this->entityManager->flush();
        $this->journalRepository->enforceEntryLimit($player);

        // ZON-38 : la recolte redevient observable. L'evenement part **apres**
        // le flush — un abonne qui lit l'inventaire ou le stock du filon doit
        // voir l'etat d'apres la recolte, pas celui d'avant.
        $this->eventDispatcher->dispatch(
            new ZoneGatherEvent($player, $zone, $resource['slug'], $resource['item'], $quantity),
            ZoneGatherEvent::NAME,
        );

        return new GatherResult(
            $resource['slug'],
            $item->getName(),
            $quantity,
            max(0, $remaining),
            'game.zone.gather.result.success',
            ['%count%' => $quantity, '%item%' => $item->getName()],
        );
    }

    public function getGatherCost(): int
    {
        if (null !== $this->costCache) {
            return $this->costCache;
        }

        $parameter = $this->entityManager->getRepository(Parameter::class)
            ->findOneBy(['name' => self::PARAM_COST]);
        $value = null !== $parameter ? (int) $parameter->getValue() : self::DEFAULT_COST;

        return $this->costCache = $value >= 0 ? $value : self::DEFAULT_COST;
    }

    /**
     * Charge (ou cree) le filon partage et applique un respawn eventuel.
     *
     * @param array{slug: string, item: string, profession: string, capacity: int, respawn_seconds: int, yield_min: int, yield_max: int, requires_skill: string|null} $resource
     */
    private function resolveVein(Zone $zone, array $resource, \DateTimeImmutable $now): ZoneVein
    {
        $vein = $this->veinRepository->findOneByZoneAndSlug($zone, $resource['slug']);
        if (null === $vein) {
            $vein = new ZoneVein($zone, $resource['slug'], $resource['capacity']);
            $this->entityManager->persist($vein);

            return $vein;
        }

        // ZON-37 : la repousse deja due est encaissee avant la recolte, et
        // l'ancre avance du temps reellement converti en unites.
        $regenerated = self::regenerate(
            $vein->getStock(),
            $resource['capacity'],
            $resource['respawn_seconds'],
            $vein->getRegeneratedAt() ?? $vein->getDepletedAt(),
            $now,
        );

        $vein->setStock($regenerated['stock']);
        $vein->setRegeneratedAt($regenerated['anchor']);
        if ($regenerated['stock'] > 0) {
            $vein->setDepletedAt(null);
        }

        return $vein;
    }

    private function effectiveStock(?ZoneVein $vein, int $capacity, int $respawnSeconds, \DateTimeImmutable $now): int
    {
        if (null === $vein) {
            return $capacity;
        }

        // Lecture sans effet de bord : la repousse est appliquee en memoire.
        return self::regenerate(
            $vein->getStock(),
            $capacity,
            $respawnSeconds,
            $vein->getRegeneratedAt() ?? $vein->getDepletedAt(),
            $now,
        )['stock'];
    }

    /**
     * Repousse continue d'un filon (ZON-37).
     *
     * > « La regeneration n'est pas une phase, c'est un debit permanent […]
     * > chaque filon rend `R = capacity x 3600 / respawn_seconds` unites par
     * > heure, **en continu**, et `capacity` n'est qu'un tampon. »
     * > — [GAME_WORLD.md](../../../docs/GAME_WORLD.md) §3.5
     *
     * Le moteur faisait l'inverse : un filon ne repoussait que s'il tombait
     * **exactement a zero**, attendait le delai plein, puis revenait plein d'un
     * bloc. Une entame partielle n'etait **jamais** reconstituee — un filon
     * descendu de 72 a 42 y restait indefiniment. Tout le calibrage de
     * BALANCE §22 decrivait donc un systeme qui n'existait pas.
     *
     * L'ancre n'avance **pas** jusqu'a `now` : elle avance du temps exactement
     * consomme par les unites rendues. Sans cela, chaque lecture jetterait la
     * fraction en cours et un filon souvent consulte ne repousserait jamais.
     *
     * @param \DateTimeImmutable|null $anchor derniere conversion temps -> unites
     *
     * @return array{stock: int, anchor: \DateTimeImmutable}
     */
    public static function regenerate(int $stock, int $capacity, int $respawnSeconds, ?\DateTimeImmutable $anchor, \DateTimeImmutable $now): array
    {
        $stock = max(0, min($stock, $capacity));

        // Sans ancre, le filon est repute a jour : on ne lui doit rien.
        if (null === $anchor || $respawnSeconds <= 0 || $capacity <= 0 || $stock >= $capacity) {
            return ['stock' => $stock, 'anchor' => $now];
        }

        $elapsed = $now->getTimestamp() - $anchor->getTimestamp();
        if ($elapsed <= 0) {
            return ['stock' => $stock, 'anchor' => $anchor];
        }

        // Secondes que coute une unite : `respawn_seconds` remplit `capacity`.
        $secondsPerUnit = $respawnSeconds / $capacity;
        $units = (int) floor($elapsed / $secondsPerUnit);

        if ($units <= 0) {
            return ['stock' => $stock, 'anchor' => $anchor];
        }

        $granted = min($units, $capacity - $stock);

        return [
            'stock' => $stock + $granted,
            // Le reliquat se reporte : on n'avance que du temps facture.
            'anchor' => $anchor->modify(sprintf('+%d seconds', (int) round($granted * $secondsPerUnit))),
        ];
    }

    private function respawnRemaining(?ZoneVein $vein, int $respawnSeconds, int $capacity, \DateTimeImmutable $now): int
    {
        if (null === $vein) {
            return 0;
        }
        $anchor = $vein->getRegeneratedAt() ?? $vein->getDepletedAt();
        if (null === $anchor || $respawnSeconds <= 0) {
            return 0;
        }

        // ZON-37 : ce n'est plus « quand le filon reviendra plein » mais
        // « quand tombe la prochaine unite ». La repousse etant continue, la
        // seconde question est la seule qui ait encore un sens.
        $secondsPerUnit = $respawnSeconds / max(1, $capacity);
        $elapsed = max(0, $now->getTimestamp() - $anchor->getTimestamp());

        return max(0, (int) ceil($secondsPerUnit - fmod($elapsed, $secondsPerUnit)));
    }

    /**
     * Rendement d'une recolte : tirage declare par le filon, puis bonus de
     * rendement du joueur (passifs de competence).
     *
     * Le bonus s'applique **avant** la borne de stock : il augmente ce qu'une
     * action rapporte, il ne permet pas de prendre plus que ce que le filon
     * contient — le stock partage reste le point de tension de la ressource.
     *
     * @param array{slug: string, item: string, profession: string, capacity: int, respawn_seconds: int, yield_min: int, yield_max: int, requires_skill: string|null} $resource
     */
    private function computeYield(Player $player, array $resource, int $stock): int
    {
        $min = $resource['yield_min'];
        $max = $resource['yield_max'];
        $span = $max - $min;
        $yield = $min + ($span > 0 ? $this->roll($span + 1) - 1 : 0);

        $yield = $this->yieldResolver->applyBonus($player, ActionYieldResolver::CATEGORY_GATHER, $yield);

        // ZON-37 — la vitalite module le rendement (GAME_ZONE_ACTIONS, loi 5).
        // Jusqu'ici le stock ne servait qu'a **plafonner** : un filon a 70/72 et
        // un filon a 3/72 rendaient autant, et la rarete ne se voyait qu'au
        // moment ou l'acces se fermait. Desormais le filon presse rend moins,
        // continument — c'est le signal que la purete (ECO-22) et la Paleur
        // (FOY-11) liront, et sans lui les deux jalons tourneraient a vide.
        $capacity = $resource['capacity'];
        if ($capacity > 0 && $stock < $capacity) {
            $yield = (int) round($yield * ($stock / $capacity));
        }

        // Plancher d'une unite : une recolte n'echoue jamais, meme a sec.
        return max(1, $stock > 0 ? min($yield, $stock) : 1);
    }

    /**
     * @return array{slug: string, item: string, profession: string, capacity: int, respawn_seconds: int, yield_min: int, yield_max: int, requires_skill: string|null}|null
     */
    private function findResource(Zone $zone, string $slug): ?array
    {
        foreach ($zone->getGatherResources() as $resource) {
            $normalized = $this->normalize($resource);
            if (null !== $normalized && $normalized['slug'] === $slug) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * @param array<array-key, mixed> $resource
     *
     * @return array{slug: string, item: string, profession: string, capacity: int, respawn_seconds: int, yield_min: int, yield_max: int, requires_skill: string|null}|null
     */
    private function normalize(array $resource): ?array
    {
        $slug = isset($resource['slug']) ? (string) $resource['slug'] : '';
        $item = isset($resource['item']) ? (string) $resource['item'] : '';
        if ('' === $slug || '' === $item) {
            return null;
        }

        // FOY-17b — le facteur de monde met a l'echelle l'**ampleur** du filon,
        // jamais son rythme : `capacity` x W, `respawn_seconds` inchange. Un
        // serveur plus peuple a des filons plus **gros**, pas plus **rapides**
        // (BALANCE § 22.4). Le debit suit mecaniquement, mais la cadence de
        // repousse — le rythme de la maree, en fiction — reste la meme pour tout
        // le monde.
        //
        // W s'indexe sur la population **globale** et reste aveugle a la
        // frequentation de ce filon-ci : un filon qui donnerait plus a mesure
        // qu'on le presse annulerait sa propre rarete.
        $capacity = max(1, (int) round(
            max(1, (int) ($resource['capacity'] ?? self::DEFAULT_CAPACITY)) * $this->worldScaleService->current(),
        ));
        $respawn = max(0, (int) ($resource['respawn_seconds'] ?? self::DEFAULT_RESPAWN_SECONDS));
        $yieldMin = max(1, (int) ($resource['yield_min'] ?? self::DEFAULT_YIELD_MIN));
        $yieldMax = max($yieldMin, (int) ($resource['yield_max'] ?? self::DEFAULT_YIELD_MAX));

        // ECO-24c — un filon sans `requires_skill` reste ouvert a tous. Le gate
        // est **opt-in** : rien de ce qui etait accessible ne se ferme (meme
        // decision A que le gate de services des foyers, FOY-05).
        $requiresSkill = isset($resource['requires_skill']) ? (string) $resource['requires_skill'] : '';

        return [
            'slug' => $slug,
            'item' => $item,
            'profession' => isset($resource['profession']) ? (string) $resource['profession'] : 'gathering',
            'capacity' => $capacity,
            'respawn_seconds' => $respawn,
            'yield_min' => $yieldMin,
            'yield_max' => $yieldMax,
            'requires_skill' => '' !== $requiresSkill ? $requiresSkill : null,
        ];
    }

    private function findItem(string $slug): ?Item
    {
        return $this->entityManager->getRepository(Item::class)->findOneBy(['slug' => $slug]);
    }

    /**
     * Instant courant — surchargeable en test pour un respawn deterministe.
     */
    protected function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    /**
     * Tirage aleatoire 1..max — surchargeable en test pour un rendement deterministe.
     */
    protected function roll(int $max): int
    {
        return random_int(1, max(1, $max));
    }
}
