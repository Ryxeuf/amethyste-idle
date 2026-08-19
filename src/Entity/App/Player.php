<?php

namespace App\Entity\App;

use App\Entity\App\Traits\CharacterStatsTrait;
use App\Entity\App\Traits\CoordinatesTrait;
use App\Entity\CharacterInterface;
use App\Entity\Game\Domain;
use App\Entity\Game\Faction;
use App\Entity\Game\Mount;
use App\Entity\Game\Race;
use App\Entity\Game\Skill;
use App\Entity\User;
use App\Enum\CoachMark;
use App\Enum\CraftSpecialization;
use App\Repository\PlayerRepository;
use App\Service\PlayerNameNormalizer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'player')]
#[ORM\Index(columns: ['map_id'], name: 'idx_player_map')]
#[ORM\Index(columns: ['current_zone_id'], name: 'idx_player_current_zone')]
#[ORM\Index(columns: ['fight_id'], name: 'idx_player_fight')]
#[ORM\Index(columns: ['user_id'], name: 'idx_player_user')]
#[ORM\Index(columns: ['updated_at'], name: 'idx_player_updated_at')]
#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player implements CharacterInterface
{
    use CharacterStatsTrait;
    use CoordinatesTrait;
    use TimestampableEntity;

    /**
     * Plafond d'energie d'action par defaut : 24 h de regeneration
     * (86400 s / `zone.energy.regen_seconds` a 360 s = 240 points).
     *
     * Le plafond couvre volontairement une journee entiere : en dessous, le
     * joueur qui ne se connecte qu'une fois par 24 h perd l'energie regeneree
     * au-dela du plafond, ce qui penalise l'absence longue (cf. docs/BALANCE.md
     * section 8). Ce plafond ne donne rien de plus au joueur assidu, qui
     * dispose du meme budget quotidien — il le depense simplement plus tot.
     */
    public const DEFAULT_MAX_ACTION_ENERGY = 240;

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->inventories = new ArrayCollection();
        $this->domainExperiences = new ArrayCollection();
        $this->quests = new ArrayCollection();
        $this->completedQuests = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->statusEffects = new ArrayCollection();
        $this->bestiaryEntries = new ArrayCollection();
        $this->resourceCatalogEntries = new ArrayCollection();
        $this->achievements = new ArrayCollection();
        $this->craftSpecializations = new ArrayCollection();
        $this->combatBranches = new ArrayCollection();
        $this->domainAccesses = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255, unique: true)]
    private string $name;

    /**
     * ONB-06 — la forme sous laquelle deux noms sont « le meme nom ».
     *
     * PostgreSQL compare des octets : l'unicite sur `name` laissait passer
     * « Claire » a cote de « claire », et « Clairе » ecrit avec un « е »
     * cyrillique a cote des deux. Cette colonne porte l'index unique reel ;
     * `name` reste le nom d'affichage, tel que le joueur l'a tape.
     *
     * Maintenue par `setName()` : elle ne peut pas se desynchroniser.
     */
    #[ORM\Column(name: 'normalized_name', type: 'string', length: 255, unique: true, nullable: true)]
    private ?string $normalizedName = null;

    #[ORM\Column(name: 'max_life', type: 'integer')]
    private int $maxLife;

    #[ORM\Column(name: 'energy', type: 'integer')]
    private int $energy;

    #[ORM\Column(name: 'hit', type: 'integer', options: ['default' => 50])]
    private int $hit = 50;

    #[ORM\Column(name: 'max_energy', type: 'integer')]
    private int $maxEnergy;

    #[ORM\Column(name: 'speed', type: 'integer', options: ['default' => 10])]
    private int $speed = 10;

    #[ORM\Column(name: 'is_moving', type: 'boolean', options: ['default' => false])]
    private bool $isMoving = false;

    #[ORM\Column(name: 'class_type', type: 'string', length: 255)]
    private string $classType;

    #[ORM\ManyToOne(targetEntity: Race::class)]
    #[ORM\JoinColumn(name: 'race_id', referencedColumnName: 'id', nullable: true)]
    private ?Race $race = null;

    #[ORM\ManyToOne(targetEntity: Mount::class)]
    #[ORM\JoinColumn(name: 'active_mount_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Mount $activeMount = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Map::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id')]
    private ?Map $map = null;

    /**
     * Zone courante du joueur — reference de position du modele PBBG (ZON-03).
     * Remplace progressivement les coordonnees `x.y`, qui subsistent uniquement
     * pour la carte gelee jusqu'a sa suppression (ZON-21).
     */
    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'current_zone_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Zone $currentZone = null;

    /**
     * Les encarts de coach deja lus (ONB-17).
     *
     * Un tableau de slugs, et **pas une entite** : il n'y a rien a interroger,
     * rien a dater, rien a joindre. Une table pour dix booleens par personnage
     * couterait une jointure a chaque rendu d'ecran pour ne jamais servir a
     * autre chose.
     *
     * Le coach est **par personnage** : deux personnages du meme joueur
     * decouvrent le jeu chacun a son rythme, et le second a souvent une raison
     * d'etre — essayer autre chose.
     *
     * @var list<string>
     */
    #[ORM\Column(name: 'seen_coach_marks', type: 'json', options: ['default' => '[]'])]
    private array $seenCoachMarks = [];

    /**
     * Foyer d'attache — la zone ou le joueur a travaille pendant l'acte I (ONB-13).
     *
     * GAME_ONBOARDING § 4.4 amende GAME_WORLD § 13.1 : **il ne se choisit pas,
     * il se gagne**. Le deriver de la race revenait a demander une orientation
     * de carriere avant toute experience de jeu.
     *
     * Il n'ouvre et ne ferme **rien**. Le lire pour autoriser un acces serait
     * exactement ce que l'amendement evite : une classe deguisee, entree par la
     * fenetre. `HomeSettlementTest` tient la liste des fichiers autorises a le
     * lire.
     */
    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'home_zone_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Zone $homeZone = null;

    /**
     * Quand le foyer a ete constate.
     *
     * Distinct de `homeZone !== null` : l'arc `intro` est rejouable, et sans
     * cette date un second passage redonnerait le cran de renommee.
     */
    #[ORM\Column(name: 'home_zone_claimed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $homeZoneClaimedAt = null;

    /**
     * Voyage en cours (pivot PBBG, ZON-06) : destination + horodatage d'arrivee.
     * L'arrivee est resolue paresseusement par ZoneTravelService::settleArrival.
     */
    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'travel_to_zone_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Zone $travelToZone = null;

    #[ORM\Column(name: 'travel_arrives_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $travelArrivesAt = null;

    /**
     * Depart du voyage en cours : sans lui, la duree totale du trajet est
     * inconnue au rendu (la liaison empruntee n'est pas conservee) et une
     * barre de progression ne peut pas situer le temps deja parcouru.
     */
    #[ORM\Column(name: 'travel_started_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $travelStartedAt = null;

    /**
     * Energie d'action PBBG (ZON-07) : gate l'acces aux rencontres (explorer,
     * chasser, recolter, rejoindre un evenement...), JAMAIS le combat lui-meme.
     * Distincte de `energy` (ressource de combat consommee par les sorts).
     * Regeneration paresseuse via ActionEnergyManager (aucun cron).
     */
    #[ORM\Column(name: 'action_energy', type: 'integer', options: ['default' => self::DEFAULT_MAX_ACTION_ENERGY])]
    private int $actionEnergy = self::DEFAULT_MAX_ACTION_ENERGY;

    #[ORM\Column(name: 'max_action_energy', type: 'integer', options: ['default' => self::DEFAULT_MAX_ACTION_ENERGY])]
    private int $maxActionEnergy = self::DEFAULT_MAX_ACTION_ENERGY;

    /**
     * Dernier point de calcul de la regeneration (null = jamais calcule).
     */
    #[ORM\Column(name: 'action_energy_updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $actionEnergyUpdatedAt = null;

    /**
     * Derniere activite reelle du personnage (FOY-17).
     *
     * Mis a jour a la **depense d'energie**, jamais a la connexion : agir sur le
     * monde coute de l'energie, s'y connecter non. Remplace le proxy
     * `Player::updatedAt` qu'utilisait `InfluenceAntiExploit`, et que le code
     * lui-meme signalait comme approximatif — `updatedAt` est un champ de cycle
     * de vie Doctrine, il bouge sur des ecritures **systeme** (regeneration,
     * respawn, backfill), si bien qu'une seule connexion valait sept jours
     * d'activite.
     */
    #[ORM\Column(name: 'last_activity_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastActivityAt = null;

    /**
     * Semaine dont le tableau de bord a deja ete vu (RET-09).
     *
     * Le lundi est un **etat**, pas un evenement : la rotation se constate a la
     * lecture, en comparant cette clef a celle de la semaine courante. Aucune
     * tache planifiee ne la touche — c'est ce qui evite une sixieme horloge
     * hebdomadaire (contrat RET-07).
     *
     * `null` vaut « jamais vu » et non « semaine ratee » : un personnage neuf
     * est simplement inscrit sur la semaine courante, sans recap. Raconter une
     * semaine qu'on n'a pas jouee serait un mensonge du premier jour.
     */
    #[ORM\Column(name: 'hub_week_key', type: 'string', length: 8, nullable: true)]
    private ?string $hubWeekKey = null;

    /**
     * Cumul de l'energie d'action depensee depuis la creation (FOY-17).
     *
     * C'est la matiere premiere du dimensionnement du monde : la somme sur tous
     * les personnages, relevee chaque jour par `WorldLoadService`, donne la
     * charge du monde (BALANCE § 22.5). Porte par le joueur et non par un
     * compteur global pour ne pas faire d'une ligne unique un point de
     * contention sur le chemin le plus chaud du jeu.
     *
     * Monotone croissante : jamais remise a zero, la difference entre deux
     * releves suffit.
     */
    #[ORM\Column(name: 'action_energy_spent_total', type: 'bigint', options: ['default' => 0])]
    private string $actionEnergySpentTotal = '0';

    /**
     * Ancre de la regeneration paresseuse des PV hors combat (ZON-12).
     * Reinitialisee a la sortie de chaque combat : la regen ne compte le temps
     * ecoule qu'a partir du moment ou le joueur quitte le combat blesse.
     * null = jamais calcule.
     */
    #[ORM\Column(name: 'life_updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lifeUpdatedAt = null;

    /**
     * Ancre de regeneration des PM hors combat (ARC-04a).
     *
     * Meme mecanique que `lifeUpdatedAt`, et c'est la symetrie qui compte :
     * *les PV paient les coups recus, les PM paient les gestes faits, et les
     * deux se rechargent en temps reel*. Avant ce jalon les PM ne revenaient
     * qu'en lancant des sorts — c'est-a-dire en depensant ce qu'on cherchait a
     * recuperer.
     *
     * null = jamais calcule.
     */
    #[ORM\Column(name: 'energy_updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $energyUpdatedAt = null;

    /**
     * Suspension d'acces aux canaux d'echange entre joueurs (ECO-16b).
     *
     * Sanction **proportionnee** : le bannissement de compte existe deja mais
     * coupe tout. Un joueur qui truque des prix doit pouvoir continuer a jouer
     * pendant que le marche lui est ferme.
     */
    #[ORM\Column(name: 'trade_suspended_until', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $tradeSuspendedUntil = null;

    /**
     * Maitre du jeu (MJ).
     *
     * Marque **le personnage**, pas le compte : un membre du staff joue
     * ordinairement avec son personnage habituel et bascule sur son personnage
     * MJ pour animer. Le drapeau n'ouvre aucun ecran d'administration (cela
     * reste l'affaire de `User::roles`) ; il leve les regulateurs de rythme du
     * PBBG — energie d'action, regeneration des PV, duree de voyage — et se
     * voit en jeu (sceau « MJ » a cote du nom).
     *
     * Contrepartie : un MJ ne pese pas sur le monde. Son energie etant
     * gratuite, le compter dans la charge mondiale (FOY-17) ou dans
     * l'assiduite hebdomadaire (RET-04) fausserait les deux mesures.
     */
    #[ORM\Column(name: 'is_game_master', type: 'boolean', options: ['default' => false])]
    private bool $gameMaster = false;

    /**
     * Le MJ se retire des ecrans des joueurs.
     *
     * Deux metiers dans un seul personnage : animer, ou l'inverse — observer une
     * zone, arbitrer un litige, regarder un joueur bloque sans influencer ce
     * qu'il fait. Le mode se bascule a volonte depuis la console MJ ; il ne
     * change rien aux privileges, seulement a ce que les autres voient.
     *
     * Le retrait ne vaut que pour les listes de rencontre (presence de zone,
     * recherche de joueurs). Une fiche de profil ouverte par son lien reste
     * lisible : cacher un personnage qu'on cite par son nom ne protegerait rien
     * et casserait les liens deja echanges.
     */
    #[ORM\Column(name: 'is_game_master_incognito', type: 'boolean', options: ['default' => false])]
    private bool $gameMasterIncognito = false;

    #[ORM\Column(name: 'lastCoordinates', type: 'string')]
    private string $lastCoordinates;

    #[ORM\OneToMany(targetEntity: Inventory::class, mappedBy: 'player')]
    private $inventories;

    #[ORM\OneToMany(targetEntity: DomainExperience::class, mappedBy: 'player')]
    private $domainExperiences;

    #[ORM\ManyToOne(targetEntity: Fight::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'fight_id', referencedColumnName: 'id')]
    private ?Fight $fight = null;

    #[ORM\OneToMany(targetEntity: PlayerQuest::class, mappedBy: 'player')]
    private $quests;

    #[ORM\OneToMany(targetEntity: PlayerQuestCompleted::class, mappedBy: 'player')]
    private $completedQuests;

    #[ORM\ManyToMany(targetEntity: Skill::class)]
    #[ORM\JoinTable(name: 'player_skill')]
    private $skills;

    #[ORM\Column(name: 'gils', type: 'integer', options: ['default' => 0])]
    private int $gils = 0;

    /**
     * FAC-04b : l'essence — le carburant de la commodite, rendu par la fonte
     * d'une materia (GAME_WORLD 12.2). Depensable **uniquement en services**
     * (reparations, entretien, accelerations) : l'invariant se tient par
     * l'absence de tout chemin d'achat d'objet en essence, et un test de
     * contrat le verifie.
     */
    #[ORM\Column(name: 'essence', type: 'integer', options: ['default' => 0])]
    private int $essence = 0;

    /**
     * FAC-06 : le compteur d'explorations nocturnes — le geste qualifiant de
     * l'approche des Ruelles. Il ne sert qu'avant le premier contact : la
     * ligne de reputation creee, il cesse de compter.
     */
    #[ORM\Column(name: 'night_explorations', type: 'integer', options: ['default' => 0])]
    private int $nightExplorations = 0;

    #[ORM\Column(name: 'respec_count', type: 'integer', options: ['default' => 0])]
    private int $respecCount = 0;

    #[ORM\Column(name: 'prestige_title', type: 'string', length: 100, nullable: true)]
    private ?string $prestigeTitle = null;

    #[ORM\Column(name: 'renown_score', type: 'integer', options: ['default' => 0])]
    private int $renownScore = 0;

    /**
     * Colonne heritee — le metier unique et irreversible d'avant DOM-04.
     *
     * Elle n'est plus lue par le jeu : la specialisation vit desormais dans
     * `craftSpecializations`, une ligne par arbre. Elle reste en base parce que
     * la migration de donnees s'appuie dessus, et qu'une colonne qu'on retire le
     * jour meme ou on la migre ne laisse aucun recours si la migration s'est
     * trompee.
     */
    #[ORM\Column(name: 'craft_specialization', type: 'string', length: 20, nullable: true, enumType: CraftSpecialization::class)]
    private ?CraftSpecialization $craftSpecialization = null;

    /**
     * La branche prise dans chaque arbre d'artisanat (DOM-04).
     *
     * Aucune exclusivite entre arbres : on peut etre forgeron d'armes **et**
     * alchimiste des remedes. L'exclusivite est au sein de l'arbre, et c'est le
     * schema qui la tient (contrainte unique `(player, craft)`).
     *
     * @var Collection<int, PlayerCraftSpecialization>
     */
    #[ORM\OneToMany(targetEntity: PlayerCraftSpecialization::class, mappedBy: 'player', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $craftSpecializations;

    /**
     * La branche choisie dans chaque arbre de combat (ARC-14b).
     *
     * Meme forme que les metiers, et pour la meme raison : **une ligne par
     * arbre**, jamais une par personnage. Mener les vingt-quatre arbres reste
     * permis — le renoncement se joue *dans* l'arbre, jamais entre eux
     * (GAME_DOMAINS § 1). L'exclusivite est tenue par le schema, contrainte
     * unique `(player, domain)`.
     *
     * @var Collection<int, PlayerCombatBranch>
     */
    #[ORM\OneToMany(targetEntity: PlayerCombatBranch::class, mappedBy: 'player', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $combatBranches;

    /**
     * Quand le **premier voyage offert** a ete depense (ONB-10).
     *
     * `null` veut dire qu'il reste a prendre. Le premier voyage d'un personnage
     * est instantane, **une seule fois** : sans cela, l'acte I s'arrete net avant
     * la premiere recolte, sur une attente de quatre a dix minutes que le joueur
     * n'a aucune raison de comprendre — il n'a pas encore appris que le voyage
     * coute du temps reel, c'est l'etape 9 qui le lui enseigne.
     *
     * Une date plutot qu'un booleen : elle se relit dans les indicateurs du
     * tunnel (ONB-19) sans qu'on ait a la deduire d'autre chose.
     */
    #[ORM\Column(name: 'first_travel_spent_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $firstTravelSpentAt = null;

    /**
     * Les arbres que ce personnage a ouverts (ONB-08).
     *
     * Aucune exclusivite : les 32 arbres sont cumulables, et en ouvrir un n'en
     * ferme aucun autre. Cette collection dit ce qu'on a **appris a apprendre**,
     * pas ce qu'on sait faire — les nœuds restent a prendre un par un.
     *
     * @var Collection<int, PlayerDomainAccess>
     */
    #[ORM\OneToMany(targetEntity: PlayerDomainAccess::class, mappedBy: 'player', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $domainAccesses;

    /**
     * Les couleurs qu'on porte (FAC-01).
     *
     * GAME_WORLD § 6.4 c : « les bonus de statistiques des paliers deviennent
     * un patronage — on porte les couleurs d'une seule faction a la fois. Un
     * palier de reputation ouvre des portes ; il n'empile jamais de la
     * puissance. »
     *
     * Une reference unique et pas une collection : c'est la forme du champ qui
     * tient l'exclusivite. Une collection aurait laisse la regle a la charge du
     * service, et un seul appelant distrait aurait suffi a empiler quatre
     * factions sans qu'aucun ecran ne le dise.
     */
    #[ORM\ManyToOne(targetEntity: Faction::class)]
    #[ORM\JoinColumn(name: 'patron_faction_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Faction $patronFaction = null;

    #[ORM\Column(name: 'discovered_recipes', type: 'json', nullable: true)]
    private ?array $discoveredRecipes = [];

    #[ORM\Column(name: 'unlocked_tool_slots', type: 'json', options: ['default' => '[]'])]
    private array $unlockedToolSlots = [];

    /**
     * Le joueur a refuse l'onboarding (ONB-14).
     *
     * Remplace `tutorial_step`, qui portait un **second** etat d'avancement a
     * cote de l'arc `intro` et pouvait le contredire (dette D7). Le seul etat
     * qui ne se deduit pas de l'arc est le refus : un joueur qui a dit
     * « passer » l'a dit une fois pour toutes, et rien dans l'arc ne l'exprime.
     */
    #[ORM\Column(name: 'onboarding_skipped_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $onboardingSkippedAt = null;

    /** @var int[] */
    #[ORM\Column(name: 'blocked_players', type: 'json', options: ['default' => '[]'])]
    private array $blockedPlayers = [];

    /** @var array<string, string>|null */
    #[ORM\Column(name: 'avatar_appearance', type: 'json', nullable: true)]
    private ?array $avatarAppearance = null;

    #[ORM\Column(name: 'avatar_hash', type: 'string', length: 64, nullable: true)]
    private ?string $avatarHash = null;

    #[ORM\Column(name: 'avatar_version', type: 'integer', options: ['default' => 1])]
    private int $avatarVersion = 1;

    #[ORM\Column(name: 'avatar_updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $avatarUpdatedAt = null;

    #[ORM\OneToMany(targetEntity: PlayerStatusEffect::class, mappedBy: 'player', cascade: ['remove'])]
    private Collection $statusEffects;

    #[ORM\OneToMany(targetEntity: PlayerBestiary::class, mappedBy: 'player', cascade: ['remove'])]
    private Collection $bestiaryEntries;

    #[ORM\OneToMany(targetEntity: PlayerResourceCatalog::class, mappedBy: 'player', cascade: ['remove'])]
    private Collection $resourceCatalogEntries;

    #[ORM\OneToMany(targetEntity: PlayerAchievement::class, mappedBy: 'player')]
    private Collection $achievements;

    /** @return Collection<int, PlayerAchievement> */
    public function getAchievements(): Collection
    {
        return $this->achievements;
    }

    /** @return Collection<int, PlayerStatusEffect> */
    public function getStatusEffects(): Collection
    {
        return $this->statusEffects;
    }

    public function addStatusEffect(PlayerStatusEffect $statusEffect): void
    {
        if (!$this->statusEffects->contains($statusEffect)) {
            $this->statusEffects->add($statusEffect);
            $statusEffect->setPlayer($this);
        }
    }

    public function removeStatusEffect(PlayerStatusEffect $statusEffect): void
    {
        $this->statusEffects->removeElement($statusEffect);
    }

    /** @return Collection<int, PlayerBestiary> */
    public function getBestiaryEntries(): Collection
    {
        return $this->bestiaryEntries;
    }

    /** @return Collection<int, PlayerResourceCatalog> */
    public function getResourceCatalogEntries(): Collection
    {
        return $this->resourceCatalogEntries;
    }

    public function getSpeed(): int
    {
        return $this->speed;
    }

    public function setSpeed(int $speed): void
    {
        $this->speed = $speed;
    }

    /**
     * Ce que ce joueur a deja verse au Repertoire aujourd'hui (REP-01).
     *
     * Meme forme que le plafond des gestes de faction : **une cle de jour et un
     * compteur**, jamais une table d'historique — *une cle differente = un
     * autre jour = compteur a zero*. Rien a purger, rien qui grossisse.
     *
     * Le plafond vit ici et non sur le Repertoire, et c'est une consequence
     * directe de la forme de celui-ci : `RepertoireReading` ne nomme aucun
     * joueur, donc il ne peut pas en compter un.
     */
    #[ORM\Column(name: 'daily_repertoire_key', type: 'string', length: 16, nullable: true)]
    private ?string $dailyRepertoireKey = null;

    #[ORM\Column(name: 'daily_repertoire_readings', type: 'integer', options: ['default' => 0])]
    private int $dailyRepertoireReadings = 0;

    public function repertoireReadingsOn(string $dayKey): int
    {
        return $this->dailyRepertoireKey === $dayKey ? $this->dailyRepertoireReadings : 0;
    }

    public function recordRepertoireReading(string $dayKey): self
    {
        if ($this->dailyRepertoireKey !== $dayKey) {
            $this->dailyRepertoireKey = $dayKey;
            $this->dailyRepertoireReadings = 0;
        }
        ++$this->dailyRepertoireReadings;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->normalizedName = (new PlayerNameNormalizer())->normalize($name);

        return $this;
    }

    public function getNormalizedName(): ?string
    {
        return $this->normalizedName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMap(): ?Map
    {
        return $this->map;
    }

    public function setMap(?Map $map): void
    {
        $this->map = $map;
    }

    public function hasSeenCoachMark(CoachMark $mark): bool
    {
        return \in_array($mark->value, $this->seenCoachMarks, true);
    }

    /**
     * Retient qu'un encart a ete lu. Rend `false` s'il l'etait deja.
     *
     * Le rendu permet a l'appelant de ne pas ecrire pour rien : une fermeture
     * repetee — double-clic, rechargement — ne doit produire ni doublon ni
     * ecriture inutile.
     */
    public function markCoachSeen(CoachMark $mark): bool
    {
        if ($this->hasSeenCoachMark($mark)) {
            return false;
        }

        $this->seenCoachMarks[] = $mark->value;

        return true;
    }

    public function getHomeZone(): ?Zone
    {
        return $this->homeZone;
    }

    public function hasClaimedHomeZone(): bool
    {
        return null !== $this->homeZoneClaimedAt;
    }

    public function getHomeZoneClaimedAt(): ?\DateTimeImmutable
    {
        return $this->homeZoneClaimedAt;
    }

    /**
     * Constate le foyer. Sans effet si le joueur en a deja un.
     *
     * Le refus est porte par l'entite, pas par l'appelant : c'est la seule
     * facon qu'un second chemin de cloture — un rejeu de l'arc, un correctif
     * de donnees — ne puisse pas redonner ce qui a deja ete donne.
     */
    public function claimHomeZone(Zone $zone): bool
    {
        if (null !== $this->homeZoneClaimedAt) {
            return false;
        }

        $this->homeZone = $zone;
        $this->homeZoneClaimedAt = new \DateTimeImmutable();

        return true;
    }

    public function getHubWeekKey(): ?string
    {
        return $this->hubWeekKey;
    }

    public function setHubWeekKey(?string $hubWeekKey): void
    {
        $this->hubWeekKey = $hubWeekKey;
    }

    public function getCurrentZone(): ?Zone
    {
        return $this->currentZone;
    }

    public function setCurrentZone(?Zone $currentZone): void
    {
        $this->currentZone = $currentZone;
    }

    public function getTravelToZone(): ?Zone
    {
        return $this->travelToZone;
    }

    public function setTravelToZone(?Zone $travelToZone): void
    {
        $this->travelToZone = $travelToZone;
    }

    public function getTravelArrivesAt(): ?\DateTimeImmutable
    {
        return $this->travelArrivesAt;
    }

    public function setTravelArrivesAt(?\DateTimeImmutable $travelArrivesAt): void
    {
        $this->travelArrivesAt = $travelArrivesAt;
    }

    public function getTravelStartedAt(): ?\DateTimeImmutable
    {
        return $this->travelStartedAt;
    }

    public function setTravelStartedAt(?\DateTimeImmutable $travelStartedAt): void
    {
        $this->travelStartedAt = $travelStartedAt;
    }

    public function isTraveling(): bool
    {
        return null !== $this->travelToZone;
    }

    public function getActionEnergy(): int
    {
        return $this->actionEnergy;
    }

    public function setActionEnergy(int $actionEnergy): void
    {
        $this->actionEnergy = max(0, $actionEnergy);
    }

    public function getMaxActionEnergy(): int
    {
        return $this->maxActionEnergy;
    }

    public function setMaxActionEnergy(int $maxActionEnergy): void
    {
        $this->maxActionEnergy = max(1, $maxActionEnergy);
    }

    public function getActionEnergyUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->actionEnergyUpdatedAt;
    }

    public function setActionEnergyUpdatedAt(?\DateTimeImmutable $actionEnergyUpdatedAt): void
    {
        $this->actionEnergyUpdatedAt = $actionEnergyUpdatedAt;
    }

    public function getLastActivityAt(): ?\DateTimeImmutable
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeImmutable $lastActivityAt): self
    {
        $this->lastActivityAt = $lastActivityAt;

        return $this;
    }

    public function getActionEnergySpentTotal(): int
    {
        return (int) $this->actionEnergySpentTotal;
    }

    public function setActionEnergySpentTotal(int $total): self
    {
        $this->actionEnergySpentTotal = (string) max(0, $total);

        return $this;
    }

    public function addActionEnergySpent(int $amount): self
    {
        if ($amount > 0) {
            $this->actionEnergySpentTotal = (string) ($this->getActionEnergySpentTotal() + $amount);
        }

        return $this;
    }

    public function getLifeUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->lifeUpdatedAt;
    }

    public function setLifeUpdatedAt(?\DateTimeImmutable $lifeUpdatedAt): void
    {
        $this->lifeUpdatedAt = $lifeUpdatedAt;
    }

    /**
     * Le geste prepare qui s'appliquera a la prochaine rencontre (ARC-18g).
     *
     * La forme **ouverture** : *le combat commence avant le combat*. `0` quand
     * rien n'attend.
     *
     * **Elle vit sur le joueur et non dans un combat**, et c'est la definition
     * meme de la forme : elle est posee **hors** rencontre, depuis l'ecran de
     * zone, et attend la suivante. Une ouverture rangee dans un combat serait
     * une contradiction dans les termes.
     */
    #[ORM\Column(name: 'pending_opening', type: 'integer', options: ['default' => 0])]
    private int $pendingOpening = 0;

    public function getPendingOpening(): int
    {
        return $this->pendingOpening;
    }

    /**
     * Preparer un geste pour la prochaine rencontre.
     *
     * Une seconde ouverture **remplace** la premiere plutot que de s'y
     * ajouter : sans cela, la journee optimale consisterait a en poser dix
     * avant d'engager, et l'ouverture cesserait d'etre une preparation pour
     * devenir un **stock**.
     */
    public function prepareOpening(int $value): void
    {
        $this->pendingOpening = max(0, $value);
    }

    /**
     * Consommer l'ouverture en attente, et rendre ce qu'elle valait.
     *
     * Lire et consommer **en un seul geste**, comme la file des differes : les
     * separer laisserait une ouverture servir a chaque rencontre, c'est-a-dire
     * un bonus permanent achete une fois.
     */
    public function consumeOpening(): int
    {
        $value = $this->pendingOpening;
        $this->pendingOpening = 0;

        return $value;
    }

    public function getEnergyUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->energyUpdatedAt;
    }

    public function setEnergyUpdatedAt(?\DateTimeImmutable $energyUpdatedAt): void
    {
        $this->energyUpdatedAt = $energyUpdatedAt;
    }

    public function getLastCoordinates(): string
    {
        return $this->lastCoordinates;
    }

    public function setLastCoordinates(string $lastCoordinates): void
    {
        $this->lastCoordinates = $lastCoordinates;
    }

    public function setEnergy(int $energy): self
    {
        $this->energy = $energy;

        return $this;
    }

    public function getEnergy(): int
    {
        return $this->energy;
    }

    public function setUser(?User $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTradeSuspendedUntil(): ?\DateTimeImmutable
    {
        return $this->tradeSuspendedUntil;
    }

    public function setTradeSuspendedUntil(?\DateTimeImmutable $until): self
    {
        $this->tradeSuspendedUntil = $until;

        return $this;
    }

    /**
     * La suspension expire d'elle-meme : une sanction qu'il faut penser a lever
     * finit par ne jamais l'etre.
     */
    public function isTradeSuspended(?\DateTimeImmutable $now = null): bool
    {
        return null !== $this->tradeSuspendedUntil
            && $this->tradeSuspendedUntil > ($now ?? new \DateTimeImmutable());
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setMaxLife(int $maxLife): self
    {
        $this->maxLife = $maxLife;

        return $this;
    }

    public function getMaxLife(): int
    {
        return $this->maxLife;
    }

    public function setMaxEnergy(int $maxEnergy): self
    {
        $this->maxEnergy = $maxEnergy;

        return $this;
    }

    public function getMaxEnergy(): int
    {
        return $this->maxEnergy;
    }

    public function addInventory(Inventory $inventory): self
    {
        $this->inventories[] = $inventory;

        return $this;
    }

    public function removeInventory(Inventory $inventory): void
    {
        $this->inventories->removeElement($inventory);
    }

    /**
     * Get inventories.
     *
     * @return Collection|Inventory[]
     */
    public function getInventories()
    {
        return $this->inventories;
    }

    /**
     * Add domainExperience.
     *
     * @return Player
     */
    public function addDomainExperience(DomainExperience $domainExperience)
    {
        $this->domainExperiences[] = $domainExperience;

        return $this;
    }

    /**
     * Remove domainExperience.
     */
    public function removeDomainExperience(DomainExperience $domainExperience)
    {
        $this->domainExperiences->removeElement($domainExperience);
    }

    /**
     * Get domainExperiences.
     *
     * @return Collection|DomainExperience[]
     */
    public function getDomainExperiences()
    {
        return $this->domainExperiences;
    }

    /**
     * Le joueur possede-t-il cette competence ?
     *
     * La comparaison porte sur l'identifiant, pas sur l'identite d'objet : les
     * competences arrivent ici par deux chemins d'hydratation differents (la
     * collection du joueur, et celle du domaine chargee en JOIN FETCH), et un
     * `===` n'a de sens que tant que les deux passent par la meme carte
     * d'identite. Une competence non encore persistee retombe sur l'identite.
     */
    public function hasSkill(Skill $skill): bool
    {
        $skillId = self::skillId($skill);

        foreach ($this->getSkills() as $playerSkill) {
            if ($playerSkill === $skill) {
                return true;
            }
            if (null !== $skillId && self::skillId($playerSkill) === $skillId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Identifiant d'une competence, ou `null` si elle n'est pas persistee.
     *
     * `Skill::getId()` est declare `int` sur une propriete sans valeur par
     * defaut : une competence transiente — cas courant en test — fait lever
     * l'accesseur plutot que de rendre `null`.
     */
    private static function skillId(Skill $skill): ?int
    {
        try {
            return $skill->getId();
        } catch (\Error) {
            return null;
        }
    }

    /** @return Collection<int, Skill> */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function setSkills(Collection $skills): void
    {
        $this->skills = $skills;
    }

    public function addSkill(Skill $skill): void
    {
        $this->skills->add($skill);
    }

    public function removeSkill(Skill $skill): void
    {
        $this->skills->removeElement($skill);
    }

    public function getFight(): ?Fight
    {
        return $this->fight;
    }

    public function setFight(?Fight $fight): void
    {
        $this->fight = $fight;
    }

    public function getHit(): int
    {
        return $this->hit;
    }

    public function setHit(int $hit): void
    {
        $this->hit = $hit;
    }

    public function getClassType(): string
    {
        return $this->classType;
    }

    public function setClassType(string $classType): void
    {
        $this->classType = $classType;
    }

    /** @return Collection<int, PlayerQuest> */
    public function getQuests(): Collection
    {
        return $this->quests;
    }

    public function setQuests(Collection $quests): void
    {
        $this->quests = $quests;
    }

    /** @return Collection<int, PlayerQuestCompleted> */
    public function getCompletedQuests(): Collection
    {
        return $this->completedQuests;
    }

    public function isMoving(): bool
    {
        return $this->isMoving;
    }

    public function setIsMoving(bool $isMoving): void
    {
        $this->isMoving = $isMoving;
    }

    public function getDiscoveredRecipes(): array
    {
        return $this->discoveredRecipes ?? [];
    }

    public function setDiscoveredRecipes(array $discoveredRecipes): void
    {
        $this->discoveredRecipes = $discoveredRecipes;
    }

    public function getGils(): int
    {
        return $this->gils;
    }

    public function setGils(int $gils): void
    {
        $this->gils = max(0, $gils);
    }

    public function addGils(int $amount): void
    {
        $this->gils += $amount;
    }

    public function removeGils(int $amount): bool
    {
        if ($this->gils < $amount) {
            return false;
        }
        $this->gils -= $amount;

        return true;
    }

    public function getEssence(): int
    {
        return $this->essence;
    }

    public function addEssence(int $amount): void
    {
        $this->essence += max(0, $amount);
    }

    public function removeEssence(int $amount): bool
    {
        if ($this->essence < $amount) {
            return false;
        }
        $this->essence -= $amount;

        return true;
    }

    public function getNightExplorations(): int
    {
        return $this->nightExplorations;
    }

    public function incrementNightExplorations(): void
    {
        ++$this->nightExplorations;
    }

    public function getRespecCount(): int
    {
        return $this->respecCount;
    }

    public function setRespecCount(int $respecCount): void
    {
        $this->respecCount = $respecCount;
    }

    public function incrementRespecCount(): void
    {
        ++$this->respecCount;
    }

    public function getPrestigeTitle(): ?string
    {
        return $this->prestigeTitle;
    }

    public function setPrestigeTitle(?string $prestigeTitle): void
    {
        $this->prestigeTitle = $prestigeTitle;
    }

    public function isGameMaster(): bool
    {
        return $this->gameMaster;
    }

    public function setGameMaster(bool $gameMaster): static
    {
        $this->gameMaster = $gameMaster;

        return $this;
    }

    public function isGameMasterIncognito(): bool
    {
        return $this->gameMasterIncognito;
    }

    public function setGameMasterIncognito(bool $incognito): static
    {
        $this->gameMasterIncognito = $incognito;

        return $this;
    }

    /**
     * Le personnage doit-il etre retire des listes ou le rencontrent d'autres
     * joueurs ? Un joueur ordinaire ne l'est jamais : l'incognito n'a de sens
     * que porte par un MJ.
     */
    public function isHiddenFromOtherPlayers(): bool
    {
        return $this->gameMaster && $this->gameMasterIncognito;
    }

    public function getRenownScore(): int
    {
        return $this->renownScore;
    }

    public function setRenownScore(int $renownScore): void
    {
        $this->renownScore = max(0, $renownScore);
    }

    public function addRenownScore(int $amount): void
    {
        $this->renownScore = max(0, $this->renownScore + $amount);
    }

    public function getCraftSpecialization(): ?CraftSpecialization
    {
        return $this->craftSpecialization;
    }

    public function setCraftSpecialization(?CraftSpecialization $craftSpecialization): void
    {
        $this->craftSpecialization = $craftSpecialization;
    }

    public function hasCraftSpecialization(): bool
    {
        return !$this->craftSpecializations->isEmpty();
    }

    /**
     * Le joueur a-t-il pris une branche dans cet arbre ?
     *
     * La signature n'a pas bouge : les appelants qui gataient une recette sur
     * « etre specialise en forgeron » continuent de fonctionner, et gagnent au
     * passage le droit de l'etre aussi ailleurs.
     */
    public function isSpecializedIn(string $craft): bool
    {
        return $this->getCraftSpecializationFor($craft) !== null;
    }

    public function getCraftSpecializationFor(string $craft): ?PlayerCraftSpecialization
    {
        foreach ($this->craftSpecializations as $specialization) {
            if ($specialization->getCraft()->value === $craft) {
                return $specialization;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, PlayerCraftSpecialization>
     */
    public function getCraftSpecializations(): Collection
    {
        return $this->craftSpecializations;
    }

    /**
     * La branche que ce joueur a choisie dans cet arbre (ARC-14b).
     *
     * `null` veut dire « pas encore choisie », et c'est un etat normal : la
     * fourche ne se pose qu'au palier 3, donc un joueur passe l'essentiel de
     * son arbre sans avoir a trancher.
     */
    public function getCombatBranchFor(Domain $domain): ?PlayerCombatBranch
    {
        foreach ($this->combatBranches as $choice) {
            if ($choice->getDomain() === $domain) {
                return $choice;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, PlayerCombatBranch>
     */
    public function getCombatBranches(): Collection
    {
        return $this->combatBranches;
    }

    public function addCombatBranch(PlayerCombatBranch $choice): void
    {
        if (!$this->combatBranches->contains($choice)) {
            $this->combatBranches->add($choice);
        }
    }

    public function addCraftSpecialization(PlayerCraftSpecialization $specialization): void
    {
        if (!$this->craftSpecializations->contains($specialization)) {
            $this->craftSpecializations->add($specialization);
        }
    }

    /**
     * Le premier voyage offert est-il encore a prendre ? (ONB-10).
     */
    public function hasFirstTravelOffer(): bool
    {
        return $this->firstTravelSpentAt === null;
    }

    public function getFirstTravelSpentAt(): ?\DateTimeImmutable
    {
        return $this->firstTravelSpentAt;
    }

    public function spendFirstTravel(): void
    {
        $this->firstTravelSpentAt ??= new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, PlayerDomainAccess>
     */
    public function getDomainAccesses(): Collection
    {
        return $this->domainAccesses;
    }

    /**
     * Cet arbre est-il ouvert pour ce personnage ? (ONB-08).
     *
     * La comparaison porte sur l'identifiant, pas sur l'identite d'objet : deux
     * instances Doctrine du meme domaine se croisent des qu'un test ou un
     * chemin d'import recharge l'entite, et une comparaison par reference
     * refuserait alors un arbre pourtant ouvert.
     */
    public function hasOpenedDomain(Domain $domain): bool
    {
        foreach ($this->domainAccesses as $access) {
            if ($access->getDomain()->getId() === $domain->getId()) {
                return true;
            }
        }

        return false;
    }

    public function addDomainAccess(PlayerDomainAccess $access): void
    {
        if (!$this->domainAccesses->contains($access)) {
            $this->domainAccesses->add($access);
        }
    }

    public function getPatronFaction(): ?Faction
    {
        return $this->patronFaction;
    }

    /**
     * Porter d'autres couleurs, ou n'en porter aucune.
     *
     * Le setter ne verifie ni le palier ni le combat : c'est `PatronageService`
     * qui arbitre, parce que le refus doit avoir un motif et que le motif se
     * dit au joueur. Un entite qui refuse en silence produirait un formulaire
     * qui ne fait rien.
     */
    public function setPatronFaction(?Faction $faction): self
    {
        $this->patronFaction = $faction;

        return $this;
    }

    public function getRace(): ?Race
    {
        return $this->race;
    }

    public function setRace(?Race $race): self
    {
        $this->race = $race;

        return $this;
    }

    public function getActiveMount(): ?Mount
    {
        return $this->activeMount;
    }

    public function setActiveMount(?Mount $activeMount): self
    {
        $this->activeMount = $activeMount;

        return $this;
    }

    public function getUnlockedToolSlots(): array
    {
        return $this->unlockedToolSlots;
    }

    public function setUnlockedToolSlots(array $unlockedToolSlots): void
    {
        $this->unlockedToolSlots = $unlockedToolSlots;
    }

    public function hasToolSlot(string $toolType): bool
    {
        return \in_array($toolType, $this->unlockedToolSlots, true);
    }

    public function unlockToolSlot(string $toolType): void
    {
        if (!$this->hasToolSlot($toolType)) {
            $this->unlockedToolSlots[] = $toolType;
        }
    }

    public function hasSkippedOnboarding(): bool
    {
        return null !== $this->onboardingSkippedAt;
    }

    public function getOnboardingSkippedAt(): ?\DateTimeImmutable
    {
        return $this->onboardingSkippedAt;
    }

    public function skipOnboarding(): void
    {
        $this->onboardingSkippedAt ??= new \DateTimeImmutable();
    }

    /** @return int[] */
    public function getBlockedPlayers(): array
    {
        return $this->blockedPlayers;
    }

    public function isPlayerBlocked(int $playerId): bool
    {
        return \in_array($playerId, $this->blockedPlayers, true);
    }

    public function blockPlayer(int $playerId): void
    {
        if (!$this->isPlayerBlocked($playerId)) {
            $this->blockedPlayers[] = $playerId;
        }
    }

    public function unblockPlayer(int $playerId): void
    {
        $this->blockedPlayers = array_values(array_filter(
            $this->blockedPlayers,
            fn (int $id) => $id !== $playerId,
        ));
    }

    /** @return array<string, string>|null */
    public function getAvatarAppearance(): ?array
    {
        return $this->avatarAppearance;
    }

    /** @param array<string, string>|null $avatarAppearance */
    public function setAvatarAppearance(?array $avatarAppearance): void
    {
        $this->avatarAppearance = $avatarAppearance;
        $this->avatarUpdatedAt = new \DateTimeImmutable();
    }

    public function getAvatarHash(): ?string
    {
        return $this->avatarHash;
    }

    public function setAvatarHash(?string $avatarHash): void
    {
        if ($this->avatarHash === $avatarHash) {
            return;
        }

        $this->avatarHash = $avatarHash;
        $this->avatarUpdatedAt = new \DateTimeImmutable();
    }

    public function getAvatarVersion(): int
    {
        return $this->avatarVersion;
    }

    public function setAvatarVersion(int $avatarVersion): void
    {
        $this->avatarVersion = $avatarVersion;
    }

    public function getAvatarUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->avatarUpdatedAt;
    }

    public function hasAvatar(): bool
    {
        return $this->avatarAppearance !== null;
    }
}
