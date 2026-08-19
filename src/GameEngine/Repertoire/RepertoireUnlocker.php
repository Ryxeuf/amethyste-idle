<?php

namespace App\GameEngine\Repertoire;

use App\Entity\App\RepertoireGesture;
use App\GameEngine\Codex\WorldFactService;
use App\GameEngine\World\WorldLoadService;
use App\Repository\RepertoireGestureRepository;
use App\Repository\RepertoireReadingRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le franchissement d'un seuil, et le geste qu'il retrouve (REP-03).
 *
 * GAME_WORLD § 12.3 b : *« ce ne sont pas des seuils generiques qui debloquent
 * une liste fixe : **ce qu'un serveur retrouve depend de ce qu'il a lu** »*.
 *
 * **Le tirage n'en est pas un.** Le canon dit « tire du bassin », et il aurait
 * ete facile de mettre un jet la. C'est refuse : *un tirage au sort ferait du
 * souvenir une loterie*, quand toute la these du systeme est que ce qu'un monde
 * retrouve se lit depuis ce qu'il a vecu. Le choix est **deterministe** — meme
 * histoire de lectures, meme geste —, et c'est ce qui permet a un serveur de
 * faire campagne (« cette maree, lisez du feu ») en sachant ce qu'il obtient.
 * C'est de la politique, ce que le canon appelle explicitement legitime.
 *
 * **Le seuil est indexe sur la population effective** (BALANCE § 22.5, la
 * mecanique de la Crue) : on mesure la charge, pas les tetes. Le n-ieme geste
 * coute n crans, si bien que l'horizon s'allonge a mesure que le monde se
 * souvient — ce qui est la forme d'un savoir, pas d'une liste de courses.
 */
class RepertoireUnlocker
{
    public function __construct(
        private readonly RepertoireCatalog $catalog,
        private readonly RepertoireDominance $dominance,
        private readonly RareConditionEvaluator $conditions,
        private readonly RepertoireGestureRepository $gestures,
        private readonly RepertoireReadingRepository $readings,
        private readonly WorldLoadService $worldLoad,
        private readonly WorldFactService $worldFacts,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Le nombre de lectures qu'il faut pour retrouver le n-ieme geste.
     */
    public function thresholdFor(int $rank): int
    {
        $unlock = $this->catalog->unlockThresholds();

        $step = max(
            $unlock['floor'],
            (int) round($unlock['per_effective_player'] * $this->worldLoad->effectivePopulation()),
        );

        return $step * max(1, $rank);
    }

    /**
     * Le total des lectures versees depuis toujours.
     */
    public function totalReadings(): int
    {
        return array_sum($this->readings->tallyByElement());
    }

    /**
     * Retrouve les gestes que le seuil autorise, et les annonce.
     *
     * **Idempotent** : appele deux fois de suite, le second appel ne retrouve
     * rien. Un geste retrouve ne se re-perd jamais, mais il ne se retrouve pas
     * deux fois non plus.
     *
     * @return list<string> les cles retrouvees pendant cet appel
     */
    public function unlockDue(): array
    {
        $recovered = [];
        $total = $this->totalReadings();

        // Une boucle plutot qu'un seul cran : un monde qui a lu beaucoup
        // pendant que le planificateur dormait doit rattraper son retard, sinon
        // une panne du worker priverait le serveur de gestes qu'il a merites.
        // La borne est le bassin lui-meme — on ne peut pas retrouver plus de
        // gestes qu'il n'en existe.
        while (\count($recovered) < \count($this->catalog->foundGestures())) {
            $rank = $this->gestures->recoveredCount() + 1;

            if ($total < $this->thresholdFor($rank)) {
                break;
            }

            $key = $this->nextGesture();
            if ($key === null) {
                // Le seuil est franchi mais rien n'est eligible : les gestes
                // restants portent des conditions que ce monde ne remplit pas
                // encore. On ne consomme pas le seuil — il retombera le jour ou
                // la condition sera remplie, et *une attente ne coute rien*
                // (la doctrine du sediment de la Crue).
                break;
            }

            $gesture = new RepertoireGesture($key, $rank);
            $this->entityManager->persist($gesture);
            $this->entityManager->flush();

            $this->announce($key);
            $recovered[] = $key;
        }

        return $recovered;
    }

    /**
     * Le prochain geste, choisi par la dominante.
     *
     * L'ordre du canon : **l'element decide, la provenance departage, le lieu
     * departage ce que la provenance n'a pas tranche**. Les trois axes ne sont
     * pas de meme rang, et les traiter a egalite ferait qu'un monde qui lit du
     * feu partout et de l'eau aux Mines retrouverait un geste d'eau.
     */
    public function nextGesture(): ?string
    {
        $recovered = $this->gestures->recoveredKeys();
        $element = $this->dominance->element();
        $provenance = $this->dominance->provenance();
        $place = $this->dominance->place();

        $candidates = [];
        foreach ($this->catalog->foundGestures() as $key => $gesture) {
            if (\in_array($key, $recovered, true)) {
                continue;
            }

            if ($gesture['condition'] !== null && !$this->conditions->isMet($gesture['condition'])) {
                continue;
            }

            // L'element est la borne, pas un critere de tri : un monde qui lit
            // du feu ne retrouve pas « le geste d'eau le mieux classe », il ne
            // retrouve pas de geste d'eau du tout.
            if ($element !== null && !\in_array($element, $gesture['elements'], true)) {
                continue;
            }

            $candidates[$key] = [
                $provenance !== null && \in_array($provenance, $gesture['provenances'], true) ? 0 : 1,
                $place !== null && \in_array($place, $gesture['places'], true) ? 0 : 1,
                $key,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        uasort($candidates, static fn (array $a, array $b): int => $a <=> $b);

        return array_key_first($candidates);
    }

    /**
     * L'annonce au journal de monde — « les Lecteurs ont retrouve le geste
     * de... ».
     *
     * Le slug rend l'ecriture **idempotente** : republier un fait deja ecrit le
     * met a jour au lieu de le dupliquer, ce qui protege le journal d'une
     * relance du planificateur.
     */
    private function announce(string $key): void
    {
        $gesture = $this->catalog->foundGestures()[$key];

        $this->worldFacts->recordWorldFact(
            'repertoire-' . $key,
            'Un geste retrouvé',
            $gesture['revelation'],
            null,
            ['en' => 'A gesture recovered'],
            ['en' => $gesture['revelation_en']],
        );
    }
}
