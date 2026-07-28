<?php

namespace App\GameEngine\World;

use App\Entity\App\Parameter;
use App\GameEngine\Codex\WorldFactService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Le facteur de monde `W` (FOY-17b).
 *
 * Refixer des constantes obligerait a retoucher 34 filons, les quotas et les
 * seuils a chaque palier de croissance : une corvee qui ne serait jamais faite,
 * et qui casserait le design en silence. Le calibrage est donc **dynamique** —
 * mais mal concu, il annulerait exactement la tension qu'il sert.
 *
 * L'invariant a servir ([BALANCE.md § 22.4](../../../docs/BALANCE.md)) :
 *
 * > **Le temps qu'il faut pour faire monter un foyer, et la tension ressentie
 * > sur un filon, doivent etre les memes a 50 joueurs et a 500.**
 *
 * Ce n'est pas « le monde grossit », c'est « l'experience reste constante quand
 * la population change ». Trois regles en decoulent, et elles sont toutes
 * verrouillees par des tests :
 *
 * 1. **Aveugle au local.** `W` s'indexe sur la population **globale**, jamais
 *    sur la frequentation d'un filon. Un filon qui donnerait plus a mesure
 *    qu'on le presse annulerait sa propre rarete.
 * 2. **Par paliers, pas en continu.** Un reglage qui glisse en permanence rend
 *    impossible a constituer le savoir du prospecteur — qu'on a precisement
 *    rendu monnayable.
 * 3. **Asymetrique.** Monte vite (n'importe quel tick), redescend lentement
 *    (bascule de maree seulement). Une baisse passagere de frequentation ne
 *    doit jamais retrecir le monde sous les pieds des joueurs presents.
 */
class WorldScaleService
{
    public const string PARAM_SCALE = 'world.scale';
    public const string PARAM_LOCK = 'world.scale.lock';

    public const float DEFAULT_SCALE = 1.0;

    /**
     * @param list<array{population: int|float, scale: int|float}> $bands paliers, du plus petit au plus grand
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorldLoadService $worldLoadService,
        private readonly WorldFactService $worldFactService,
        private readonly LoggerInterface $logger,
        private readonly array $bands,
        private readonly float $floor,
        /** Nombre de marees mesurees avant qu'une contraction soit permise. */
        private readonly int $graceTides,
        private readonly int $tideDays,
    ) {
    }

    public function current(): float
    {
        $parameter = $this->parameter(self::PARAM_SCALE);
        if ($parameter === null) {
            return self::DEFAULT_SCALE;
        }

        $value = (float) $parameter->getValue();

        return $value > 0.0 ? $value : self::DEFAULT_SCALE;
    }

    /**
     * `W` fige par un administrateur — pour un evenement, pour un test, et pour
     * le jour ou la valeur automatique aura tort.
     */
    public function isLocked(): bool
    {
        return $this->parameter(self::PARAM_LOCK)?->getValue() === '1';
    }

    public function lock(bool $locked): void
    {
        $this->write(self::PARAM_LOCK, $locked ? '1' : '0');
        $this->entityManager->flush();
    }

    /**
     * Palier vise par la charge actuelle, sans tenir compte des regles de
     * cadence. C'est la lecture brute, utile a l'administration.
     */
    public function targetScale(): float
    {
        $population = $this->worldLoadService->effectivePopulation();

        $scale = $this->floor;
        foreach ($this->bands as $band) {
            if ($population >= (float) $band['population']) {
                $scale = (float) $band['scale'];
            }
        }

        return max($this->floor, $scale);
    }

    /**
     * Applique la cadence et retourne le nouveau `W` s'il a change, `null` sinon.
     *
     * @param bool $tideBoundary vrai au basculement d'une maree
     */
    public function evaluate(bool $tideBoundary = false): ?float
    {
        if ($this->isLocked()) {
            return null;
        }

        $current = $this->current();
        $target = $this->targetScale();

        if ($target === $current) {
            return null;
        }

        if ($target > $current) {
            // Expansion : possible a n'importe quel tick. Attendre 28 jours pour
            // ouvrir le monde serait trop lent pour un jeune serveur qui grandit.
            return $this->apply($target, $current, 'expansion');
        }

        // Contraction : seulement au basculement de maree...
        if (!$tideBoundary) {
            return null;
        }

        // ...et jamais tant que la fenetre de mesure est incomplete. Un serveur
        // qui demarre a cinq joueurs ne doit pas se refermer sur eux avant
        // meme d'avoir vu une maree entiere.
        if ($this->worldLoadService->measuredDays() < $this->graceTides * $this->tideDays) {
            $this->logger->info('World scale contraction held back: grace period ({days} day(s) measured).', [
                'days' => $this->worldLoadService->measuredDays(),
            ]);

            return null;
        }

        return $this->apply($target, $current, 'contraction');
    }

    private function apply(float $target, float $previous, string $direction): float
    {
        $this->write(self::PARAM_SCALE, (string) $target);
        $this->entityManager->flush();

        // Annonce, jamais silencieux : une necessite technique devient un
        // evenement du monde plutot qu'un ajustement subi.
        $expanding = $direction === 'expansion';
        $this->worldFactService->recordWorldFact(
            sprintf('world-scale-%s', str_replace('.', '-', (string) $target)),
            $expanding ? 'La Concorde s\'etend' : 'La Concorde se resserre',
            $expanding
                ? 'Les filons du monde portent davantage : les routes se peuplent, et ce que la terre rend suffit a plus de mains.'
                : 'Le monde se resserre sur ceux qui restent. Les filons rendent moins, mais au meme rythme.',
        );

        $this->logger->info('World scale {direction}: {previous} -> {target}', [
            'direction' => $direction,
            'previous' => $previous,
            'target' => $target,
        ]);

        return $target;
    }

    private function parameter(string $name): ?Parameter
    {
        return $this->entityManager->getRepository(Parameter::class)->findOneBy(['name' => $name]);
    }

    private function write(string $name, string $value): void
    {
        $parameter = $this->parameter($name);
        if ($parameter === null) {
            $parameter = new Parameter();
            $parameter->setName($name);
            $parameter->setCreatedAt(new \DateTime());
            $this->entityManager->persist($parameter);
        }

        $parameter->setValue($value);
        $parameter->setUpdatedAt(new \DateTime());
    }
}
