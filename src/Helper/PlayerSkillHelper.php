<?php

namespace App\Helper;

use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\DomainAccessManager;

class PlayerSkillHelper
{
    public const int MAX_TOTAL_SKILL_POINTS = 500;

    /** Motifs de refus, utilises comme suffixe de cle de traduction. */
    public const REFUSAL_NO_PLAYER = 'no_player';
    public const REFUSAL_ALREADY_ACQUIRED = 'already_acquired';
    public const REFUSAL_GLOBAL_CAP = 'global_cap';
    public const REFUSAL_NOT_ENOUGH_XP = 'not_enough_xp';
    public const REFUSAL_MISSING_REQUIREMENTS = 'missing_requirements';
    /**
     * Le nœud appartient a l'autre branche de l'arbre (DOM-06).
     *
     * C'est le seul refus qui ne se leve pas en jouant : il se leve en
     * **renoncant** — par le respec de branche, qui se paie (DOM-04). Le motif
     * doit donc le dire, sinon le joueur cherchera un prerequis qui n'existe pas.
     */
    public const REFUSAL_OTHER_BRANCH = 'other_branch';
    /**
     * Le nœud est pose mais pas encore ouvert (DOM-07).
     *
     * L'accord d'hybride attend que la fusion ouvre. Le montrer et le refuser
     * vaut mieux que de le cacher : un arbre dont un nœud apparaitrait le jour
     * d'une mise a jour se relirait comme un ajout, alors que c'est une porte
     * qu'on savait la.
     */
    public const REFUSAL_DORMANT = 'dormant';
    /**
     * L'arbre n'est pas ouvert (ONB-08).
     *
     * Ce refus se leve en **lisant un parchemin**, jamais en jouant plus : il
     * doit donc nommer le geste, sinon le joueur cherchera des points qu'il a
     * deja. Ce n'est pas un verrou de contenu — le parchemin est vendu a tout
     * le monde, a prix fixe, sans aucun prerequis.
     */
    public const REFUSAL_DOMAIN_CLOSED = 'domain_closed';

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerDomainHelper $playerDomainHelper,
        private readonly DomainAccessManager $domainAccessManager,
    ) {
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

        // DOM-07 : avant tout le reste. Un joueur qui a les points et les
        // prerequis d'un nœud dormant doit lire « pas encore ouvert », pas un
        // motif qui lui ferait croire qu'il lui manque quelque chose.
        if ($skill->isDormant()) {
            return self::REFUSAL_DORMANT;
        }

        // ONB-08 : un arbre ferme n'accorde aucun nœud. Le refus passe avant
        // les points, parce qu'il ne parle pas de la meme chose : compter des
        // points dans un arbre ou l'on n'est pas entre revient a repondre a une
        // question que le joueur n'a pas posee.
        //
        // Un nœud partage entre plusieurs arbres suffit a **un** arbre ouvert
        // (« plusieurs chemins pour la meme chose »), et une competence sans
        // domaine reste libre pour tous.
        if (!$this->domainAccessManager->isSkillReachable($player, $skill)) {
            return self::REFUSAL_DOMAIN_CLOSED;
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

        if (!$this->meetsRequirements($player, $skill)) {
            return self::REFUSAL_MISSING_REQUIREMENTS;
        }

        // DOM-06 : les nœuds terminaux d'un arbre d'artisanat appartiennent a
        // une branche. C'est ici que le choix de DOM-04 devient visible dans
        // l'arbre — sans quoi la specialisation resterait un bonus de qualite
        // sans aucune trace dans ce que le joueur apprend.
        return $this->matchesChosenBranch($player, $skill) ? null : self::REFUSAL_OTHER_BRANCH;
    }

    /**
     * Le nœud est-il de la branche que le joueur a prise ?
     *
     * Un nœud sans declaration de branche appartient a tout le monde : la
     * grande majorite de l'arbre reste commune, et seule la poignee de nœuds
     * terminaux se choisit.
     */
    private function matchesChosenBranch(Player $player, Skill $skill): bool
    {
        foreach ($skill->getActions() ?? [] as $descriptor) {
            if (!\is_array($descriptor) || ($descriptor['action'] ?? null) !== 'specialization.branch') {
                continue;
            }

            $craft = (string) ($descriptor['craft'] ?? '');
            $branch = (string) ($descriptor['branch'] ?? '');
            if ($craft === '' || $branch === '') {
                continue;
            }

            if ($player->getCraftSpecializationFor($craft)?->getBranch() !== $branch) {
                return false;
            }
        }

        return true;
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
