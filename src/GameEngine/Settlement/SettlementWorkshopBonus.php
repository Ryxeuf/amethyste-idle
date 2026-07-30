<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Zone;
use App\Repository\SettlementRepository;

/**
 * On voyage pour crafter (FOY-07).
 *
 * Une ville est bonne a quelque chose de precis (GAME_WORLD § 5.2). Le bonus
 * d'atelier se lit sur trois choses, et **jamais sur le joueur** :
 *
 * - le **rang** du foyer — un etabli de Metropole vaut mieux qu'un etabli de
 *   Hameau, quoi qu'on y forge ;
 * - le **type** du foyer — ce que la ville a fini par devenir, deduit de son
 *   indice dominant, jamais choisi ;
 * - la **ligne de production** de la zone (§ 5.1) — une epee se forge mieux la
 *   ou le metal sort.
 *
 * La consequence recherchee est un arbitrage geographique : l'artisan s'installe
 * ou son metier est bon, et une guilde qui fait monter un foyer choisit
 * litteralement le metier de sa region.
 *
 * **Le plafond n'est pas un detail de calibrage.** Sans lui, une Metropole batie
 * sur sa propre ligne accorderait un bonus superieur a la specialisation de
 * metier — un choix irreversible, paye en experience de domaine. Un lieu ne doit
 * pas valoir plus qu'une carriere.
 *
 * **Hors zone, rien.** Le bonus se lit sur la zone courante du personnage : il
 * n'y a pas d'atelier a distance, et c'est ce qui fait du deplacement une
 * decision plutot qu'une formalite.
 */
class SettlementWorkshopBonus
{
    /**
     * @var array{
     *     rank_bonus: array<string, int>,
     *     type_bonus: array<string, array<string, int>>,
     *     line_bonus: array<string, array<string, int>>,
     *     cap: int,
     *     zone_line: array<string, string>
     * }|null
     */
    private ?array $workshop = null;

    public function __construct(
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementDefinitionLoader $loader,
    ) {
    }

    /**
     * Points de chance de qualite superieure accordes par le lieu.
     *
     * Rendus dans la meme unite que le bonus de specialisation, parce qu'ils
     * s'additionnent a lui : deux unites differentes se seraient melangees tot
     * ou tard, et le jour ou elles l'auraient fait, rien ne l'aurait signale.
     */
    public function bonusFor(?Zone $zone, string $craft): int
    {
        return $this->describe($zone, $craft)->total;
    }

    /**
     * Le bonus **et sa composition**, pour l'ecran d'artisanat.
     */
    public function describe(?Zone $zone, string $craft): WorkshopBonus
    {
        if ($zone === null) {
            return WorkshopBonus::none();
        }

        $settlement = $this->settlementRepository->findOneByZone($zone);
        if ($settlement === null) {
            // le Fanal et les Jardins sont batis sur la Voute : rien ne s'y
            // depose, donc rien n'y ameliore un etabli. Le plancher T1 y reste
            // entier (ECO-02) — un debutant n'est pas penalise, il n'est
            // simplement pas avantage.
            return WorkshopBonus::none();
        }

        $workshop = $this->workshop();
        $rank = $settlement->getRank();
        $type = $settlement->getType();
        $line = $workshop['zone_line'][$zone->getSlug()] ?? null;

        $rankPart = $workshop['rank_bonus'][$rank->value] ?? 0;
        $typePart = $type === null ? 0 : ($workshop['type_bonus'][$type->value][$craft] ?? 0);
        $linePart = $line === null ? 0 : ($workshop['line_bonus'][$line][$craft] ?? 0);

        $raw = $rankPart + $typePart + $linePart;
        $total = min($raw, $workshop['cap']);

        return new WorkshopBonus(
            $rankPart,
            $typePart,
            $linePart,
            $total,
            $total < $raw,
            $rank,
            $type,
            $line,
        );
    }

    /**
     * @return array{
     *     rank_bonus: array<string, int>,
     *     type_bonus: array<string, array<string, int>>,
     *     line_bonus: array<string, array<string, int>>,
     *     cap: int,
     *     zone_line: array<string, string>
     * }
     */
    private function workshop(): array
    {
        if ($this->workshop === null) {
            $this->workshop = $this->loader->load()['workshop'];
        }

        return $this->workshop;
    }
}
