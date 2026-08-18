<?php

namespace App\Tests\Integration\Fight;

use App\Entity\Game\StatusEffect;
use App\Enum\CombatLever;
use App\Enum\CombatRegister;
use App\Enum\Element;
use App\Enum\SpellIntent;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Fight\StanceLaw;
use App\GameEngine\Fight\StatusEffectManager;
use App\GameEngine\Progression\CombatLeverScale;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * La posture, de bout en bout (ARC-18b).
 *
 * Trois choses a prouver, et la troisieme est la seule qui compte : la posture
 * **change ce que le personnage vaut**. Les deux premieres — une seule active,
 * elle ne vieillit pas — sont les garde-fous du canon ; la troisieme est la
 * difference entre une mecanique et une decoration.
 */
class StanceIntegrationTest extends AbstractIntegrationTestCase
{
    private StatusEffectManager $statusEffectManager;
    private CombatSkillResolver $skillResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statusEffectManager = $this->getService(StatusEffectManager::class);
        $this->skillResolver = $this->getService(CombatSkillResolver::class);
    }

    /**
     * **Une posture chasse la precedente.**.
     *
     * L'exclusivite est ce qui fait de la forme une decision : deux postures
     * cumulees seraient deux capstones portes ensemble, et surtout il n'y
     * aurait plus rien a arbitrer — on les prendrait toutes.
     */
    public function testASecondStanceReplacesTheFirst(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob();
        $fight = $this->createFight($player, $mob);

        $offensive = $this->stance('stance-braced', ['power' => 6, 'guard' => -6]);
        $defensive = $this->stance('stance-rooted', ['guard' => 6, 'power' => -6]);

        $this->statusEffectManager->applyStatusEffect($fight, $player, $offensive);
        self::assertSame(['stance-braced'], $this->stanceSlugsOn($fight, $player));

        $this->statusEffectManager->applyStatusEffect($fight, $player, $defensive);
        self::assertSame(['stance-rooted'], $this->stanceSlugsOn($fight, $player), 'Deux postures cohabitent : il n\'y a plus de choix a faire.');
    }

    /**
     * Reposer la posture qu'on tient deja ne fait rien.
     *
     * Le distinguo importe : le remplacement retire **les autres**, jamais
     * celle qu'on repose. Autrement, un geste sans effet deviendrait un geste
     * qui coupe puis remet — invisible aujourd'hui, faux le jour ou une posture
     * declenchera quelque chose en se posant.
     */
    public function testRepostingTheSameStanceIsANoOp(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob();
        $fight = $this->createFight($player, $mob);

        $stance = $this->stance('stance-braced', ['power' => 6, 'guard' => -6]);

        $this->statusEffectManager->applyStatusEffect($fight, $player, $stance);
        $this->statusEffectManager->applyStatusEffect($fight, $player, $stance);

        self::assertSame(['stance-braced'], $this->stanceSlugsOn($fight, $player));
    }

    /**
     * **Une posture ne vieillit pas.**.
     *
     * Elle traverse les tours sans etre decomptee : elle finit sur un
     * evenement — on en pose une autre, ou la rencontre s'acheve —, jamais sur
     * un compteur. Lui donner une duree ferait de la decision une attente.
     */
    public function testAStanceSurvivesEveryTurn(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob();
        $fight = $this->createFight($player, $mob);

        $this->statusEffectManager->applyStatusEffect($fight, $player, $this->stance('stance-braced', ['power' => 6, 'guard' => -6]));

        for ($turn = 1; $turn <= 12; ++$turn) {
            $fight->setStep($turn);
            $this->statusEffectManager->processStartOfTurn($fight, $player);
        }

        self::assertSame(['stance-braced'], $this->stanceSlugsOn($fight, $player), 'La posture a expire : elle a ete traitee comme une amelioration ordinaire.');
        self::assertSame(StanceLaw::HELD, $this->stanceRowOn($fight, $player)->getRemainingTurns());
    }

    /**
     * **La posture change ce que le personnage vaut** — la seule chose qui
     * fasse d'elle une mecanique plutot qu'une decoration.
     *
     * Elle se somme aux nœuds comme un dix-neuvieme nœud, ce qui lui fait
     * heriter gratuitement de toutes les bornes : ici, `power` sur un geste de
     * degat monte, et `guard` — qui ne qualifie pas l'intention de degat
     * (ARC-11b-b) — n'entre meme pas dans le porteur.
     */
    public function testAStanceMovesWhatTheCharacterIsWorth(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob();
        $fight = $this->createFight($player, $mob);

        /** @var CombatLeverScale $scale */
        $scale = $this->getService(CombatLeverScale::class);

        $before = $this->skillResolver->getLeverEffects($player, null, CombatRegister::Spell, SpellIntent::Damage, $fight);

        $this->statusEffectManager->applyStatusEffect($fight, $player, $this->stance('stance-braced', ['power' => 6, 'guard' => -6]));

        $after = $this->skillResolver->getLeverEffects($player, null, CombatRegister::Spell, SpellIntent::Damage, $fight);

        self::assertGreaterThan(
            $before->multiplierFor(CombatLever::Power, $scale),
            $after->multiplierFor(CombatLever::Power, $scale),
            'La posture ne deplace rien : elle est une decoration.'
        );

        // Hors combat, il n'y a rien a lire : une posture ne survit pas a la
        // rencontre, donc la fiche de personnage ne la voit jamais.
        $sheet = $this->skillResolver->getLeverEffects($player, null, CombatRegister::Spell, SpellIntent::Damage);
        self::assertSame(
            $before->multiplierFor(CombatLever::Power, $scale),
            $sheet->multiplierFor(CombatLever::Power, $scale),
            'Une posture se lit hors du combat qui la porte.'
        );
    }

    /**
     * @param array<string, int> $levers
     */
    private function stance(string $slug, array $levers): StatusEffect
    {
        $effect = new StatusEffect();
        $effect->setSlug($slug);
        $effect->setName($slug);
        $effect->setType(StatusEffect::TYPE_STANCE);
        $effect->setDuration(1);
        $effect->setChance(100);
        $effect->setElement(Element::None);
        $effect->setLevers($levers);
        $effect->setCreatedAt(new \DateTime());
        $effect->setUpdatedAt(new \DateTime());

        $this->persistAndFlush($effect);

        return $effect;
    }

    /**
     * @return list<string>
     */
    private function stanceSlugsOn(\App\Entity\App\Fight $fight, \App\Entity\App\Player $player): array
    {
        $slugs = [];
        foreach ($this->statusEffectManager->getActiveEffects($fight, $player) as $row) {
            if (StanceLaw::isStance($row->getStatusEffect())) {
                $slugs[] = $row->getStatusEffect()->getSlug();
            }
        }

        sort($slugs);

        return $slugs;
    }

    private function stanceRowOn(\App\Entity\App\Fight $fight, \App\Entity\App\Player $player): \App\Entity\App\FightStatusEffect
    {
        foreach ($this->statusEffectManager->getActiveEffects($fight, $player) as $row) {
            if (StanceLaw::isStance($row->getStatusEffect())) {
                return $row;
            }
        }

        self::fail('Aucune posture tenue.');
    }
}
