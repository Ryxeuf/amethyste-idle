<?php

namespace App\GameEngine\Repertoire;

use App\Entity\App\AwakeningRite;
use App\Entity\App\Guild;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\Enum\Purity;
use App\Enum\SettlementType;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Region\PlayerRegionResolver;
use App\GameEngine\Settlement\SettlementGate;
use App\Helper\InventoryHelper;
use App\Repository\AwakeningRiteRepository;
use App\Repository\RepertoireGestureRepository;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * L'Autel d'eveil (REP-04) — le seul craft de materia du jeu.
 *
 * GAME_WORLD § 12.3 a : **un service de la ville, comme le marche ou la
 * banque**. La guilde qui gouverne la Metropole *taxe* chaque rite ; elle ne
 * ferme jamais la porte (doctrine D14). Un joueur sans guilde eveille comme les
 * autres.
 *
 * ## Ce que l'Autel eveille, et ce qui reste en attente
 *
 * **Ce que le monde a retrouve, et rien d'autre.** Le § 12.3 annonce deux
 * moities — *« le catalogue de base par provenance, **plus** les gestes
 * retrouves »* —, et ce jalon livre la seconde. C'est celle qui fait payer
 * REP-03 : si la liste de base contenait deja tout, retrouver un geste
 * n'elargirait rien, et le debouche collectif de « lire » serait vide.
 *
 * **La premiere moitie est bloquee par une mesure, pas par le temps.** Un
 * catalogue « par provenance » suppose que les lots d'amethystite disent d'ou
 * ils viennent. Ils ne le disent pas : REP-01 n'a stampe la provenance que la
 * ou le monde la donne — le butin d'un monstre —, et l'amethystite se
 * **recolte**. La remplir depuis la zone de recolte ferait revenir exactement
 * l'erreur que REP-01 a refusee : *un axe rempli par la copie d'un autre*.
 * Cliquet nomme, en attente d'une provenance sur les lots recoltes.
 *
 * ## Les trois refus, et pourquoi ils sont dans cet ordre
 *
 * Le rang de foyer d'abord (c'est le lieu qui ouvre), l'accord ensuite (c'est
 * le personnage qui sait), la matiere en dernier (c'est ce qu'on peut aller
 * chercher). Un joueur doit apprendre dans cet ordre ce qui lui manque : on ne
 * lui dit pas « il vous manque trois lots » avant de lui dire qu'il n'a rien a
 * faire ici.
 */
class AwakeningAltar
{
    public const SERVICE = 'awakening_altar';

