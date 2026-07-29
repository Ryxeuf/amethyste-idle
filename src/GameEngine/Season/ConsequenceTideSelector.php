<?php

namespace App\GameEngine\Season;

use App\Entity\App\Parameter;
use App\Enum\ConsequenceTide;
use App\Enum\SettlementRank;
use App\GameEngine\Settlement\CrueQuotaService;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\Repository\ZoneVeinRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Quelle maree le monde a **meritee** (FOY-15).
 *
 * La saison cesse d'etre un calendrier : le theme de la maree qui vient est
 * choisi a la cloture de la precedente, a partir d'un agregat du mois ecoule.
 * Les cinq marees canon de l'an 1 (GAME_SEASONS § 2) gardent leur place ; une
 * consequence **preempte** le prochain creneau de rotation, elle ne bouscule
 * jamais une maree ecrite.
 *
 * **Deux conditions, et une seule facon de les mesurer : le changement.**
 *
 * - *La Paleur* se lit sur un **etat** : assez de filons portent une trace
 *   visible pour que la sur-extraction soit avérée. C'est legitime parce que la
 *   Paleur est elle-meme reversible — un serveur qui a repare ne la revoit pas.
 * - *L'Appel de la Crue* se lit sur une **variation** : une place s'est
 *   liberee. L'etat ne dirait rien, puisqu'au lancement toutes les places sont
 *   libres et que l'Appel sonnerait a chaque maree. Ce qui compte est qu'une
 *   place *de plus* existe aujourd'hui — que la population ait franchi un
 *   palier (§13.4) ou qu'une regression ait rendu une place. Une seule mesure
 *   couvre les deux causes, et c'est voulu : du point de vue des joueurs, la
 *   nouvelle est la meme.
 *
 * Le repere du dernier releve vit dans `Parameter` plutot que dans une table :
 * c'est un unique vecteur de trois entiers, et lui donner un schema aurait cree
 * une migration pour trois nombres.
 */
class ConsequenceTideSelector
{
    public const PARAM_FREE_SLOTS = 'settlement.crue.free_slots';

    /**
     * Rangs contingentes par la Crue, du plus petit au plus grand.
     */
    private const CONTINGENTED = [
        SettlementRank::Town,
        SettlementRank::City,
        SettlementRank::Metropolis,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ZoneVeinRepository $veinRepository,
        private readonly CrueQuotaService $crueQuotaService,
        private readonly SettlementDefinitionLoader $settlementLoader,
        private readonly ConsequenceTideDefinitionLoader $loader,
    ) {
    }

    /**
     * La maree que le monde a meritee, ou `null` si rien ne s'est passe.
     *
     * `null` est le resultat **normal** : la plupart des marees ne sont pas des
     * consequences, et forcer un theme a chaque cloture reviendrait a rendre le
     * declenchement insignifiant.
     */
    public function select(): ?ConsequenceTide
    {
        $candidates = [];

        if ($this->paleVeins() >= $this->loader->load()['paleness_threshold']) {
            $candidates[] = ConsequenceTide::Paleness;
        }

        if ($this->aSlotHasOpened()) {
            $candidates[] = ConsequenceTide::CrueCall;
        }

        if ([] === $candidates) {
            return null;
        }

        // La consequence *negative* passe devant : c'est elle qui enseigne, et
        // la faire ceder a une bonne nouvelle reviendrait a dire au serveur que
        // sa sur-extraction est sans suite (GAME_SEASONS § 3).
        usort($candidates, static fn (ConsequenceTide $a, ConsequenceTide $b) => $a->precedence() <=> $b->precedence());

        return $candidates[0];
    }

    /**
     * Fige le nombre de places libres, pour que la prochaine cloture puisse
     * mesurer une variation.
     *
     * Appele **apres** `select()` par le tick : un releve pris avant ferait
     * disparaitre l'ouverture qu'on cherche justement a detecter.
     */
    public function rememberFreeSlots(): void
    {
        $parameter = $this->parameter();
        $parameter->setValue((string) json_encode($this->freeSlots()));

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();
    }

    /**
     * Filons portant une trace **visible**.
     *
     * Le seuil de visibilite est celui de FOY-11 : compter les traces
     * imperceptibles ferait sonner la Paleur pour un monde que personne ne voit
     * palir.
     */
    private function paleVeins(): int
    {
        $visibleFrom = $this->settlementLoader->load()['paleness']['visible_from'];

        $count = 0;
        foreach ($this->veinRepository->findAll() as $vein) {
            if ($vein->getPaleness() >= $visibleFrom) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Une place de grande ville s'est-elle liberee depuis le dernier releve ?
     *
     * Sans repere anterieur, la reponse est **non** : la premiere cloture pose
     * le repere sans rien declencher. Un monde neuf a toutes ses places libres,
     * et les annoncer comme une nouvelle serait un contresens.
     */
    private function aSlotHasOpened(): bool
    {
        $previous = $this->previousFreeSlots();
        if (null === $previous) {
            return false;
        }

        foreach ($this->freeSlots() as $rank => $free) {
            if ($free > ($previous[$rank] ?? $free)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Places libres par rang contingente.
     *
     * Un rang non contingente n'apparait pas : la Crue borne les grandes villes,
     * pas le droit d'exister.
     *
     * @return array<string, int>
     */
    private function freeSlots(): array
    {
        $slots = [];

        foreach (self::CONTINGENTED as $rank) {
            $quota = $this->crueQuotaService->quotaFor($rank);
            if (null === $quota) {
                continue;
            }

            $slots[$rank->value] = max(0, $quota - \count($this->crueQuotaService->occupants($rank)));
        }

        return $slots;
    }

    /**
     * @return array<string, int>|null
     */
    private function previousFreeSlots(): ?array
    {
        $value = $this->parameter()->getValue();
        if ('' === $value) {
            return null;
        }

        $decoded = json_decode($value, true);
        if (!\is_array($decoded)) {
            return null;
        }

        $previous = [];
        foreach ($decoded as $rank => $free) {
            if (\is_string($rank) && is_numeric($free)) {
                $previous[$rank] = (int) $free;
            }
        }

        return $previous;
    }

    private function parameter(): Parameter
    {
        $parameter = $this->entityManager->getRepository(Parameter::class)->findOneBy(['name' => self::PARAM_FREE_SLOTS]);

        return $parameter ?? (new Parameter())->setName(self::PARAM_FREE_SLOTS)->setValue('');
    }
}
