<?php

namespace App\GameEngine\Materia;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\CodexEntry;
use App\Entity\Game\Domain;
use App\Event\Game\MateriaReadEvent;
use App\GameEngine\Codex\CodexUnlockService;
use App\GameEngine\Progression\ActOneMateriaGranter;
use App\GameEngine\Reputation\HostileConsequenceResolver;
use App\GameEngine\Reputation\ReputationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Fondre ou lire (FAC-04b).
 *
 * GAME_WORLD § 12.2 : une materia trouvee peut aller a deux endroits, et un
 * seul. **Fondre** rend des gils et de l'essence — utile aujourd'hui, et le
 * geste disparait definitivement du monde. **Lire** inscrit — une entree de
 * Codex, de la reputation au Cercle, un progres d'accord dans l'arbre qui
 * enseigne cette materia — et ne sera jamais repris. C'est tout le propos du
 * monde ramene a un bouton : le joueur presse fond, le joueur qui pense au
 * serveur lit, personne n'a tort, et il faut choisir a chaque materia en
 * double.
 *
 * **L'accord ne se derive pas de l'element** — meme doctrine
 * qu'`ActOneMateriaGranter` : le berserker est feu, mais `m1-fire` est
 * enseignee par l'arbre du pyromancien. Le progres va a l'arbre dont un nœud
 * `materia.unlock` ouvre le sort de la materia lue.
 */
class MateriaConversionService
{
    /**
     * La part en gils de la fonte : le taux commun de rachat PNJ. Ce que la
     * fonte ajoute, c'est l'essence et la reputation — pas un meilleur prix,
     * sinon elle deviendrait le canal de vente dominant.
     */
    public const MELT_GILS_RATE = 0.3;

    /**
     * L'essence rendue suit le palier de la materia (m1 → 1 ... m5 → 5) : le
     * haut du catalogue a depose plus de temps, il en rend plus.
     */
    public const ESSENCE_PER_LEVEL = 1;

    /**
     * Le progres d'accord d'une lecture, en points de domaine. Modeste par
     * palier : la lecture est un geste quotidien, pas une source d'XP — c'est
     * la reputation et le Codex qui portent sa valeur.
     */
    public const READ_ACCORD_POINTS_PER_LEVEL = 2;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReputationManager $reputationManager,
        private readonly CodexUnlockService $codexUnlockService,
        private readonly HostileConsequenceResolver $hostileConsequences,
        private readonly ActOneMateriaGranter $accordGranter,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Fondre : la materia disparait, le joueur recoit gils + essence, et la
     * Fonderie est nourrie (route de geste FAC-02, plafonnee).
     *
     * @return array{gils: int, essence: int}
     *
     * @throws MateriaConversionException
     */
    public function melt(Player $player, PlayerItem $materia): array
    {
        $this->assertConvertible($materia);

        $item = $materia->getGenericItem();
        $gils = max(1, (int) (($item->getPrice() ?? 0) * self::MELT_GILS_RATE));
        $essence = max(1, ($item->getLevel() ?? 1) * self::ESSENCE_PER_LEVEL);

        $player->addGils($gils);
        $player->addEssence($essence);

        $this->entityManager->remove($materia);
        $this->entityManager->flush();

        $this->reputationManager->grantGestureReputation($player, 'materia_melt');

        return ['gils' => $gils, 'essence' => $essence];
    }

    /**
     * Lire : la materia disparait aussi — mais ce qu'elle portait est inscrit.
     * Une entree de Codex (la premiere lecture de chaque flux ouvre sa page),
     * la reputation du Cercle (route FAC-02), un progres d'accord dans l'arbre
     * qui enseigne cette materia, et le versement au Repertoire (crochet
     * declare, sans abonne tant que REP n'est pas jalonne).
     *
     * @return array{codexUnlocked: int, accordDomain: string|null, accordPoints: int}
     *
     * @throws MateriaConversionException
     */
    public function read(Player $player, PlayerItem $materia): array
    {
        $this->assertConvertible($materia);

        if ($this->hostileConsequences->isMateriaReadingRefused($player)) {
            // FAC-03, materia_reading_refused : le Cercle ne lit pas pour un
            // Hostile. Il reste la fonte, ou le stock — jamais la boucle cœur.
            throw new MateriaConversionException('game.materia.convert.error.reading_refused');
        }

        $item = $materia->getGenericItem();

        $codexUnlocked = $this->codexUnlockService->unlockByTrigger(
            $player,
            CodexEntry::UNLOCK_MATERIA_READ,
            $item->getElement()->value,
        );

        $accordPoints = 0;
        $domain = $this->teachingDomain($materia);
        if (null !== $domain) {
            $accordPoints = max(1, ($item->getLevel() ?? 1) * self::READ_ACCORD_POINTS_PER_LEVEL);
            $this->accordGranter->grantAccordPoints($player, $domain, $accordPoints);
        }

        // REP-01 : la provenance vit sur la **piece**, et la piece dispararait
        // a la ligne suivante. On la retient avant, sinon le Repertoire ne
        // saurait jamais d'ou venait ce qu'il a lu.
        $provenanceZoneId = $materia->getOriginZoneId();

        $this->entityManager->remove($materia);
        $this->entityManager->flush();

        $this->reputationManager->grantGestureReputation($player, 'materia_read');

        $this->eventDispatcher->dispatch(new MateriaReadEvent($player, $item, $provenanceZoneId), MateriaReadEvent::NAME);

        return [
            'codexUnlocked' => $codexUnlocked,
            'accordDomain' => $domain?->getTitle(),
            'accordPoints' => $accordPoints,
        ];
    }

    /**
     * Ce qui ne se convertit pas : autre chose qu'une materia, ou une materia
     * encore sertie — on ne fond pas ce qu'on porte.
     */
    private function assertConvertible(PlayerItem $materia): void
    {
        if (!$materia->isMateria()) {
            throw new MateriaConversionException('game.materia.convert.error.not_materia');
        }
        if (null !== $materia->getSlotSet()) {
            throw new MateriaConversionException('game.materia.convert.error.socketed');
        }
    }

    /**
     * L'arbre qui enseigne cette materia : celui dont un nœud `materia.unlock`
     * ouvre son sort — le moins cher si plusieurs arbres l'enseignent, comme
     * pour la materia de l'acte I.
     */
    private function teachingDomain(PlayerItem $materia): ?Domain
    {
        $spellSlug = $materia->getGenericItem()->getSpell()?->getSlug();
        if (null === $spellSlug) {
            return null;
        }

        $best = null;
        $bestCost = null;
        foreach ($this->entityManager->getRepository(Domain::class)->findAll() as $domain) {
            foreach ($domain->getSkills() as $skill) {
                $actions = $skill->getActions() ?? [];
                if (($actions['materia']['unlock'] ?? null) !== $spellSlug) {
                    continue;
                }
                if (null === $bestCost || $skill->getRequiredPoints() < $bestCost) {
                    $best = $domain;
                    $bestCost = $skill->getRequiredPoints();
                }
            }
        }

        return $best;
    }
}
