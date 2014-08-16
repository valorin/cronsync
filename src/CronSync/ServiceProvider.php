<?php namespace Valorin\CronSync;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Valorin\CronSync\Command\CronSync;

class ServiceProvider extends BaseServiceProvider
{
	/**
	* Indicates if loading of the provider is deferred.
	*
	* @var bool
	*/
	protected $defer = false;

	/**
	* Bootstrap the application events.
	*
	* @return void
	*/
	public function boot()
	{
		$this->package('valorin/cronsync', 'vcronsync', __DIR__.'/..');
	}

	/**
	* Register the service provider.
	*
	* @return void
	*/
	public function register()
	{
		$this->registerCronSyncCommand();
	}

	/**
	* Registers the base cronsync command
	*
	*/
	public function registerCronSyncCommand()
	{
		$this->app['command.cronsync'] = $this->app->share(function () {
			return new CronSync();
		});
		$this->commands('command.cronsync');
	}

	/**
	* Get the services provided by the provider.
	*
	* @return array
	*/
	public function provides()
	{
		return array();
	}

}
