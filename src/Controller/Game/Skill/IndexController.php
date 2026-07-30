<?php

namespace App\Controller\Game\Skill;

use App\Dto\Domain\DomainModel;
use App\Dto\Domain\PlayerDomain;
use App\Dto\Skill\SkillPlayer;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Enum\CombatRegister;
use App\GameEngine\Fight\BuildDomainResolver;
use App\GameEngine\Progression\BuildPresetManager;
use App\GameEngine\Progression\DomainAccessManager;
use App\GameEngine\Progression\SkillRespecManager;
use App\Helper\PlayerDomainHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use App\Transformer\PlayerSkillTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/skills', name: 'app_game_skills')]
class IndexController extends AbstractController
{
    public function __construct(
        private readonly PlayerDomainHelper $playerDomainHelper,
        private readonly PlayerSkillTransformer $playerSkillDataTransformer,
        private readonly PlayerSkillHelper $skillHelper,
        private readonly PlayerHelper $playerHelper,
        private readonly SkillRespecManager $respecManager,
        private readonly BuildPresetManager $presetManager,
        private readonly BuildDomainResolver $buildDomainResolver,
        private readonly DomainAccessManager $domainAccessManager,
    ) {
    }

    public function __invoke(): Response
    {
        $player = $this->playerHelper->getPlayer();

        // ONB-09 — l'ecran des arbres montre **les arbres ouverts**, le
        // catalogue montre tous les autres.
        //
        // Il listait jusqu'ici les domaines ou le joueur avait de l'experience,
        // ce qui produisait deux defauts opposes : un arbre tout juste ouvert
        // n'y apparaissait pas (aucune ligne d'experience encore), et un arbre
        // **ferme** pouvait y apparaitre — `CrossDomainSkillResolver` credite
        // tous les domaines d'un nœud partage, y compris ceux ou l'on n'est
        // jamais entre.
        $domains = $player !== null ? $this->domainAccessManager->openedDomains($player) : [];

        $domainsModels = array_map($this->transformDomain(...), $domains);

        // DOM-02 : l'ecran dit ce que le build exprime, et ce qu'il faudrait
        // porter pour le reste. Le savoir n'est jamais perdu — seule son
        // expression depend de l'equipement, et c'est cette distinction que
        // l'affichage doit rendre lisible plutot que de la laisser deviner.
        if ($player !== null) {
            foreach ($domainsModels as $model) {
                $this->annotateActivation($player, $model);
            }
        }

        $buildStats = $this->computeBuildStats($domainsModels);

        return $this->render('game/skills/index.html.twig', [
            'domains' => $domainsModels,
            'buildStats' => $buildStats,
            'respecCost' => $player ? $this->respecManager->getRespecCost($player) : 0,
            'canRespec' => $player ? $this->respecManager->canRespec($player) : false,
            'playerGils' => $player ? $player->getGils() : 0,
            'skillCount' => $player ? $player->getSkills()->count() : 0,
            'totalUsedPoints' => $player ? $this->skillHelper->getTotalUsedPoints($player) : 0,
            'maxTotalPoints' => PlayerSkillHelper::MAX_TOTAL_SKILL_POINTS,
            'presets' => $player ? $this->presetManager->getPresets($player) : [],
            'canSavePreset' => $player ? $this->presetManager->canSave($player) : false,
            'maxPresets' => BuildPresetManager::MAX_PRESETS_PER_PLAYER,
        ]);
    }

    /**
     * @param DomainModel[] $domainsModels
     *
     * @return array{damage: int, heal: int, hit: int, critical: int, life: int, count: int}
     */
    private function computeBuildStats(array $domainsModels): array
    {
        $stats = ['damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0, 'count' => 0];

        foreach ($domainsModels as $domain) {
            foreach ($domain->skills as $skill) {
                if (!($skill instanceof SkillPlayer) || !$skill->acquired) {
                    continue;
                }
                $stats['damage'] += $skill->damage;
                $stats['heal'] += $skill->heal;
                $stats['hit'] += $skill->hit;
                $stats['critical'] += $skill->critical;
                $stats['life'] += $skill->life;
                ++$stats['count'];
            }
        }

        return $stats;
    }

    /**
     * Dit si le domaine s'exprime, et sinon ce qu'il faudrait porter.
     *
     * Le conseil est **une phrase, pas un code d'erreur** : « inactif » seul se
     * lirait comme une punition, alors que la borne est materielle et se leve en
     * changeant d'equipement.
     */
    private function annotateActivation(Player $player, DomainModel $model): void
    {
        $register = $model->entity->getRegister();
        if ($register === null) {
            return;
        }

        $model->activeInBuild = $this->buildDomainResolver->isActive($player, $model->entity);
        if ($model->activeInBuild) {
            return;
        }

        $model->activationHint = $register === CombatRegister::Spell
            ? 'Sertissez une matéria de cet élément pour l\'exprimer.'
            : sprintf('Équipez une arme de %s pour l\'exprimer.', mb_strtolower($register->label()));
    }

    private function transformDomain(Domain $domain): DomainModel
    {
        $output = new PlayerDomain($domain);
        // Un arbre ouvert n'a pas forcement de ligne d'experience : ouvrir est
        // un acte, gagner de l'experience en est un autre. Le premier jour d'un
        // arbre, tout est a zero — et c'est la bonne reponse, pas une erreur.
        $domainExperience = $this->playerDomainHelper->getDomainExperience($domain);

        foreach ($domain->getSkills() as $skill) {
            $playerSkillOutput = $this->playerSkillDataTransformer->transform($skill);
            $playerSkillOutput->acquired = $this->skillHelper->hasSkill($skill);
            $playerSkillOutput->canBeAcquired = $this->skillHelper->canAcquireSkill($skill);

            $output->skills[] = $playerSkillOutput;
        }

        $output->availableExperience = $domainExperience?->getAvailableExperience() ?? 0;
        $output->totalExperience = $domainExperience?->getTotalExperience() ?? 0;
        $output->damage = $domainExperience?->getDamage() ?? 0;
        $output->hit = $domainExperience?->getHit() ?? 0;
        $output->critical = $domainExperience?->getCritical() ?? 0;

        return $output;
    }
}
