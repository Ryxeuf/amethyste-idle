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
 * de l'etal loue (ECO Piste D). **L'Autel d'eveil en etait un jusqu'a REP-04** :
 * il attendait la purete (ECO-22), livree depuis, et il a desormais son ecran.
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
     * La banque pointe sur l'ecran d'inventaire et non sur
     * `app_game_inventory_bank_list` : cette route-la rend un **fragment** Turbo,
     * qui affiche une liste nue hors de son cadre. Une porte doit mener a un
     * ecran, pas a un morceau d'ecran.
     *
     * @var array<string, string> service => nom de route
     */
    private const ROUTES = [
        'regional_market' => 'app_game_auction',
        // REP-04 : le nom etait **faux**. `IndexController` porte l'attribut de
        // route sur la classe (`app_game_inventory`) et Symfony suffixe celui de
        // la methode : la route s'appelle `app_game_inventory_index`. Le defaut
        // etait latent — `zone_bank` s'ouvre a la Cite, qu'aucun foyer du monde
        // livre n'atteint —, donc il attendait le premier serveur assez vieux
        // pour l'y conduire, et il aurait alors casse le panneau de foyer plutot
        // que d'y manquer une ligne. C'est le contrat gate ↔ routeur qui l'a
        // trouve, en cherchant l'Autel.
        'zone_bank' => 'app_game_inventory_index',
        // REP-04 : l'Autel cesse d'etre une promesse. Il etait **gate sans etre
        // route** — le panneau de foyer l'annoncait ouvert a la Metropole, et
        // il ne menait a rien.
        'awakening_altar' => 'app_game_awakening',
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
     * Les services **branches** et leur route, lisibles de l'exterieur (REP-04).
     *
     * Rendue publique pour que le contrat gate ↔ routeur puisse interroger la
     * table plutot que la recopier : *une regle recopiee derive de son original
     * en silence*, et un contrat qui porterait sa propre copie de la liste
     * cesserait de mesurer quoi que ce soit des qu'elle vieillirait.
     *
     * @return array<string, string>
     */
    public static function routes(): array
    {
        return self::ROUTES;
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