    public function __construct(
        private readonly RepertoireCatalog $catalog,
        private readonly RepertoireGestureRepository $gestures,
        private readonly AwakeningRiteRepository $rites,
        private readonly SettlementGate $gate,
        private readonly SettlementRepository $settlements,
        private readonly CombatSkillResolver $skills,
        private readonly PlayerRegionResolver $regionResolver,
        private readonly TownControlManager $townControl,
        private readonly InventoryHelper $inventory,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Les materia que ce joueur peut eveiller ici.
     *
     * Deux conditions se croisent : le **monde** a retrouve le geste, et le
     * **personnage** en possede l'accord. Ni l'une ni l'autre ne suffit — c'est
     * ce qui fait de l'eveil un aboutissement a la fois collectif et personnel.
     *
     * @return list<Item>
     */
    public function awakenableBy(Player $player): array
    {
        $unlocked = $this->skills->getUnlockedMateriaSpellSlugs($player);
        $recovered = $this->gestures->recoveredKeys();
        $pool = $this->catalog->foundGestures();

        $materias = [];
        foreach ($recovered as $key) {
            $gesture = $pool[$key] ?? null;
            if ($gesture === null) {
                // Un geste retrouve dont l'entree a disparu du bassin : on ne
                // le propose plus, mais on ne le retire pas non plus de la
                // table — le savoir n'est jamais borne, et une donnee retiree
                // du fichier ne doit pas effacer l'histoire du monde.
                continue;
            }

            $materia = $this->entityManager->getRepository(Item::class)->findOneBy(['slug' => $gesture['awakens']]);
            if ($materia === null) {
                continue;
            }

            $spellSlug = $materia->getSpell()?->getSlug();
            if ($spellSlug === null || !\in_array($spellSlug, $unlocked, true)) {
                continue;
            }

            $materias[] = $materia;
        }

        return $materias;
    }

    /**
     * Le cout du rite ici — gils et duree, remise du Sanctuaire comprise.
     *
     * @return array{gils: int, lots: int, seconds: int, sanctuary: bool}
     */
    public function costAt(Zone $zone): array
    {
        $altar = $this->catalog->altar();
        $sanctuary = $this->settlements->findOneByZone($zone)?->getType() === SettlementType::Sanctuary;

        $discount = $sanctuary ? $altar['sanctuary_discount_percent'] : 0;
        $keep = 100 - $discount;

        return [
            // Arithmetique entiere, arrondie **vers le bas** : la remise du
            // Sanctuaire profite au joueur jusqu'au dernier gil. L'inverse
            // ferait qu'une remise de 25 % sur un prix impair coute plus cher
            // qu'annonce, ce qui se lit comme un bug et jamais comme un arrondi.
            'gils' => intdiv($altar['gils'] * $keep, 100),
            'lots' => $altar['perfect_lots'],
            'seconds' => intdiv($altar['duration_hours'] * 3600 * $keep, 100),
            'sanctuary' => $sanctuary,
        ];
    }

    /**
     * Lance un rite.
     *
     * @throws AwakeningException
     */
    public function start(Player $player, Zone $zone, Item $materia, \DateTimeImmutable $now): AwakeningRite
    {
        if (!$this->gate->allows($zone, self::SERVICE)) {
            throw new AwakeningException('game.repertoire.altar.error.closed');
        }

        if ($this->rites->findPending($player) !== null) {
            throw new AwakeningException('game.repertoire.altar.error.already_running');
        }

        if (!\in_array($materia->getId(), array_map(static fn (Item $i): int => $i->getId(), $this->awakenableBy($player)), true)) {
            throw new AwakeningException('game.repertoire.altar.error.not_awakenable');
        }

        $cost = $this->costAt($zone);

        $lots = $this->perfectLots($player);
        if (\count($lots) < $cost['lots']) {
            throw new AwakeningException('game.repertoire.altar.error.not_enough_perfect');
        }

        if ($player->getGils() < $cost['gils']) {
            throw new AwakeningException('game.repertoire.altar.error.not_enough_gils');
        }

        $player->removeGils($cost['gils']);
        foreach (\array_slice($lots, 0, $cost['lots']) as $lot) {
            $this->entityManager->remove($lot);
        }

        $this->payTax($zone, $cost['gils']);

        $rite = new AwakeningRite($player, $zone, $materia, $cost['gils'], $now->modify(sprintf('+%d seconds', $cost['seconds'])));
        $this->entityManager->persist($rite);
        $this->entityManager->flush();

        return $rite;
    }

    /**
     * Reclame un rite acheve.
     *
     * @throws AwakeningException
     */
    public function claim(Player $player, \DateTimeImmutable $now): Item
    {
        $rite = $this->rites->findPending($player);

        if ($rite === null) {
            throw new AwakeningException('game.repertoire.altar.error.nothing_to_claim');
        }

        if (!$rite->isReady($now)) {
            throw new AwakeningException('game.repertoire.altar.error.not_ready');
        }

        $rite->claim($now);
        $this->inventory->addItemId($rite->getMateria()->getId(), false);
        $this->entityManager->flush();

        return $rite->getMateria();
    }

    /**
     * Les lots d'amethystite **Parfaite** que ce joueur porte.
     *
     * Seul le Parfait eveille (ECO-22, GAME_WORLD § 3.3). C'est la seule bande
     * dont la valeur ne soit pas qu'un prix, et c'est ici qu'elle la trouve.
     *
     * @return list<PlayerItem>
     */
    public function perfectLots(Player $player): array
    {
        $lots = [];

        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }
            foreach ($inventory->getItems() as $item) {
                if ($item->getGenericItem()->getSlug() === 'ore-amethyst-crystal'
                    && $item->getPurity() === Purity::Parfait
                    && $item->isExchangeable()) {
                    $lots[] = $item;
                }
            }
        }

        return $lots;
    }

    /**
     * La part de la guilde qui gouverne — ou la destruction des gils quand la
     * cite n'a pas de maitre.
     *
     * Meme regle qu'a l'hotel des ventes (ECO-04) et a l'echoppe : sans guilde
     * pour la recevoir, la taxe **sort du jeu**. La rendre au joueur en ferait
     * une remise deguisee sur les serveurs sans controle, c'est-a-dire l'inverse
     * d'un gold sink.
     */
    private function payTax(Zone $zone, int $gilsPaid): void
    {
        $tax = intdiv($gilsPaid * $this->catalog->altar()['tax_percent'], 100);
        if ($tax <= 0) {
            return;
        }

        $region = $this->regionResolver->resolveForZone($zone);
        $ruler = $region !== null ? $this->townControl->getControllingGuild($region) : null;

        if (!$ruler instanceof Guild) {
            $this->logger->info('Awakening tax burned (city has no ruling guild)', [
                'zone' => $zone->getSlug(),
                'amount' => $tax,
            ]);

            return;
        }

        $ruler->addGilsTreasury($tax);

        $this->logger->info('Awakening tax transferred to guild treasury', [
            'zone' => $zone->getSlug(),
            'guild' => $ruler->getName(),
            'amount' => $tax,
        ]);
    }
}
