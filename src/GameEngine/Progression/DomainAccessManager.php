<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\App\PlayerDomainAccess;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\GameEngine\Notification\NotificationService;
use App\Helper\InventoryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Ouvrir un arbre, et savoir s'il l'est (ONB-08).
 *
 * Le seul endroit qui ecrit dans `player_domain_access`, et le seul qu'on
 * interroge pour repondre « ce personnage peut-il apprendre dans cet arbre ? ».
 *
 * La doctrine que ce service doit tenir, litteralement (GAME_ONBOARDING § 6.3) :
 *
 * - **le parchemin est un cout, jamais un verrou** — rien ici ne consulte le
 *   peuple, la faction, la progression ni un choix anterieur ;
 * - **les 32 sont cumulables** — ouvrir n'a aucun effet de bord sur les autres ;
 * - **l'ouverture est idempotente** — la relire ne cree pas de doublon ;
 * - **les verbes elementaires restent libres** — marcher, voyager, explorer,
 *   parler, ramasser et se battre a mains nues ne passent jamais par ici.
 *
 * Un nœud **partage** entre plusieurs arbres (`Skill::domains` est un
 * ManyToMany) se prend des qu'**un seul** de ses arbres est ouvert : c'est la
 * regle « plusieurs chemins pour la meme chose » de ONB-20b, et elle vaut deja
 * pour l'apprentissage.
 */
class DomainAccessManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
        private readonly EquipmentPortCatalog $portCatalog,
        private readonly InventoryHelper $inventoryHelper,
    ) {
    }

    public function isOpen(Player $player, Domain $domain): bool
    {
        return $player->hasOpenedDomain($domain);
    }

    /**
     * Ouvre l'arbre. Retourne `true` si c'est cette lecture qui l'a ouvert.
     *
     * Le booleen n'est pas cosmetique : l'ouverture est un **moment** qu'on
     * notifie (ONB-09), et un parchemin relu ne doit pas rejouer l'annonce.
     */
    public function open(Player $player, Domain $domain): bool
    {
        if ($player->hasOpenedDomain($domain)) {
            return false;
        }

        $access = new PlayerDomainAccess($player, $domain);
        $player->addDomainAccess($access);
        $this->entityManager->persist($access);

        // ONB-20b — ouvrir un arbre livre immediatement son **kit de port**.
        // C'est ce qui garantit le plancher jour 1 : on ne donne jamais une
        // arme qu'on ne peut pas tenir. Le cout reel est le parchemin, jamais
        // les points.
        $this->grantPortKit($player, $domain);

        // OBJ-05 — ouvrir un arbre de recolte livre l'outil de palier 1.
        // C'est la garantie anti-mur de GAME_ITEMS §4.3 : la recolte exige
        // desormais un outil, et « une recolte n'echoue jamais » ne tient que
        // si l'outil arrive avec l'arbre. Le cout reel est le parchemin,
        // jamais l'outil.
        $this->grantGatherToolKit($player, $domain);

        // ONB-09 — l'ouverture est **notifiee**. Un arbre qui apparaitrait
        // simplement dans un menu se lirait comme un changement d'interface,
        // alors que c'est le seul moment de la boucle *parchemin -> arbre ->
        // geste* ou quelque chose se gagne.
        $this->announce($player, $domain);

        return true;
    }

    /**
     * Livre les nœuds d'entree gratuits de l'arbre (ONB-20b).
     *
     * Le kit se lit dans le **graphe reel** — les competences du domaine dont
     * le slug est un echelon 1 — et non dans une table de correspondance entre
     * cles de fixtures et domaines. Le catalogue declare les familles par cle
     * de fixture, qui n'existe pas a l'execution ; passer par les competences
     * evite d'inventer un second identifiant de domaine, et fait suivre
     * automatiquement tout arbre qui se met a enseigner une famille.
     */
    public function grantPortKit(Player $player, Domain $domain): int
    {
        $rungOne = $this->portCatalog->rungOneSlugs();

        $granted = 0;
        foreach ($domain->getSkills() as $skill) {
            if (!\in_array($skill->getSlug(), $rungOne, true) || $player->hasSkill($skill)) {
                continue;
            }

            $player->addSkill($skill);
            ++$granted;
        }

        return $granted;
    }

    /**
     * Livre l'outil de palier 1 des professions de recolte que l'arbre
     * enseigne (OBJ-05).
     *
     * Le type d'outil se lit dans le **graphe reel** — les nœuds
     * `tool_slot.unlock` du domaine — exactement comme le kit de port se lit
     * dans les echelons 1 : aucune table de correspondance entre domaines et
     * outils a tenir a jour. Seuls les types de recolte sont concernes
     * (GATHER_TOOL_TYPES) : l'outil d'artisanat reste un achat, la recolte
     * seule porte la garantie « une recolte n'echoue jamais ».
     *
     * Un joueur qui possede deja un outil du type — achete, ou herite d'une
     * autre ouverture — n'en recoit pas un second : l'octroi comble un
     * manque, il ne remplit pas un entrepot.
     */
    public function grantGatherToolKit(Player $player, Domain $domain): int
    {
        $granted = 0;

        foreach ($this->gatherToolTypes($domain) as $toolType) {
            if ($this->ownsToolOfType($player, $toolType)) {
                continue;
            }

            $item = $this->entityManager->getRepository(Item::class)
                ->findOneBy(['toolType' => $toolType, 'toolTier' => Item::TOOL_TIER_BRONZE]);
            if (null === $item) {
                continue;
            }

            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($item);
            $playerItem->setNbUsages($item->getNbUsages());

            // L'emplacement s'ouvre et l'outil s'y range : un outil offert
            // qu'il faudrait encore savoir equiper reconstruirait le mur que
            // l'octroi vient d'abattre.
            $player->unlockToolSlot($toolType);
            $gearBit = PlayerItem::TOOL_TYPE_TO_GEAR[$toolType] ?? null;
            if (null !== $gearBit && !$this->hasEquippedToolBit($player, $gearBit)) {
                $playerItem->setGear($gearBit);
            }

            // Le point de passage oblige d'ECO-01 : tout objet entre par
            // InventoryHelper, qui ecrit dans le sac du joueur de **session**.
            // C'est toujours le bon ici — ouvrir un arbre est le geste du
            // porteur du parchemin, il n'existe aucun chemin ou l'on ouvre
            // l'arbre d'autrui.
            $this->inventoryHelper->addItem($playerItem, false);
            ++$granted;
        }

        return $granted;
    }

    /**
     * @return list<string> les types d'outil de recolte que l'arbre enseigne
     */
    private function gatherToolTypes(Domain $domain): array
    {
        $types = [];
        foreach ($domain->getSkills() as $skill) {
            foreach ($skill->getActions() ?? [] as $action) {
                if (!\is_array($action) || ($action['action'] ?? null) !== 'tool_slot.unlock') {
                    continue;
                }
                $slot = $action['slot'] ?? null;
                if (\is_string($slot) && \in_array($slot, Item::GATHER_TOOL_TYPES, true) && !\in_array($slot, $types, true)) {
                    $types[] = $slot;
                }
            }
        }

        return $types;
    }

    private function ownsToolOfType(Player $player, string $toolType): bool
    {
        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                $generic = $playerItem->getGenericItem();
                if ($generic->isTool() && $generic->getToolType() === $toolType) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasEquippedToolBit(Player $player, int $gearBit): bool
    {
        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem->getGear() & $gearBit) {
                    return true;
                }
            }
        }

        return false;
    }

    private function announce(Player $player, Domain $domain): void
    {
        // Une annonce ratee ne doit jamais annuler une ouverture : le joueur a
        // consomme son parchemin, et perdre l'arbre pour un hub injoignable
        // serait un vol.
        try {
            $this->notificationService->notify(
                $player,
                'domain_opened',
                'Un arbre s\'ouvre',
                sprintf('Vous avez déchiffré la voie du %s. Ce qu\'on y apprend reste à apprendre.', $domain->getTitle()),
                '📜',
                '/game/skills',
            );
        } catch (\Throwable $e) {
            $this->logger->warning('Ouverture de domaine non annoncee : {error}', [
                'error' => $e->getMessage(),
                'player' => $this->identify($player),
                'domain' => $domain->getSlug(),
            ]);
        }
    }

    /**
     * L'identifiant du personnage, ou `null` s'il n'en a pas encore.
     *
     * **Le rattrapage ne doit pas pouvoir echouer a son tour.** `Player::$id`
     * est une propriete typee non initialisee tant que Doctrine n'a pas ecrit :
     * lire `getId()` sur un personnage non persiste leve une `Error`, qui
     * remonterait **depuis le `catch`** et annulerait precisement l'ouverture
     * que ce bloc protege. Le defaut se lisait d'autant plus mal qu'il ne
     * survient que sur le chemin d'echec.
     *
     * L'idiome est deja celui de `Player::getSkillId()`.
     */
    private function identify(Player $player): ?int
    {
        try {
            return $player->getId();
        } catch (\Error) {
            return null;
        }
    }

    /**
     * @return Domain[] les arbres ouverts, dans l'ordre ou ils l'ont ete
     */
    public function openedDomains(Player $player): array
    {
        $domains = [];
        foreach ($player->getDomainAccesses() as $access) {
            $domains[] = $access->getDomain();
        }

        return $domains;
    }

    /**
     * Ce nœud est-il accessible, du seul point de vue de l'ouverture d'arbre ?
     *
     * Une competence **sans domaine** reste apprenable par tout le monde : c'est
     * la frontiere, et elle est volontairement large. Fermer ce cas ferait de la
     * moindre competence transverse un peage, et le jeu deviendrait « une parade
     * de verrous » que le cadrage refuse explicitement.
     */
    public function isSkillReachable(Player $player, Skill $skill): bool
    {
        $domains = $skill->getDomains();
        if (\count($domains) === 0) {
            return true;
        }

        foreach ($domains as $domain) {
            if ($player->hasOpenedDomain($domain)) {
                return true;
            }
        }

        return false;
    }
}
