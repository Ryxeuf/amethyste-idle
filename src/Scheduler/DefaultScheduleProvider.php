<?php

namespace App\Scheduler;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * Taches recurrentes du jeu.
 *
 * ## Qui consomme ce calendrier
 *
 * `symfony/scheduler` publie ses messages sur un transport `scheduler_default`,
 * qu'il faut consommer. C'est le role du service Docker **`worker`**
 * (`compose.yaml`), lance par `frankenphp/scheduler-entrypoint.sh` :
 * `messenger:consume scheduler_default`.
 *
 * Ce fichier a ete **declaratif pendant des mois**, faute de ce consommateur —
 * et la preuve etait dans ce fichier meme : `api:mob:move` y etait planifiee
 * toutes les minutes alors que **la commande avait ete supprimee par ZON-21**.
 * Un consommateur aurait leve « Command not defined » toutes les 60 secondes
 * depuis le pivot. Personne ne l'a vu. C'est ce que garde desormais
 * `tests/Unit/Scheduler/ScheduledCommandTest.php`.
 *
 * ## Trois contraintes a connaitre avant de toucher a ce calendrier
 *
 * 1. **Une seule replique du worker.** Le calendrier n'a pas de verrou
 *    (`Schedule::lock()` n'est pas appele, `symfony/lock` n'est pas installe —
 *    jalon F.1). A deux repliques, chaque tache se declenche deux fois : loyers
 *    preleves en double, recompenses de saison versees en double.
 *    `compose.yaml` fige `deploy.replicas: 1`, et un test le verifie.
 * 2. **Rien n'est rejoue.** Le calendrier est sans etat (`stateful()` n'est pas
 *    appele) : un declenchement tombe pendant un redemarrage du worker est
 *    perdu, jamais rattrape. C'est voulu — un loyer saute vaut mieux qu'un
 *    loyer preleve deux fois.
 * 3. **Une commande ajoutee ici tourne vraiment.** Ce n'est plus une
 *    declaration d'intention : verifier son cout et son idempotence.
 *
 * Voir `docs/LOAD_TESTING_BOTTLENECKS.md` § jalon F.
 */
