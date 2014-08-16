<?php

return array(

    /**
     * Command Prefix
     *
     * Optional command to prefix the cron line with. Useful if you use tools such as run-one to ensure
     * long running cron tasks aren't duplicated.
     */
    'command_prefix' => '',

    /**
     * Log cron output to file
     *
     * Specifies if the generated cron command should log to a file in ./app/storage/logs or not.
     */
    'log_to_file' => true,

    /**
     * Custom Commands
     *
     * Sometimes you need to run custom cron commands which are not linked through artisan commands.
     * Since CronSync removes any lines containing the base_path(), you will need to define them below.
     *
     * IMPORTANT: This command needs base_path() in it, or it will be duplicated on repeated cronsync runs.
     *
     * Each entry is in the form: ['schedule', 'command'],
     */
    'custom_commands' => array(

        //['* * * * *', '/var/www/html/command'],

    ),
);
