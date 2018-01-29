<?php
/**
 * Service provider
 *
 * PHP version 7
 *
 * @category  Mileage
 * @package   App\Services\Mileage
 */

namespace App\Providers;

use App\Services\Mileage\MileageManager;
use App\Services\Mileage\Repositories\CacheDecorator;
use App\Services\Mileage\Repositories\EloquentRepository;
use App\Services\Support\LaravelCache;
use Illuminate\Support\ServiceProvider;

/**
 * Mileage Service Provider
 *
 * @category Mileage
 * @package  App\Services\Mileage
 */
class MileageServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Service Provider Boot
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
        $this->app->bind([MileageManager::class=> 'point.mileage'], function ($app) {
            $repo = new EloquentRepository();

            if ($app['config']['app.debug'] !== true) {
                $repo = new CacheDecorator($repo, new LaravelCache($app['cache.store']));
            }

            return new MileageManager(
                $repo
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['point.mileage'];
    }
}
