<?php

namespace App\Tests\Unit\GameEngine\Retention;

use App\GameEngine\Retention\WeekKey;
use PHPUnit\Framework\TestCase;

/**
 * Le contrat transverse du plan de retention (RET-07).
 *
 * Les briques hebdomadaires se testent chacune de leur cote ; ce fichier teste
 * ce qu'aucune ne peut tester seule — ce qui n'est vrai que **de l'ensemble**.
 * Deux proprietes, tirees directement de la table des risques du plan :
 *
 * 1. **La semaine est une.** « Cinq mecaniques hebdomadaires = cinq horloges
 *    qui derivent » est le risque nomme ; la parade est un point de calcul
 *    unique et une bascule unique, le lundi 00h00.
 * 2. **Aucune brique ne penalise une semaine d'absence.** C'est l'interdit de
 *    RET-04, mais il vaut pour toutes : une brique hebdomadaire qui punirait
 *    l'absence transformerait le rendez-vous en corvee.
 *
 * Ces controles sont **lexicaux et structurels**, pas comportementaux : ils
 * verrouillent la forme du code pour qu'une regression se voie a l'ecriture,
 * la ou un test de comportement ne verrait que la brique qu'il connait.
 */
class RetentionPlanContractTest extends TestCase
{
    /**
     * Le seul fichier autorise a connaitre le format de semaine ISO.
     */
    private const WEEK_KEY_HOME = 'src/GameEngine/Retention/WeekKey.php';

    /**
     * Vocabulaire de la serie continue.
     *
     * Interdit dans le moteur, pas dans les commentaires : les docblocs de ce
     * plan **doivent** pouvoir nommer ce qu'ils refusent. Le scan ne lit donc
     * que le code, commentaires retires.
     *
     * @var list<string>
     */
    private const STREAK_VOCABULARY = ['streak', 'consecutiveweeks', 'weekstreak', 'currentstreak'];

    private function root(): string
    {
        return \dirname(__DIR__, 4);
    }

    /**
     * @return list<string> chemins relatifs des sources du moteur
     */
    private function engineSources(): array
    {
        $files = [];
        $directories = [
            $this->root() . '/src/GameEngine/Retention',
            $this->root() . '/src/GameEngine/Guild',
            $this->root() . '/src/GameEngine/Settlement',
            $this->root() . '/src/GameEngine/Economy',
            // RET-09 : le hub est devenu une surface hebdomadaire a part
            // entiere (le bloc « La semaine », puis le recap du lundi). Les
            // deux interdits de ce plan y valent donc autant qu'ailleurs — et
            // c'est precisement l'ecran ou une serie continue se
            // reintroduirait « parce que c'est standard ».
            $this->root() . '/src/GameEngine/Player',
            $this->root() . '/src/Entity/App',
            $this->root() . '/src/Command',
        ];

        foreach ($directories as $directory) {
            foreach ((array) glob($directory . '/*.php') as $file) {
                $files[] = (string) $file;
            }
        }

        return $files;
    }

    // =====================================================================
    // 1. La semaine est une
    // =====================================================================

