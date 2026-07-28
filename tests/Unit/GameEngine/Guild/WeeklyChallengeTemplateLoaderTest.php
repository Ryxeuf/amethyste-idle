<?php

namespace App\Tests\Unit\GameEngine\Guild;

use App\Enum\InfluenceActivityType;
use App\GameEngine\Guild\WeeklyChallengeTemplateException;
use App\GameEngine\Guild\WeeklyChallengeTemplateLoader;
use PHPUnit\Framework\TestCase;

/**
 * RET-01 — le pool declaratif des defis hebdomadaires.
 *
 * Un gabarit mal ecrit doit echouer a la lecture du fichier, pas six jours plus
 * tard sur un ecran de guilde vide.
 */
class WeeklyChallengeTemplateLoaderTest extends TestCase
{
    private WeeklyChallengeTemplateLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new WeeklyChallengeTemplateLoader(\dirname(__DIR__, 4));
    }

    public function testRealPoolLoadsAndIsUsable(): void
    {
        $pool = $this->loader->load();

        self::assertGreaterThanOrEqual(1, $pool['per_week']);
        self::assertGreaterThanOrEqual($pool['per_week'], \count($pool['challenges']));

        foreach ($pool['challenges'] as $template) {
            self::assertNotSame('', $template->title);
            self::assertNotSame('', $template->description);
            self::assertGreaterThan(0, $template->target);
            self::assertGreaterThan(0, $template->bonusPoints);
        }
    }

    /**
     * Le pool livre doit couvrir assez d'activites pour qu'une semaine ne serve
     * pas trois fois la meme : c'est la condition de lisibilite de l'ecran.
     */
    public function testRealPoolCoversAtLeastAsManyActivitiesAsWeeklySlots(): void
    {
        $pool = $this->loader->load();

        $activities = [];
        foreach ($pool['challenges'] as $template) {
            $activities[$template->activity->value] = true;
        }

        self::assertGreaterThanOrEqual($pool['per_week'], \count($activities));
    }

    public function testNormalizeParsesAMinimalTemplate(): void
    {
        $pool = $this->loader->normalize([
            'per_week' => 1,
            'challenges' => [
                [
                    'slug' => 'chasse',
                    'activity' => 'mob_kill',
                    'title' => 'Chasse',
                    'description' => 'Tuez des choses.',
                    'target' => 12,
                    'bonus_points' => 30,
                ],
            ],
        ]);

        self::assertSame(1, $pool['per_week']);
        self::assertCount(1, $pool['challenges']);
        self::assertSame(InfluenceActivityType::MobKill, $pool['challenges'][0]->activity);
        self::assertNull($pool['challenges'][0]->titleEn);
    }

    public function testEmptyPoolIsRejected(): void
    {
        $this->expectException(WeeklyChallengeTemplateException::class);

        $this->loader->normalize(['challenges' => []]);
    }

    public function testUnknownActivityIsRejected(): void
    {
        $this->expectException(WeeklyChallengeTemplateException::class);

        $this->loader->normalize(['challenges' => [$this->template(['activity' => 'dancing'])]]);
    }

    /**
     * `challenge` est le canal de versement des bonus : un defi qui s'en
     * reclamerait se nourrirait de ses propres recompenses.
     */
    public function testReservedChallengeActivityIsRejected(): void
    {
        $this->expectException(WeeklyChallengeTemplateException::class);

        $this->loader->normalize(['challenges' => [$this->template(['activity' => 'challenge'])]]);
    }

    public function testDuplicateSlugIsRejected(): void
    {
        $this->expectException(WeeklyChallengeTemplateException::class);

        $this->loader->normalize(['challenges' => [$this->template(), $this->template()]]);
    }

    public function testNonPositiveTargetIsRejected(): void
    {
        $this->expectException(WeeklyChallengeTemplateException::class);

        $this->loader->normalize(['challenges' => [$this->template(['target' => 0])]]);
    }

    public function testNonPositiveBonusIsRejected(): void
    {
        $this->expectException(WeeklyChallengeTemplateException::class);

        $this->loader->normalize(['challenges' => [$this->template(['bonus_points' => -5])]]);
    }

    public function testMissingFileIsRejected(): void
    {
        $this->expectException(WeeklyChallengeTemplateException::class);

        $this->loader->load('/nowhere/weekly_challenges.yaml');
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function template(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'chasse',
            'activity' => 'mob_kill',
            'title' => 'Chasse',
            'description' => 'Tuez des choses.',
            'target' => 12,
            'bonus_points' => 30,
        ], $overrides);
    }
}
