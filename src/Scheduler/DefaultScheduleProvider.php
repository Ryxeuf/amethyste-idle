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
 * > **Attention (audit jalon F, tache 134)** : ce calendrier n'est **consomme
 * > par aucun processus**. `symfony/scheduler` publie ses messages sur un
 * > transport `scheduler_default` qu'il faut consommer avec
 * > `messenger:consume scheduler_default` — et aucun worker de ce type n'existe
 * > dans `compose.yaml`, `compose.prod.yaml`, le `Dockerfile` ni l'entrypoint.
 * >
 * > La preuve est dans ce fichier meme : `api:mob:move` y etait planifiee toutes
 * > les minutes alors que **la commande a ete supprimee par ZON-21**. Un
 * > consommateur aurait leve « Command not defined » toutes les 60 secondes
 * > depuis le pivot. Personne ne l'a vu.
 * >
 * > Tant que ce worker n'existe pas, tout ce fichier est declaratif. Voir
 * > `docs/LOAD_TESTING_BOTTLENECKS.md` § jalon F.
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

                // --- Cloture hebdomadaire -----------------------------------
                // WeeklyChallenge : clôt la semaine de défis écoulée et ouvre
                // la suivante (RET-01). **Avant** la chaîne de minuit : la
                // semaine s'ouvre d'abord, le quotidien s'enchaîne dessus.
                RecurringMessage::cron('0 0 * * 1', new RunCommandMessage('app:weekly-challenge:rotate')),

                // --- Cloture quotidienne ------------------------------------
                // DailyQuest : rotation des quêtes quotidiennes
                RecurringMessage::cron('1 0 * * *', new RunCommandMessage('app:daily-quest:rotate')),
                // SeasonTick : cycle de vie des saisons d'influence
                RecurringMessage::cron('5 0 * * *', new RunCommandMessage('app:season:tick')),
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
