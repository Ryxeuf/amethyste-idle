<?php

namespace App\Tests\Unit\Scheduler;

use PHPUnit\Framework\TestCase;

/**
 * Garde-fou du calendrier des taches recurrentes (tache 134, jalon F).
 *
 * L'audit du jalon F a trouve trois defauts, tous silencieux :
 *
 * 1. `api:mob:move` etait planifiee **toutes les minutes** alors que la
 *    commande avait ete supprimee par ZON-21 ;
 * 2. **sept** commandes manifestement recurrentes — expiration d'encheres et de
 *    commandes (qui **rendent de l'escrow**), loyers, restock PNJ, respawn de
 *    recolte, vagues d'invasion — n'etaient declarees nulle part ;
 * 3. et rien ne consommait le transport du scheduler, ce que le point 1 prouve :
 *    un consommateur aurait leve « Command not defined » toutes les 60 secondes
 *    depuis le pivot.
 *
 * Ce test traite les deux premiers. Le troisieme etait un probleme de
 * deploiement : il est resolu par le service `worker` de `compose.yaml`, et
 * garde par `SchedulerWorkerDeploymentTest`.
 *
 * La regle : toute commande est **planifiee**, ou **declaree manuelle** avec sa
 * raison. Pas de troisieme cas, et surtout pas celui du silence.
 */
class ScheduledCommandTest extends TestCase
{
    /**
     * Commandes qu'on lance a la main, et pourquoi.
     *
     * @var array<string, string>
     */
    private const MANUAL = [
        'app:avatar:inventory' => 'outil d\'inventaire des sprites, lance a la demande',
        'app:economy:rent-backlog-reset' => 'operation d\'exploitation, lancee par l\'entrypoint du worker avant chaque consommation du calendrier (F.0) — pas une tache planifiee',
        'app:balance:report' => 'rapport d\'equilibrage, lu par un humain',
        'app:balance:simulate' => 'simulateur d\'equilibrage (ARC-17c), joue a la demande sur une passe de recalibrage — il ne change aucune donnee, il en rend une table',
        'app:fixtures:load-selective' => 'outil de developpement',
        'app:game:validate' => 'controle de coherence, lance par la CI',
        'app:player:backfill-zone' => 'reparation ponctuelle des joueurs sans zone, a lancer une fois apres deploiement',
        'app:zone:audit' => 'rattachement des entites de monde a leur zone, joue par le deploiement et l\'entrypoint apres les migrations — pas une tache planifiee',
        'app:zone:import' => 'import du graphe de zones, joue par le deploiement apres les migrations et a la main a chaque changement de YAML',
    ];

    private function projectDir(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * Noms des commandes reellement definies dans `src/Command`.
     *
     * @return list<string>
     */
    private function definedCommands(): array
    {
        $names = [];
        foreach (glob($this->projectDir() . '/src/Command/*.php') ?: [] as $file) {
            $source = (string) file_get_contents($file);
            if (preg_match("/name: '([a-z][a-z:0-9-]*)'/", $source, $match)) {
                $names[] = $match[1];
            }
        }
        sort($names);

        return $names;
    }

    /**
     * Noms des commandes planifiees.
     *
     * @return list<string>
     */
    private function scheduledCommands(): array
    {
        $source = (string) file_get_contents($this->projectDir() . '/src/Scheduler/DefaultScheduleProvider.php');
        preg_match_all("/RunCommandMessage\('([a-z][a-z:0-9-]*)'\)/", $source, $matches);

        $names = $matches[1];
        sort($names);

        return array_values(array_unique($names));
    }

    /**
     * Toute commande planifiee existe.
     *
     * C'est le defaut n° 1 : une commande supprimee reste planifiee, et le
     * calendrier echoue en silence a chaque declenchement.
     */
    public function testEveryScheduledCommandExists(): void
    {
        $scheduled = $this->scheduledCommands();
        $this->assertNotEmpty($scheduled, 'Le test ne verifie rien si l\'extraction echoue.');

        $this->assertSame(
            [],
            array_values(array_diff($scheduled, $this->definedCommands())),
            'Ces commandes sont planifiees mais n\'existent plus : le calendrier echouera a chaque declenchement.',
        );
    }

    /**
     * Toute commande est classee : planifiee, ou manuelle avec sa raison.
     *
     * C'est le defaut n° 2. Une commande recurrente qu'on oublie de declarer ne
     * casse rien — elle ne tourne simplement jamais, et ce qu'elle devait faire
     * n'arrive pas. Pour `app:auction:expire` et `app:craft-order:expire`, ce
     * « rien » vaut de l'escrow immobilise indefiniment.
     */
    public function testEveryCommandIsEitherScheduledOrDeclaredManual(): void
    {
        $defined = $this->definedCommands();
        $this->assertNotEmpty($defined, 'Le test ne verifie rien si l\'extraction echoue.');

        $classified = array_merge($this->scheduledCommands(), array_keys(self::MANUAL));

        $this->assertSame(
            [],
            array_values(array_diff($defined, $classified)),
            'Ces commandes ne sont ni planifiees ni declarees manuelles. Si elles sont recurrentes, '
            . 'les ajouter a DefaultScheduleProvider ; sinon, les justifier dans self::MANUAL.',
        );
    }

    /**
     * La liste des manuelles ne cite que des commandes existantes.
     *
     * Sans cela, une commande supprimee laisse une ligne morte qui dispenserait
     * de classement une future commande reprenant le meme nom.
     */
    public function testNoStaleManualEntry(): void
    {
        $this->assertSame(
            [],
            array_values(array_diff(array_keys(self::MANUAL), $this->definedCommands())),
            'Ces commandes declarees manuelles n\'existent plus.',
        );
    }

    /**
     * Une commande n'est pas a la fois planifiee et declaree manuelle.
     *
     * Les deux listes se contrediraient, et `testEveryCommandIsEitherScheduled…`
     * resterait vert sur la contradiction.
     */
    public function testNoCommandIsBothScheduledAndManual(): void
    {
        $this->assertSame(
            [],
            array_values(array_intersect($this->scheduledCommands(), array_keys(self::MANUAL))),
            'Ces commandes sont a la fois planifiees et declarees manuelles.',
        );
    }
}
