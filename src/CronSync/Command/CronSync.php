<?php namespace Valorin\CronSync\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Valorin\CronSync\CronCommandInterface;

class CronSync extends Command
{
	const DIVIDER = '# CronSync for ';
	const PAD_SCHEDULE = '20';
	const LOG_PREFIX = '2>&1 | tee ';
	const LOG_POSTFIX = '-cron.log';

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'cronsync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Updates the user\'s crontab with the latest from the artisan commands.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		// Identify the commands configured with a cron line
		$cronCommands = $this->getCronCommands();

		// Build crontab
		$crontab = $this->buildCrontab($cronCommands);

		// Output
		$this->info("Installing new crontab:");
		$this->line($crontab);

		// Dry-run
		if ($this->option('dry-run')) {
			$this->comment("Dry-run: crontab has not been changed.");
			return;
		}

		// Update existing crontab
		if ($this->update($crontab)) {
			$this->info("Success!");
			return;
		}

		$this->error("FAILED!");
		return 1;
	}

	/**
	 * Identifies the artisan commands with cron options
	 *
	 * @return Command[]
	 */
	protected function getCronCommands()
	{
		// Identify the commands configured with a cron line
        $cronCommands = array();
        foreach (Artisan::all() as $command) {
            if ($command instanceOf CronCommandInterface) {
                $cronCommands[] = $command;
            }
        }

        return $cronCommands;
	}

	/**
	 * Builds the crontab lines from the enabled commands
	 *
	 * @param  Commamd[] $commands
	 * @return string
	 */
	protected function buildCrontab($commands)
	{
		// Loop and build lines
		$lines = [self::DIVIDER.base_path()];
		foreach ($commands as $command) {

			// Figure out schedule and parameters
			list($schedule, $parameters) = $this->extractScheduleParameters($command);

			// Define prefix command
			$key  = 'vcronsync::config.command_prefix';

			// Add lines
			$line  = str_pad($schedule, self::PAD_SCHEDULE);
			$line .= '  ';
			$line .= (Config::get($key) ? Config::get($key).' ' : '');
			$line .= 'php '.base_path().'/artisan ';
			$line .= $command->getName().' ';
			$line .= $parameters ? $parameters.' ' : '';

			// Add optional logging component
			if (Config::get('vcronsync::config.log_to_file')) {
				$line .= self::LOG_PREFIX.storage_path().'/logs/'.Str::slug($command->getName()).self::LOG_POSTFIX;
			}

			// Add to array
			$lines[] = $line;
		}

		// Handle custom commands
		foreach (Config::get('vcronsync::config.custom_commands') as $command) {

			list($schedule, $cmd) = $command;

			$line  = str_pad($schedule, self::PAD_SCHEDULE);
			$line .= '  ';
			$line .= $cmd;

			$lines[] = $line;
		}

		return "\n".implode("\n", $lines)."\n";
	}

	/**
	 * Extracts the cron schedule and the parameters from the command
	 *
	 * @param  Command
	 * @return string[]
	 */
	protected function extractScheduleParameters($command)
	{
		$schedule = $command->schedule();

		if (is_array($schedule)) {
			return $schedule;
		}

		return [$schedule, ''];
	}

	/**
	 * Updates the existing crontab with the new lines
	 *
	 * @param  string
	 * @return boolean
	 */
	protected function update($crontab)
	{
		// Load existing
		exec('crontab -l', $existing, $status);

		// If non-zero, we're making a fresh one
		if ($status) {
			$existing = [];
		}

		// Remove lines with
		$existing = $this->removeExisting($existing);

		// Flattern, and append ours
		$new = ltrim(rtrim(implode("\n", $existing))."\n".$crontab);

		// Write to the crontab
		$file = tempnam('/tmp', 'cronsync');
		File::put($file, $new);
		exec("crontab {$file}", $output, $status);
		File::delete($file);

		return ($status == 0);
	}

	/**
	 * Removes existing lines from the crontab
	 *
	 * @param  string[]
	 * @return string[]
	 */
	protected function removeExisting($existing)
	{
		$empty = false;

		foreach ($existing as $key => $value) {

			// Unset if contains existing base_path()
			if (Str::contains($value, base_path())) {
				unset($existing[$key]);
			}

			// Check for duplicates
			if (empty($value)) {
				if ($empty) {
					unset($existing[$key]);
				}
				$empty = true;
			} else {
				$empty = false;
			}
		}

		return $existing;
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('dry-run', null, InputOption::VALUE_NONE, 'Displays the new cron without making any changes.', null),
		);
	}

}
