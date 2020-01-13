<?php namespace Gecche\Cupparis\Menus;

use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{


    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('menus', function ($app) {
            return new MenuManager(function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            });
        });
    }

}