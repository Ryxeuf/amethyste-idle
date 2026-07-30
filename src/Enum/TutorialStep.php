<?php

namespace App\Enum;

/**
 * Le tutoriel, projete sur l'arc `intro` (ONB-14).
 *
 * **Ferme la dette D7.** Deux etats d'onboarding vivaient cote a cote : la
 * colonne `player.tutorial_step`, avancee par cinq abonnements a des evenements
 * de jeu, et l'arc de quetes `intro`. Ils ne se parlaient pas. On pouvait
 * terminer le tutoriel sans avoir touche a l'arc, abandonner l'arc en restant
 * « en tutoriel », et « passer le tutoriel » ne fermait rien du tout.
 *
 * **L'arc est desormais la source, ce type n'en est qu'une vue.** Aucune valeur
 * n'est ecrite nulle part : chaque etape se deduit du nombre de quetes de l'arc
 * terminees. Deux etats ne peuvent plus diverger quand il n'y en a qu'un.
 *
 * Les cinq cases sont conservees, **valeurs comprises** : les dialogues de PNJ
 * s'y branchent par leur entier (`tutorial_step: [0..4]`), et les renumeroter
 * aurait fait dire n'importe quoi au guide du Fanal sans rien casser de visible.
 * Ce qui change est leur **sens** — les anciennes etapes enseignaient le voyage
 * en premier, c'est-a-dire exactement l'ordre qu'ONB-12b a defait.
 */
enum TutorialStep: int
{
    /** L'arme et la voie qui l'autorise — tour 1 de la boucle. */
    case Weapon = 0;

    /** La materia : reçue, accordee, lancee — tour 2. */
    case Materia = 1;

    /** Le metier de recolte, et ce qu'on en fait — tour 3. */
    case Trade = 2;

    /** Le depart : le voyage coute du temps reel. */
    case Departure = 3;

    /** L'expedition : quitter le jeu en le laissant travailler. */
    case Expedition = 4;

    /**
     * Nombre d'etapes de l'arc `intro` (GAME_ONBOARDING § 5.2).
     */
    public const ARC_STEPS = 10;

    /**
     * Bornes de projection : quete terminee a partir de laquelle l'etape commence.
     *
     * @var array<int, int>
     */
    private const REACHED_AT = [
        0 => 0,  // Weapon     — des le reveil
        1 => 2,  // Materia    — l'arme portee
        2 => 5,  // Trade      — le sort lance
        3 => 8,  // Departure  — l'atelier fait
        4 => 9,  // Expedition — le voyage fait
    ];

    /**
     * L'etape en cours, deduite des quetes de l'arc terminees.
     *
     * Rend `null` quand l'arc est termine : c'est la meme absence que « pas de
     * tutoriel en cours », et le rendre distinct obligerait chaque appelant a
     * traiter deux cas pour la meme situation.
     */
    public static function fromCompletedSteps(int $completed): ?self
    {
        if ($completed >= self::ARC_STEPS) {
            return null;
        }

        $current = self::Weapon;
        foreach (self::REACHED_AT as $value => $threshold) {
            if ($completed >= $threshold) {
                $current = self::from($value);
            }
        }

        return $current;
    }

    public function label(): string
    {
        return match ($this) {
            self::Weapon => 'L\'arme',
            self::Materia => 'La materia',
            self::Trade => 'Le metier',
            self::Departure => 'Le depart',
            self::Expedition => 'L\'expedition',
        };
    }

    public function objective(): string
    {
        return match ($this) {
            self::Weapon => 'Choisissez une arme chez la maitresse d\'armes, puis apprenez a la porter.',
            self::Materia => 'Battez le mannequin, accordez la materia recue, puis lancez son sort.',
            self::Trade => 'Choisissez un metier de recolte, allez recolter, puis fabriquez.',
            self::Departure => 'Voyagez vers une vraie zone — le trajet coute du temps reel.',
            self::Expedition => 'Lancez une expedition avant de fermer : votre personnage travaillera sans vous.',
        };
    }

    /**
     * Ou envoyer le joueur pour avancer.
     *
     * Le lien vivait dans le gabarit, sous la forme d'un `if` sur la valeur
     * entiere de l'etape — et il n'en couvrait que trois sur cinq. Ajouter une
     * etape demandait donc de penser a deux endroits, dont un qui ne se plaint
     * jamais. Toute etape a une destination : c'est ce que le type dit.
     */
    public function hintRoute(): string
    {
        return match ($this) {
            self::Weapon, self::Materia, self::Departure, self::Expedition => 'app_game_zone',
            self::Trade => 'app_game_quests',
        };
    }

    public function stepNumber(): int
    {
        return $this->value + 1;
    }

    public static function totalSteps(): int
    {
        return \count(self::cases());
    }
}
