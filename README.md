CronSync
========

Inspired by [Dispatcher](https://github.com/indatus/dispatcher), CronSync is a way to
define your artisan command cron schedule within your code (and therefore in version control), by updating
the crontab as part of your deploy process, without needing to run a separate process
every minute to check for any cron tasks.

Running `php artisan cronsync` after pushing out new code will ensure the current user's
crontab is configured to match the cron definitions automatically.

Installation
------------

Add the package to your application with composer:

```
composer require "valorin/cronsync:~1.0"
```

Add the Service Provider to the `providers` list in `./app/config/app.php`:

```
'providers' => array(
    ...
    'Valorin\CronSync\ServiceProvider',
),
```

Usage
-----

To define a cron schedule in an artisan command, first add the `CronCommandInterface` onto the command:

```
use Valorin\CronSync\CronCommandInterface;
...
class ClassName extends Command implements CronCommandInterface
```

Then implement the `schedule()` command in the class to return the cron schedule expression:

```
/**
 * Returns the cron schedule to use
 *
 * @return string|string[]
 */
public function schedule()
{
    // Every hour at 42 minutes past the hour
    return '42 * * * *';
}
```

To support parameters on the artisan command, return an array:

```
    return ['*/5 * * * *', '--option --option2="value" argument1'];
```

Then run the `dry-run` command to check that the cron will be configured correctly:

```
php artisan cronsync --dry-run
```

If it all looks good, you can drop the `--dry-run` off the end:

```
php artisan cronsync
```

**IMPORTANT:** CronSync uses `base_path()` to identify existing cron entries that need to
be removed before it adds it's own. If you have custom cron entries which are **not**
linked to artisan commands but do include the `base_path()`, they will be removed. Either
implement them via artisan commands, or add them into the `custom_commands` config option.

Configuration
-------------

To change the default configuration, run:

```
./artisan config:publish "valorin/cronsync"
```

And then edit the configuration file at:

```
./app/config/packages/valorin/cronsync/config.php
```
