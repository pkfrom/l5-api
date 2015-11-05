<?php
/**
 * Created by PhpStorm.
 * User: pkfrom
 * Date: 5/11/2558
 * Time: 8:28
 */

namespace Fromz\Api;

use Fromz\Api\Generator\ApiMakeCommand;

class ApiServiceProvider extends \Illuminate\Support\ServiceProvider
{

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/config.php', 'l5-api'
        );

        $this->app->singleton('command.api.make', function ($app) {
            return new ApiMakeCommand($app['files']);
        });

        $this->commands('command.api.make');
    }

    public function boot()
    {
		$this->publishConfig();
		$this->publishApi();

    }

    private function publishConfig()
    {
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('l5-api.php'),
        ], 'config');

        require app_path(config('l5-api.routes_file'));
    }

	private function publishApi()
    {
		$this->publishes([
			__DIR__.'/../templates/Api/' => base_path('app/Api')], 'api');
    }
}