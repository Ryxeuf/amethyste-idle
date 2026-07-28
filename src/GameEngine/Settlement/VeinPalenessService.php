<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\ZoneVein;
use App\Repository\ZoneVeinRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * L'extraction laisse une trace (FOY-11).
 *
 * Graduelle, bornee, **reversible** — jamais une Etale (GAME_WORLD § 12.1).
 * L'Etale est un lieu ancien et permanent ; la Paleur est ce qu'un serveur fait
 * a un filon en le pressant, et ce qu'il defait en le laissant respirer.
 *
 * **Par filon, jamais par zone.** C'est la decision qui porte tout le jalon :
 * elle garantit que la Paleur ne frappe que l'exploitation *concentree* et
 * jamais le passage diffus des debutants (§3.5). Un agregat de zone punirait
 * cinquante joueurs occasionnels pour ce qu'une guilde a fait a un seul filon.
 *
 * **Ce qui se mesure est un rythme, pas un cumul.** La pression compare ce
 * qu'on a pris dans la journee au **debit soutenu** du filon sur la meme
 * journee : `R = capacity x 86400 / respawn_seconds`. Au-dessus de 1, on prend
 * plus vite que ca ne repousse. Un cumul historique aurait fait payer
 * eternellement une ruee d'un soir.
 *
 * **Jamais de jachere** (§3.5). La recuperation court **pendant qu'on joue** :
 * un filon frequente sous son debit se refait, il n'y a aucune phase a
 * proteger et donc rien a gagner a s'abstenir collectivement. Elle est
 * simplement plus lente que la montee — abimer va plus vite que reparer, sinon
 * la trace n'en serait pas une.
 *
 * Aucune horloge nouvelle : le calcul se greffe sur `app:settlement:tick`,
 * deja quotidien et deja le tick ou le monde vieillit.
 */
class VeinPalenessService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ZoneVeinRepository $veinRepository,
        private readonly SettlementDefinitionLoader $loader,
    ) {
    }

    /**
     * @return array{processed: int, dulled: int, recovered: int}
     */
    public function tick(): array
    {
        $definition = $this->loader->load()['paleness'];
        $report = ['processed' => 0, 'dulled' => 0, 'recovered' => 0];

        foreach ($this->veinRepository->findAll() as $vein) {
            $before = $vein->getPaleness();
            $after = self::step($before, $this->pressureOf($vein), $definition);

            $vein->setPaleness($after);
            // Le compteur repart a zero : ce qu'on mesure est la journee, pas
            // l'histoire du filon.
            $vein->setExtractedSinceTick(0);

            ++$report['processed'];
            if ($after > $before) {
                ++$report['dulled'];
            } elseif ($after < $before) {
                ++$report['recovered'];
            }
        }

        $this->entityManager->flush();

        return $report;
    }

    /**
     * Un jour de Paleur.
     *
     * Statique et pure : le calcul est le cœur du jalon, et il doit pouvoir se
     * verifier sans base de donnees.
     *
     * @param array{rise_per_pressure: float, daily_recovery: float, max: float, visible_from: float, dulls_purity_from: float} $definition
     */
    public static function step(float $paleness, float $pressure, array $definition): float
    {
        if ($pressure > 1.0) {
            $risen = $paleness + $definition['rise_per_pressure'] * ($pressure - 1.0);

            // Plancher dur : un filon pali ne devient **jamais** sterile.
            return min($definition['max'], $risen);
        }

        // Exactement au debit soutenu, le filon **tient** : il ne s'abime pas et
        // ne se refait pas. C'est le regime d'equilibre que vise le calibrage
        // (BALANCE § 22.3), et le faire guerir la reviendrait a dire qu'une
        // exploitation parfaitement soutenable repare le passe.
        if ($pressure === 1.0) {
            return $paleness;
        }

        return max(0.0, $paleness - $definition['daily_recovery']);
    }

    /**
     * Ce qu'on a pris rapporte a ce que le filon rend de lui-meme.
     *
     * Un filon dont la definition a disparu de la config de zone rend une
     * pression nulle : il se refait tout seul et finit oublie, ce qui est
     * exactement le comportement voulu pour un residu.
     */
    private function pressureOf(ZoneVein $vein): float
    {
        $sustained = $this->sustainedDailyYield($vein);
        if ($sustained <= 0.0) {
            return 0.0;
        }

        return $vein->getExtractedSinceTick() / $sustained;
    }

    /**
     * Debit soutenu quotidien du filon : `capacity x 86400 / respawn_seconds`.
     *
     * Lu sur la **definition declarative** de la zone et non sur l'etat du
     * filon : c'est ce que le filon rendrait si personne ne le pressait, et
     * c'est donc le bon denominateur.
     */
    private function sustainedDailyYield(ZoneVein $vein): float
    {
        foreach ($vein->getZone()->getGatherResources() as $resource) {
            if (($resource['slug'] ?? null) !== $vein->getSlug()) {
                continue;
            }

            $capacity = max(0, (int) ($resource['capacity'] ?? 0));
            $respawn = max(0, (int) ($resource['respawn_seconds'] ?? 0));

            return $respawn > 0 ? $capacity * 86400 / $respawn : 0.0;
        }

        return 0.0;
    }
}
