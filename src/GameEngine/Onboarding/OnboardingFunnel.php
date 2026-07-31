<?php

declare(strict_types=1);

namespace App\GameEngine\Onboarding;

/**
 * Les sept indicateurs du tunnel d'entree (ONB-19b).
 *
 * GAME_ONBOARDING § 9 : *sans mesure, on repare a l'aveugle*. Le plan a ferme
 * ses invariants avec ONB-19a — ce qui ne doit **jamais** arriver est desormais
 * tenu par un test. Reste ce qu'aucun test ne peut dire : ou les gens
 * s'arretent.
 *
 * **Aucun indicateur ne mesure une intention.** Chacun se derive d'un etat deja
 * en base : un compte sans personnage est un abandon dans le tunnel, un foyer
 * d'attache reclame est un acte I termine, un arbre ouvert est un choix fait.
 * Rien n'a ete instrumente pour ce jalon, et c'est la garantie que la mesure ne
 * dérive pas de ce qu'elle mesure.
 *
 * **Les trois repartitions sortent d'une seule table.** `PlayerDomainAccess`
 * porte les arbres ouverts, et `Domain` porte deja les deux bornes de DOM-01 —
 * l'element et le registre. Le registre nomme l'arme (melee, distance, sorts) ;
 * son absence nomme le metier (« un `null` sur `Domain::register` dit hors
 * combat, jamais registre inconnu »). Ajouter un element ou un registre etend
 * donc la mesure le jour meme, sans y toucher.
 */
final readonly class OnboardingFunnel
{
    /**
     * Fenetre de retour : « encore la le lendemain », « encore la la semaine
     * d'apres ».
     */
    public const RETURN_DAYS = [1, 7];

    /**
     * @param int                $accounts              comptes crees
     * @param int                $accountsWithCharacter comptes ayant au moins un personnage
     * @param int                $characters            personnages crees
     * @param int                $actOneCompleted       personnages ayant reclame leur foyer d'attache
     * @param int                $onboardingSkipped     personnages ayant refuse le tutoriel
     * @param int                $matureAccounts        comptes crees il y a plus de 7 jours
     * @param int                $verifiedAmongMature   parmi eux, ceux dont l'e-mail est verifie
     * @param array<int, int>    $stillActive           jour de recul => personnages encore actifs apres ce delai
     * @param array<string, int> $races                 peuple => nombre de personnages
     * @param array<string, int> $weapons               registre de combat => nombre d'arbres ouverts
     * @param array<string, int> $elements              element => nombre d'arbres ouverts
     * @param array<string, int> $crafts                metier (arbre hors combat) => nombre d'arbres ouverts
     */
    public function __construct(
        public int $accounts = 0,
        public int $accountsWithCharacter = 0,
        public int $characters = 0,
        public int $actOneCompleted = 0,
        public int $onboardingSkipped = 0,
        public int $matureAccounts = 0,
        public int $verifiedAmongMature = 0,
        public array $stillActive = [],
        public array $races = [],
        public array $weapons = [],
        public array $elements = [],
        public array $crafts = [],
    ) {
    }

    /**
     * Comptes crees qui n'ont jamais abouti a un personnage.
     *
     * C'est **l'indicateur d'abandon dans le tunnel**, et il se derive plutot
     * que de se compter : un compteur d'abandons serait a maintenir a chaque
     * pas du tunnel, et se tromperait au premier pas ajoute.
     */
    public function abandonedInTunnel(): int
    {
        return max(0, $this->accounts - $this->accountsWithCharacter);
    }

    /** Part des comptes qui ont abouti a un personnage. */
    public function characterShare(): ?int
    {
        return self::share($this->accountsWithCharacter, $this->accounts);
    }

    /** Part des personnages qui ont mene l'acte I a son terme. */
    public function actOneShare(): ?int
    {
        return self::share($this->actOneCompleted, $this->characters);
    }

    /**
     * Part des comptes assez vieux dont l'e-mail est verifie.
     *
     * Restera a 0 % tant qu'ONB-02/04 ne sont pas livres : rien, dans le code,
     * ne renseigne `emailVerifiedAt`. L'ecran le dit — un zero qu'on prend pour
     * un resultat au lieu d'une absence est pire qu'une case vide.
     */
    public function verifiedShare(): ?int
    {
        return self::share($this->verifiedAmongMature, $this->matureAccounts);
    }

    /** Part des personnages encore actifs N jours apres leur creation. */
    public function stillActiveShare(int $days): ?int
    {
        return self::share($this->stillActive[$days] ?? 0, $this->characters);
    }

    /**
     * Part d'un total, en pourcentage entier — `null` si le total est nul.
     *
     * **`null` plutot que zero**, et c'est le seul endroit du jalon ou la forme
     * porte une regle : « aucun compte » et « aucun compte abouti » se lisent
     * pareil a l'ecran alors qu'ils ne disent pas du tout la meme chose. Un taux
     * sur zero observation n'est pas zero — il n'existe pas, et un ecran de
     * pilotage qui affiche 0 % le jour de l'ouverture fait paniquer pour rien.
     */
    private static function share(int $part, int $total): ?int
    {
        if ($total <= 0) {
            return null;
        }

        return (int) round(($part / $total) * 100);
    }
}
