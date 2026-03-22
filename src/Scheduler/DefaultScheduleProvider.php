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
            );
    }
}
