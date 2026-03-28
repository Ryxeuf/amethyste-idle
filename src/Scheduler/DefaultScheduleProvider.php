<?php

namespace App\Scheduler;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * Schedule provider par défaut.
 * Remplace le cron-bundle pour les tâches planifiées (ex: move_mob).
 */
#[AsSchedule]
class DefaultScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->with(
                // MobMove : déplace les mobs sur la carte (anciennement api:mob:move via cron)
                RecurringMessage::cron('* * * * *', new RunCommandMessage('api:mob:move')),
                // GameEvent : active/complète les événements planifiés (toutes les 60s)
                RecurringMessage::cron('* * * * *', new RunCommandMessage('app:game-event:execute')),
                // Weather : met à jour la météo de chaque carte (toutes les 15 min)
                RecurringMessage::cron('*/15 * * * *', new RunCommandMessage('app:weather:tick')),
                // PnjRoutine : déplace les PNJ selon leurs horaires de routine (toutes les 5 min)
                RecurringMessage::cron('*/5 * * * *', new RunCommandMessage('app:pnj:routine')),
                // DailyQuest : rotation des quêtes quotidiennes (chaque jour à 00h01)
                RecurringMessage::cron('1 0 * * *', new RunCommandMessage('app:daily-quest:rotate')),
                // RandomEvent : tente de générer un événement aléatoire (toutes les 30 min)
                RecurringMessage::cron('*/30 * * * *', new RunCommandMessage('app:events:random')),
                // SeasonTick : gère le cycle de vie des saisons d'influence (1x/jour à 00h05)
                RecurringMessage::cron('5 0 * * *', new RunCommandMessage('app:season:tick')),
            );
    }
}