#[AsSchedule]
class DefaultScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->with(
                // --- Monde vivant -------------------------------------------
                // GameEvent : active/complète les événements planifiés
                RecurringMessage::cron('* * * * *', new RunCommandMessage('app:game-event:execute')),
                // Invasion : avance les vagues actives (la commande précise
                // elle-même « à exécuter toutes les minutes »)
                RecurringMessage::cron('* * * * *', new RunCommandMessage('app:invasion:tick')),
                // Weather : met à jour la météo de chaque carte
                RecurringMessage::cron('*/15 * * * *', new RunCommandMessage('app:weather:tick')),
                // PnjRoutine : déplace les PNJ selon leurs horaires
                RecurringMessage::cron('*/5 * * * *', new RunCommandMessage('app:pnj:routine')),
                // RandomEvent : tente de générer un événement aléatoire
                RecurringMessage::cron('*/30 * * * *', new RunCommandMessage('app:events:random')),
                // HarvestRespawn : libère les spots dont le cooldown est écoulé.
                // À la minute : c'est la granularité des cooldowns de récolte.
                RecurringMessage::cron('* * * * *', new RunCommandMessage('app:harvest:respawn')),

                // --- Economie -----------------------------------------------
                // AuctionExpire et CraftOrderExpire **rendent de l'escrow**. Le
                // pas de 5 minutes borne le temps pendant lequel des Gils et des
                // objets restent immobilises apres l'echeance.
                RecurringMessage::cron('*/5 * * * *', new RunCommandMessage('app:auction:expire')),
                RecurringMessage::cron('*/5 * * * *', new RunCommandMessage('app:craft-order:expire')),
                // ShopRestock : reapprovisionne les boutiques PNJ — c'est le
                // plancher T1 anti cold-start d'ECO-02.
                RecurringMessage::cron('0 * * * *', new RunCommandMessage('app:shop:restock')),

                // --- Compte -------------------------------------------------
                // Rappels de verification d'e-mail (ONB-04) : J+1, J+3, puis
                // silence. Une fois par jour suffit — le compteur sur le
                // compte rend la commande idempotente a l'echelle du jour.
                RecurringMessage::cron('30 9 * * *', new RunCommandMessage('app:verification:remind')),

                // --- Cloture hebdomadaire -----------------------------------
                // WeeklyChallenge : clôt la semaine de défis écoulée et ouvre
                // la suivante (RET-01). **Avant** la chaîne de minuit : la
                // semaine s'ouvre d'abord, le quotidien s'enchaîne dessus.
                RecurringMessage::cron('0 0 * * 1', new RunCommandMessage('app:weekly-challenge:rotate')),
                // WeeklyCommission : le rendez-vous **personnel** (RET-02).
                // Après le défi de guilde : les deux s'ouvrent le même lundi,
                // le collectif d'abord.
                RecurringMessage::cron('2 0 * * 1', new RunCommandMessage('app:weekly-commission:rotate')),
                // SettlementWork : ce que chaque foyer attend cette semaine
                // (RET-05). En dernier des trois : il lit le **type** du foyer,
                // que le tick de saison n'a pas encore touche a cette heure-ci.
                RecurringMessage::cron('4 0 * * 1', new RunCommandMessage('app:settlement-work:rotate')),
                // WeeklyOutcrop : le filon qui rend un cran plus haut cette
                // semaine (RET-06). Personne n'en est informe — c'est le point.
                RecurringMessage::cron('6 0 * * 1', new RunCommandMessage('app:weekly-outcrop:rotate')),
                // FoundryContract : l'affiche de la Fonderie (FAC-05). Apres
                // le marche de la semaine ecoulee : le garde-fou de prix lit
                // la mediane HV au tirage.
                RecurringMessage::cron('8 0 * * 1', new RunCommandMessage('app:weekly-foundry-contract:rotate')),

                // --- Cloture quotidienne ------------------------------------
                // DailyQuest : rotation des quêtes quotidiennes
                RecurringMessage::cron('1 0 * * *', new RunCommandMessage('app:daily-quest:rotate')),
                // SeasonTick : cycle de vie des saisons d'influence
                RecurringMessage::cron('5 0 * * *', new RunCommandMessage('app:season:tick')),
                // Foyers : décroissance des indices, rang et type (FOY-03).
                // **Après** le tick de saison : la marée se clôt d'abord, les
                // foyers s'amincissent ensuite. L'inverse ferait redescendre un
                // foyer juste avant que la saison ne compte ce qu'il valait.
                RecurringMessage::cron('7 0 * * *', new RunCommandMessage('app:settlement:tick')),

                // Repertoire : le monde retrouve ce que ses lectures lui ont
                // merite (REP-03). **Apres** le tick des foyers, parce que deux
                // des trois conditions rares se lisent sur eux — une Metropole
                // ou une doctrine adoptee dans la journee doit compter le jour
                // meme, pas le lendemain.
                RecurringMessage::cron('9 0 * * *', new RunCommandMessage('app:repertoire:unlock')),

                // FOY-20 : les cheminees. **Apres** le tick des foyers, comme
                // le Repertoire : un grain depose avant la decroissance du jour
                // serait mange par elle le matin meme, et la population
                // residente ne soutiendrait rien.
                RecurringMessage::cron('11 0 * * *', new RunCommandMessage('app:house:residence-grain')),
                // GilsSupply : relève la masse monétaire (ECO-15).
                // Après le tick de saison : les récompenses de clôture doivent
                // être versées avant qu'on compte, sinon la masse du jour saute
                // d'un cran une fois par saison sans qu'aucun robinet ait coulé.
                RecurringMessage::cron('10 0 * * *', new RunCommandMessage('app:economy:snapshot')),
                // Loyers : demeure puis échoppe. Après le relevé de masse, pour
                // que deux journées consécutives se comparent au même point du
                // cycle plutôt qu'à cheval sur un prélèvement.
                RecurringMessage::cron('15 0 * * *', new RunCommandMessage('app:house:rent')),
                RecurringMessage::cron('20 0 * * *', new RunCommandMessage('app:shop:rent')),
            );
    }
}
