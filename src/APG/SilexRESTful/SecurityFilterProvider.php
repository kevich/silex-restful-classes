<?php
namespace APG\SilexRESTful;


use Silex\Application;
use Silex\ServiceProviderInterface;

class SecurityFilterProvider implements ServiceProviderInterface {

    private $configName;

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app An Application instance
     */
    public function register(Application $app)
    {
        if (isset($app[$this->configName])) {
            $app['securityFilter'] = new SecurityFilter($app[$this->configName]);
        }
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     * @param Application $app
     */
    public function boot(Application $app)
    {
        if ($app->offsetExists('security.authentication_providers')) {
            $app['securityFilter']->setSecurityService($app['security']);
        }
    }

    /**
     * @param string $configName
     */
    public function __construct($configName) {
        $this->configName = $configName;
    }
}