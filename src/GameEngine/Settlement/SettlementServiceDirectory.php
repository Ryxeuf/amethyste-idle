<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Zone;
use App\Enum\SettlementRank;

/**
 * Ou mene un service ouvert par le rang (FOY-06).
 *
 * `SettlementGate` dit **si** un service est ouvert ; ce repertoire dit **ou il
 * mene**. La separation compte : le gate est une regle de jeu et se teste sans
 * routeur, le repertoire est du branchement d'ecran et change au rythme des
 * jalons qui livrent les services.
 *
 * Un service declare dans `settlements.yaml` mais absent de `ROUTES` est une
 * **promesse** : le palier l'annonce (`SettlementPanelBuilder::opensAt()`), et
 * rien ne s'affiche tant que le jalon qui le livre n'est pas passe. C'est le cas
 * de l'etal loue (ECO Piste D) et de l'Autel d'eveil, qui attend la purete
 * (ECO-22) — sans bande `parfait`, un rite d'eveil n'aurait rien a consommer.
 *
 * Les routes vivent ici et non dans le YAML : ce n'est pas du calibrage. La
 * regle « rien en dur » de PLAN_SETTLEMENTS porte sur les seuils, les taux et
 * les quotas — ce qu'un game designer regle sans redeploiement. Un nom de route
 * change avec le code, et il doit casser avec lui.
 */
class SettlementServiceDirectory
{
    /**
     * Services **branches** : ceux que l'ecran de zone sait atteindre.
     *
     * Aucun d'eux ne ferme un ecran existant. Le menu du HV et l'onglet banque
     * restent joignables partout, quel que soit le rang — la decision A interdit
     * de retirer un acces acquis. Ce que le foyer ouvre, c'est de les trouver
     * **sur place**, dans la zone qu'on a fait monter.
     *
     * @var array<string, string> service => nom de route
     */
    private const ROUTES = [
        'regional_market' => 'app_game_auction',
        'zone_bank' => 'app_game_inventory_bank_list',
    ];

    public function __construct(
        private readonly SettlementGate $gate,
    ) {
    }

    /**
     * Les services branches de cette zone, ouverts ou non.
     *
     * Les fermes sont rendus eux aussi : un service absent de l'ecran n'apprend
     * rien, alors qu'un service affiche avec son rang manquant transforme le
     * chiffre du foyer en projet.
     *
     * @return list<array{service: string, required: SettlementRank, open: bool, route: string}>
     */
    public function forZone(Zone $zone): array
    {
        $rows = [];

        foreach ($this->gate->services() as $service => $required) {
            $route = self::ROUTES[$service] ?? null;
            if ($route === null) {
                continue;
            }

            $rows[] = [
                'service' => $service,
                'required' => $required,
                'open' => $this->gate->allows($zone, $service),
                'route' => $route,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $a['required']->level() <=> $b['required']->level());

        return $rows;
    }

    /**
     * Services dont le seuil est franchi entre deux rangs, dans un sens ou dans
     * l'autre.
     *
     * Sert a l'annonce : une promotion dit ce qui ouvre, une regression dit ce
     * qui se ferme. La meme fonction rend les deux — les enumerer separement
     * aurait laisse deriver l'un des deux cotes, et c'est le cote « ferme » qui
     * aurait ete oublie.
     *
     * @return list<string> triee par rang requis, du plus bas au plus haut
     */
    public function crossedBetween(SettlementRank $from, SettlementRank $to): array
    {
        $low = min($from->level(), $to->level());
        $high = max($from->level(), $to->level());

        $crossed = [];
        foreach ($this->gate->services() as $service => $required) {
            $level = $required->level();
            if ($level > $low && $level <= $high) {
                $crossed[$service] = $level;
            }
        }

        asort($crossed);

        return array_keys($crossed);
    }
}
