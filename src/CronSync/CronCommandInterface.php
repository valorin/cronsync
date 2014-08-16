<?php namespace Valorin\CronSync;

interface CronCommandInterface
{
    /**
     * Returns the cron schedule to use
     *
     * @return string|string[]
     */
    public function schedule();
}