    /**
     * Le format de semaine ISO n'est ecrit qu'a un endroit.
     *
     * Il l'etait a **deux** avant ce jalon : `WeeklyChallengeRotator` recopiait
     * la formule que les quatre autres briques partageaient. Les deux
     * s'accordaient — c'est justement ce qui rend ce genre de duplication
     * dangereux : elle ne se signale que le jour ou elle a deja diverge.
     */
    public function testTheIsoWeekFormatLivesInExactlyOnePlace(): void
    {
        $offenders = [];

        foreach ($this->engineSources() as $file) {
            $relative = ltrim(str_replace($this->root(), '', $file), '/');
            if ($relative === self::WEEK_KEY_HOME) {
                continue;
            }

            // Commentaires retires : un docbloc a parfaitement le droit de
            // citer le format qu'il ne calcule pas — c'est meme comme cela
            // qu'une entite explique ce que porte sa colonne.
            $content = $this->stripComments((string) file_get_contents($file));
            if (str_contains($content, 'o-\\WW') || str_contains($content, 'monday this week')) {
                $offenders[] = $relative;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Ces fichiers recalculent la semaine ISO au lieu de passer par WeekKey. Deux calculs de semaine sont '
            . 'deux horloges, et deux horloges finissent par ne plus dire la meme heure.',
        );
    }

    /**
     * Toutes les rotations hebdomadaires basculent le **meme** lundi, a la
     * meme heure, et a des minutes distinctes.
     *
     * Les minutes distinctes ne sont pas de la coquetterie : l'ordre porte du
     * sens (le defi de guilde ouvre la semaine, le chantier de foyer lit un
     * type que le tick de saison n'a pas encore touche), et deux commandes a la
     * meme minute rendraient cet ordre dependant de l'ordonnanceur.
     */
    public function testEveryWeeklyRotationFiresOnTheSameMonday(): void
    {
        $schedule = (string) file_get_contents($this->root() . '/src/Scheduler/DefaultScheduleProvider.php');

        preg_match_all(
            "/RecurringMessage::cron\('([^']+)', new RunCommandMessage\('([^']+)'\)\)/",
            $schedule,
            $matches,
            \PREG_SET_ORDER,
        );

        $this->assertNotEmpty($matches, 'Le calendrier n\'a pas pu etre lu : le test ne verifie rien.');

        $minutes = [];
        $offenders = [];

        foreach ($matches as [, $cron, $command]) {
            if (!str_contains($command, 'weekly') && !str_contains($command, 'settlement-work')) {
                continue;
            }

            $fields = explode(' ', $cron);
            // minute heure jour mois jour-de-semaine
            if (\count($fields) !== 5 || $fields[1] !== '0' || $fields[4] !== '1') {
                $offenders[] = $command . ' => ' . $cron;
                continue;
            }

            $minutes[$fields[0]][] = $command;
        }

        $this->assertNotEmpty($minutes, 'Aucune rotation hebdomadaire trouvee : le test ne verifie rien.');
        $this->assertSame([], $offenders, 'Ces rotations hebdomadaires ne tombent pas le lundi a 00h.');

        $collisions = array_keys(array_filter($minutes, static fn (array $commands): bool => \count($commands) > 1));
        $this->assertSame(
            [],
            $collisions,
            'Deux rotations partagent la meme minute : leur ordre depend alors de l\'ordonnanceur, alors qu\'il '
            . 'porte du sens.',
        );
    }

    /**
     * La clef s'ancre sur le lundi, pas sur le jour de l'appel.
     *
     * C'est ce qui fait qu'une brique consultee un dimanche soir et une autre
     * consultee le lundi matin parlent bien de deux semaines differentes — et
     * que deux briques consultees le meme jeudi parlent de la meme.
     */
    public function testTheWeekKeyIsAnchoredOnMonday(): void
    {
        $monday = new \DateTimeImmutable('2026-07-27 00:00:00');
        $sunday = new \DateTimeImmutable('2026-08-02 23:59:59');
        $nextMonday = new \DateTimeImmutable('2026-08-03 00:00:01');

        self::assertSame('2026-W31', WeekKey::of($monday));
        self::assertSame(WeekKey::of($monday), WeekKey::of($sunday));
        self::assertNotSame(WeekKey::of($sunday), WeekKey::of($nextMonday));
        self::assertSame($monday->format('c'), WeekKey::mondayOf($sunday)->format('c'));
    }

    /**
     * Une clef deja ecrite se relit en dates, et sans seconde formule.
     *
     * Le recap du lundi (RET-09) part d'une clef **stockee** — la semaine de
     * derniere visite — et a besoin de ses bornes. Le chemin inverse vit dans
     * `WeekKey` pour la meme raison que l'aller : le premier appelant qui
     * l'aurait recalcule sur place aurait recree la duplication que ce fichier
     * supprime.
     */
    public function testAWrittenWeekKeyReadsBackToItsOwnMonday(): void
    {
        $monday = new \DateTimeImmutable('2026-07-27 00:00:00');

        self::assertSame(
            $monday->format('c'),
            WeekKey::mondayOfKey(WeekKey::of($monday))?->format('c'),
        );
        self::assertSame(
            $monday->format('c'),
            WeekKey::mondayOfKey(WeekKey::of(new \DateTimeImmutable('2026-08-02 23:59:59')))?->format('c'),
        );
        self::assertNull(WeekKey::mondayOfKey('pas-une-semaine'));
    }

    // =====================================================================
    // 2. Aucune brique ne penalise une semaine d'absence
    // =====================================================================

    /**
     * Aucun moteur ne connait le vocabulaire de la serie continue.
     *
     * Le plan interdit la serie « reintroduite parce que c'est standard ».
     * RET-04 rend l'interdit structurel pour l'assiduite (une ligne par semaine
     * ISO, donc aucune memoire des semaines ratees) ; ce test l'etend a tout ce
     * qui touche a l'hebdomadaire, y compris aux briques qui n'existent pas
     * encore.
     */
    public function testNoWeeklyBrickSpeaksTheLanguageOfStreaks(): void
    {
        $offenders = [];

        foreach ($this->engineSources() as $file) {
            $relative = ltrim(str_replace($this->root(), '', $file), '/');
            $code = $this->stripComments((string) file_get_contents($file));
            $haystack = strtolower($code);

            foreach (self::STREAK_VOCABULARY as $word) {
                if (str_contains($haystack, $word)) {
                    $offenders[] = $relative . ' (' . $word . ')';
                    break;
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Une serie continue penalise l\'absence : une semaine manquee y coute ce qui a ete accumule. '
            . 'C\'est l\'inverse du contrat du genre, et le plan l\'interdit explicitement.',
        );
    }

    /**
     * L'assiduite ne stocke aucune trace des semaines precedentes.
     *
     * L'invariant est verrouille par la **forme** : les colonnes de
     * `player_weekly_attendance` ne parlent que de la semaine courante. Une
     * colonne qui porterait un report — meme nommee innocemment — rendrait la
     * serie a nouveau ecrivable.
     */
    public function testTheAttendanceTableOnlyKnowsTheCurrentWeek(): void
    {
        $entity = (string) file_get_contents($this->root() . '/src/Entity/App/PlayerWeeklyAttendance.php');

        preg_match_all("/#\[ORM\\\\Column\(name: '([a-z_]+)'/", $entity, $matches);

        $this->assertSame(
            ['id', 'week_key', 'active_days', 'last_active_day', 'granted_tier_days'],
            $matches[1],
            'Les colonnes de l\'assiduite ont change. Chacune ne doit parler que de la semaine courante : une '
            . 'colonne de report rouvrirait la porte a la serie continue.',
        );
    }

    /**
     * Retire commentaires et docblocs : ce plan **doit** pouvoir nommer par
     * ecrit ce qu'il refuse d'implementer.
     */
    private function stripComments(string $source): string
    {
        $code = '';
        foreach (token_get_all($source) as $token) {
            if (\is_array($token) && \in_array($token[0], [\T_COMMENT, \T_DOC_COMMENT], true)) {
                continue;
            }
            $code .= \is_array($token) ? $token[1] : $token;
        }

        return $code;
    }
}
