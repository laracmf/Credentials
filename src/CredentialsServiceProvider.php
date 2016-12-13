<?php

/*
 * This file is part of Laravel Credentials.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GrahamCampbell\Credentials;

use GrahamCampbell\BootstrapCMS\Models\User;
use GrahamCampbell\Credentials\Services\UsersService;
use GrahamCampbell\Credentials\Http\Controllers\ActivationController;
use GrahamCampbell\Credentials\Http\Controllers\LoginController;
use GrahamCampbell\Credentials\Http\Controllers\RegistrationController;
use GrahamCampbell\Credentials\Http\Controllers\ResetController;
use GrahamCampbell\Credentials\Repositories\UserRepository;
use Illuminate\Contracts\View\Factory as View;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use McCool\LaravelAutoPresenter\PresenterDecorator;

/**
 * This is the credentials service provider class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class CredentialsServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupPackage();

        $this->setupBlade($this->app->view);

        $this->setupRoutes($this->app->router);
    }

    /**
     * Setup the package.
     *
     * @return void
     */
    protected function setupPackage()
    {
        $configuration = realpath(__DIR__.'/../config/credentials.php');
        $sentinel = realpath(__DIR__ . '/../config/sentinel.php');
        $migrations = realpath(__DIR__.'/../migrations');

        $this->publishes([
            $configuration => config_path('credentials.php'),
            $migrations    => base_path('database/migrations'),
            $sentinel      => config_path('sentinel.php')
        ]);

        $this->mergeConfigFrom($configuration, 'credentials');
        $this->mergeConfigFrom($sentinel, 'sentinel');

        $this->loadViewsFrom(realpath(__DIR__.'/../views'), 'credentials');
    }

    /**
     * Setup the blade compiler class.
     *
     * @param \Illuminate\Contracts\View\Factory $view
     *
     * @return void
     */
    protected function setupBlade(View $view)
    {
        $blade = $view->getEngineResolver()->resolve('blade')->getCompiler();

        $blade->directive('auth', function ($expression) {
            return "<?php if (\GrahamCampbell\Credentials\Facades\Credentials::check() && \GrahamCampbell\Credentials\Facades\Credentials::hasAccess{$expression}): ?>";
        });

        $blade->directive('endauth', function () {
            return '<?php endif; ?>';
        });
    }

    /**
     * Setup the routes.
     *
     * @param \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function setupRoutes(Router $router)
    {
        $router->group(['namespace' => 'GrahamCampbell\Credentials\Http\Controllers'], function (Router $router) {
            require __DIR__.'/Http/routes.php';
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerUserRepository();
        $this->registerCredentials();

        $this->registerLoginController();
        $this->registerRegistrationController();
        $this->registerResetController();
        $this->registerActivationController();
        $this->registerUsersService();
    }

    /**
     * Register user service.
     *
     * @return void
     */
    protected function registerUsersService()
    {
        $this->app->bind(UsersService::class, function () {
            return new UsersService();
        });
    }

    /**
     * Register the user repository class.
     *
     * @return void
     */
    protected function registerUserRepository()
    {
        $this->app->singleton('userrepository', function ($app) {
            $validator = $app['validator'];

            return new UserRepository(new User(), $validator);
        });

        $this->app->alias('userrepository', UserRepository::class);
    }

    /**
     * Register the credentials class.
     *
     * @return void
     */
    protected function registerCredentials()
    {
        $this->app->singleton('credentials', function ($app) {
            $sentinel = $app['sentinel'];
            $decorator = $app->make(PresenterDecorator::class);

            return new Credentials($sentinel, $decorator);
        });

        $this->app->alias('credentials', Credentials::class);
    }

    /**
     * Register the login controller class.
     *
     * @return void
     */
    protected function registerLoginController()
    {
        $this->app->bind(LoginController::class, function () {
            return new LoginController();
        });
    }

    /**
     * Register the registration controller class.
     *
     * @return void
     */
    protected function registerRegistrationController()
    {
        $this->app->bind(RegistrationController::class, function () {
            return new RegistrationController();
        });
    }

    /**
     * Register the reset controller class.
     *
     * @return void
     */
    protected function registerResetController()
    {
        $this->app->bind(ResetController::class, function () {
            return new ResetController();
        });
    }

    /**
     * Register the resend controller class.
     *
     * @return void
     */
    protected function registerActivationController()
    {
        $this->app->bind(ActivationController::class, function () {
            return new ActivationController();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            'revisionrepository',
            'userrepository',
            'grouprepository',
            'credentials',
        ];
    }
}