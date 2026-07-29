<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Guild;
use App\Entity\App\GuildMember;
use App\Entity\App\Player;
use App\Entity\App\VeinRestoration;
use App\Entity\App\Zone;
use App\Entity\App\ZoneVein;
use App\GameEngine\Codex\WorldFactService;
use App\Repository\VeinRestorationRepository;
use App\Repository\ZoneVeinRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Restaurer un filon pali est une **depense politique** (FOY-12).
 *
 * Mecanique de Wakfu : la Paleur cesse d'etre une perte seche des lors qu'une
 * guilde peut payer pour la reduire. Ce que le jalon ajoute a FOY-11 n'est pas
 * un bouton « reparer » mais une decision de tresorerie qui se voit — au meme
 * endroit que les autres actes de gouvernement, le journal de monde.
 *
 * **Trois interdits** portent le jalon :
 *
 * 1. *On n'achete pas un monde propre.* Le chantier ajoute un debit de
 *    recuperation pendant `duration_days` ; il ne remet jamais la Paleur a
 *    zero. A `paleness.max`, un chantier complet en retire un tiers.
 * 2. *Payer n'autorise pas a continuer.* Le bonus ne s'applique qu'a un filon
 *    dont la pression du jour est retombee sous son debit soutenu — un chantier
 *    ouvert sur un filon qu'on presse encore ne repare rien. C'est ce qui
 *    empeche la Paleur de devenir une simple facture pour guilde riche.
 * 3. *Restaurer se voit.* Le chantier laisse deux traces : la ligne
 *    `VeinRestoration` (la comptabilite) et un fait de monde public (la
 *    mention). Un acte de gouvernement invisible n'en est pas un.
 *
 * **Ecart assume au plan.** Le plan demandait la trace au `GuildVaultLog` et un
 * chantier reserve a la « guilde controlante ». Ni l'un ni l'autre n'etait
 * tenable :
 *
 * - `GuildVaultLog` exige un `Item` non nul — c'est un journal d'objets, pas de
 *   Gils. La ligne `VeinRestoration` porte donc elle-meme la guilde, le montant
 *   et la date, et la mention publique passe par le journal de monde.
 * - Le lien zone -> region controlee court par `Zone::getSourceMap()`, un
 *   heritage d'avant le pivot PBBG que les zones recentes (Dunes d'Ambre,
 *   Vallons d'Aubepine) n'ont pas. Reserver le chantier a la guilde controlante
 *   aurait rendu la majorite de la carte irreparable. L'autorite retenue est
 *   celle qui gouverne deja la depense : le rang qui peut retirer du tresor.
 */
class VeinRestorationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly VeinRestorationRepository $restorationRepository,
        private readonly ZoneVeinRepository $veinRepository,
        private readonly SettlementDefinitionLoader $loader,
        private readonly WorldFactService $worldFactService,
    ) {
    }

    /**
     * Le prix d'un chantier, indexe sur la Paleur accumulee.
     *
     * Statique et pure : le cout est ce que le joueur lit avant de decider, il
     * doit pouvoir se verifier sans base de donnees. Un point vaut 0,01 de
     * Paleur — un filon deux fois plus abime coute deux fois plus cher, sans
     * palier ni arrondi qui brouillerait la lecture.
     *
     * @param array{cost_per_point: int, duration_days: int, daily_bonus: float, opens_from: float} $definition
     */
    public static function costFor(float $paleness, array $definition): int
    {
        return (int) round(max(0.0, $paleness) * 100 * $definition['cost_per_point']);
    }

    /**
     * Ce que l'ecran de zone affiche pour les filons palis, par slug de filon.
     *
     * Un filon sous `opens_from` n'apparait pas : il n'y a rien a restaurer, et
     * proposer un chantier inutile ferait payer une trace que le temps efface
     * seul.
     *
     * @return array<string, VeinRestorationOffer>
     */
    public function offersFor(?Player $player, Zone $zone, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $definition = $this->loader->load()['restoration'];
        $active = $this->restorationRepository->activeInZone($zone, $now);

        $offers = [];

        foreach ($this->veinRepository->findBy(['zone' => $zone]) as $vein) {
            if ($vein->getPaleness() < $definition['opens_from']) {
                continue;
            }

            $underway = $active[$vein->getSlug()] ?? null;
            $cost = self::costFor($vein->getPaleness(), $definition);

            $offers[$vein->getSlug()] = new VeinRestorationOffer(
                $vein->getSlug(),
                $cost,
                (int) round($vein->getPaleness() * 100),
                $underway?->getEndsAt(),
                $underway?->getGuild()->getName(),
                null !== $underway ? null : $this->blockedReason($player, $cost),
            );
        }

        return $offers;
    }

    /**
     * Ouvre un chantier et debite le tresor.
     *
     * @throws VeinRestorationException si le joueur n'a pas l'autorite, si le filon n'a rien a restaurer, si un chantier y court deja, ou si le tresor ne suit pas
     */
    public function open(Player $player, Zone $zone, string $veinSlug, ?\DateTimeImmutable $now = null): VeinRestoration
    {
        $now ??= new \DateTimeImmutable();
        $definition = $this->loader->load()['restoration'];

        $guild = $this->authorizedGuild($player);

        $vein = $this->veinRepository->findOneByZoneAndSlug($zone, $veinSlug);
        if (null === $vein || $vein->getPaleness() < $definition['opens_from']) {
            throw new VeinRestorationException('game.zone.restoration.error.nothing_to_mend');
        }

        // Idempotence : un filon ne porte jamais deux chantiers. Sans cette
        // garde, deux clics — ou deux guildes — paieraient pour le meme effet,
        // et le second paiement n'acheterait rien.
        if (null !== $this->restorationRepository->findActive($zone, $veinSlug, $now)) {
            throw new VeinRestorationException('game.zone.restoration.error.already_underway');
        }

        $cost = self::costFor($vein->getPaleness(), $definition);
        if ($guild->getGilsTreasury() < $cost) {
            throw new VeinRestorationException('game.zone.restoration.error.treasury_too_low');
        }

        $guild->addGilsTreasury(-$cost);

        $restoration = new VeinRestoration(
            $zone,
            $veinSlug,
            $guild,
            $cost,
            $vein->getPaleness(),
            $now->modify(sprintf('+%d days', $definition['duration_days'])),
        );

        $this->entityManager->persist($restoration);
        $this->entityManager->flush();

        $this->announce($zone, $vein, $guild, $cost, $now);

        return $restoration;
    }

    /**
     * La guilde au nom de laquelle le joueur peut engager le tresor.
     *
     * @throws VeinRestorationException
     */
    private function authorizedGuild(Player $player): Guild
    {
        $membership = $this->entityManager->getRepository(GuildMember::class)->findOneBy(['player' => $player]);
        if (!$membership instanceof GuildMember) {
            throw new VeinRestorationException('game.zone.restoration.error.no_guild');
        }

        // L'autorite est celle qui gouverne deja la depense. Un chantier est un
        // retrait du tresor ; rien ne justifierait qu'il obeisse a une regle
        // plus permissive qu'un retrait ordinaire.
        if (!$membership->getRank()->canWithdraw()) {
            throw new VeinRestorationException('game.zone.restoration.error.rank_too_low');
        }

        return $membership->getGuild();
    }

    /**
     * Pourquoi le bouton manque, ou `null` s'il est la.
     */
    private function blockedReason(?Player $player, int $cost): ?string
    {
        if (null === $player) {
            return 'game.zone.restoration.error.no_guild';
        }

        try {
            $guild = $this->authorizedGuild($player);
        } catch (VeinRestorationException $exception) {
            return $exception->getMessage();
        }

        return $guild->getGilsTreasury() >= $cost ? null : 'game.zone.restoration.error.treasury_too_low';
    }

    /**
     * La mention publique.
     *
     * Le slug porte la date d'ouverture : deux chantiers successifs sur le meme
     * filon sont deux faits, pas un fait ecrase. L'idempotence par slug du
     * journal (NAR-07) protege alors le rejeu d'une meme ouverture sans effacer
     * l'histoire du lieu.
     */
    private function announce(Zone $zone, ZoneVein $vein, Guild $guild, int $cost, \DateTimeImmutable $now): void
    {
        $this->worldFactService->recordWorldFact(
            sprintf('restauration_%s_%s_%s', $zone->getSlug(), $vein->getSlug(), $now->format('Y-m-d')),
            sprintf('Chantier de restauration — %s', $zone->getName()),
            sprintf(
                'La guilde « %s » a verse %d Gils pour que le filon « %s » de %s se refasse plus vite. La trace ne s\'efface pas d\'un coup : le chantier accompagne la guérison, il ne l\'achète pas.',
                $guild->getName(),
                $cost,
                $vein->getSlug(),
                $zone->getName(),
            ),
            $guild->getName(),
        );
    }
}
