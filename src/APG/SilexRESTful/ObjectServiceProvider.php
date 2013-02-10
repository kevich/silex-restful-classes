<?php
namespace APG\SilexRESTful;

use Silex\Application;
use Silex\ServiceProviderInterface;

class ObjectServiceProvider implements ServiceProviderInterface
{
    private $classes;

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
        foreach ($this->classes as $label => $class) {
            $app["object.".$label] = $app->share(function() use ($class, $app) {
                return class_exists($class) ? new $class($app['db']) : null;
            });
        }
        $app["object.service"] = $app->share(function() use ($app) {
            return new ServiceDefault($app['db']);
            });
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registers
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {

    }

    public function  __construct($classes)
    {
        $this->classes = $classes;
    }
}
