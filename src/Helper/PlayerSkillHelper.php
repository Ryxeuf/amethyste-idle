<?php

namespace App\Helper;

use App\Entity\App\Player;
use App\Entity\Game\Skill;

class PlayerSkillHelper
{
    public const int MAX_TOTAL_SKILL_POINTS = 500;

    /** Motifs de refus, utilises comme suffixe de cle de traduction. */
    public const REFUSAL_NO_PLAYER = 'no_player';
    public const REFUSAL_ALREADY_ACQUIRED = 'already_acquired';
    public const REFUSAL_GLOBAL_CAP = 'global_cap';
    public const REFUSAL_NOT_ENOUGH_XP = 'not_enough_xp';
    public const REFUSAL_MISSING_REQUIREMENTS = 'missing_requirements';

    public function __construct(private readonly PlayerHelper $playerHelper, private readonly PlayerDomainHelper $playerDomainHelper)
    {
    }

    public function canAcquireSkill(Skill $skill): bool
    {
        return null === $this->refusalFor($skill);
    }

    /**
     * Pourquoi une competence ne peut pas etre apprise, ou `null` si elle le peut.
     *
     * L'ecran d'apprentissage annoncait « Compétence acquise avec succès ! »
     * meme quand rien n'etait acquis : le refus etait muet, donc indiagnosticable.
     * Un seul endroit decide, et il dit son motif.
     *
     * @return self::REFUSAL_*|null
     */
    public function refusalFor(Skill $skill): ?string
    {
        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return self::REFUSAL_NO_PLAYER;
        }

        if ($player->hasSkill($skill)) {
            return self::REFUSAL_ALREADY_ACQUIRED;
        }

        // Limite globale multi-domaine
        if ($this->getTotalUsedPoints($player) + $skill->getRequiredPoints() > self::MAX_TOTAL_SKILL_POINTS) {
            return self::REFUSAL_GLOBAL_CAP;
        }

        // Multi-domaine : il faut assez de points dans AU MOINS UN des domaines.
        // Une competence sans domaine reste apprenable si elle ne coute rien —
        // l'ancienne boucle la refusait, faute d'iteration.
        $hasEnoughPoints = 0 === $skill->getRequiredPoints();
        foreach ($skill->getDomains() as $domain) {
            if ($this->playerDomainHelper->getAvailableDomainExperience($domain, $player) >= $skill->getRequiredPoints()) {
                $hasEnoughPoints = true;
                break;
            }
        }

        if (!$hasEnoughPoints) {
            return self::REFUSAL_NOT_ENOUGH_XP;
        }

        return $this->meetsRequirements($player, $skill) ? null : self::REFUSAL_MISSING_REQUIREMENTS;
    }

    public function getTotalUsedPoints(?Player $player = null): int
    {
        $player = $player ?? $this->playerHelper->getPlayer();
        $total = 0;
        foreach ($player?->getDomainExperiences() ?? [] as $domainExperience) {
            $total += $domainExperience->getUsedExperience();
        }

        return $total;
    }

    public function hasSkill(Skill $skill): bool
    {
        return $this->playerHelper->getPlayer()?->hasSkill($skill) ?? false;
    }

    /**
     * Tous les prerequis de la competence sont-ils acquis ?
     *
     * Le test precedent passait par `array_intersect` sur des entites, qui les
     * compare **converties en chaine** — donc par leur titre. Or les titres se
     * repetent d'un arbre a l'autre (« Concentration », « Vitalite »…) : deux
     * competences homonymes deja apprises comptaient pour deux correspondances
     * d'un meme prerequis, l'egalite des cardinalites devenait fausse, et la
     * competence restait bloquee sans explication. La comparaison porte sur les
     * identifiants.
     */
    private function meetsRequirements(Player $player, Skill $skill): bool
    {
        foreach ($skill->getRequirements() as $requirement) {
            if (!$player->hasSkill($requirement)) {
                return false;
            }
        }

        return true;
    }
}
