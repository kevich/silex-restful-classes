<?php

namespace APG\SilexRESTful;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use APG\SilexRESTful\Interfaces\Service;
use APG\SilexRESTful\Helpers;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use Worker\Model as WorkerModel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class ControllerProviderAbstract implements ControllerProviderInterface
{
    /**
     * @var string
     */
    protected $object_name;

    /**
     * @var Service
     */
    protected $service;

    /**
     * @var array
     */
    protected $securityLimitationsConfig;

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     * @return ControllerCollection A ControllerCollection instance
     * @internal ControllerCollection $controllers
     */
    final public function connect(Application $app)
    {
        // creates a new controller based on the default route
        /** @var $controllers ControllerCollection */
        $controllers = $app['controllers_factory'];

        /** @var ServiceDefault $default_service */
        $default_service = $app['object.service'];
        $default_service->setTableName($this->object_name);

        $this->service = (class_exists(Helpers::to_camel_case($this->object_name) . '\Service')) ?
            $app['object.' . $this->object_name] : $app['object.service'];

        $this->registerAdditionalControllers($controllers);

        $controllers->get('/', $this->getAll($this));

        $controllers->get('/{id}', $this->getById($this));

        $controllers->post('/', $this->post($this));

        $controllers->put('/{id}', $this->put($this));

        $controllers->delete('/{id}', $this->deleteById($this));

        return $controllers;
    }

    /**
     * @param array $config
     */
    final public function registerSecurityLimitationsConfig($config)
    {
        $this->securityLimitationsConfig = $config;
    }

    /**
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return string
     */
    public function getObjectName()
    {
        return $this->object_name;
    }

    /**
     * @param self $controllerProvider
     * @return callable
     */
    protected function post($controllerProvider)
    {
        return function (Application $app, Request $request) use ($controllerProvider) {
            $objectName = $controllerProvider->getObjectName();
            $content = json_decode($request->getContent());
            if (!$data = $content->$objectName) {
                return new Response('Missing parameters.', 400);
            }
            if ($controllerProvider->getService() instanceof ServiceDefault) {
                $controllerProvider->getService()->setTableName($objectName);
            }

            $class_name = Helpers::to_camel_case($objectName) . '\Model';
            $object = class_exists($class_name) ? new $class_name() : new ModelDummy($objectName);
            $object->fillFromArray((array)$data);
            if (isset($app['securityFilter'])
                && !(
                    $app['securityFilter']->isMethodAllowedForItemName('post', $controllerProvider->getObjectName())
                    || $app['securityFilter']->isMethodAllowedForItem('post', $controllerProvider->getObjectName(), $object)
                )
            ) {
                return new Response('Method not allowed', 405);
            }
            $status = $controllerProvider->getService()->saveObject($object);
            return $status ? $app->json(array(
                'success' => true,
                'msg' => 'created',
                'data' => array_merge(array('id' => $object->getId()), get_object_vars($object))
            )) : new Response('Server error.', 500);
        };
    }

    /**
     * @param self $controllerProvider
     * @return callable
     */
    protected function put($controllerProvider)
    {
        return function (Application $app, Request $request, $id) use ($controllerProvider) {
            $objectName = $controllerProvider->getObjectName();
            $content = json_decode($request->getContent());
            if (!$data = $content->$objectName) {
                return new Response('Missing parameters.', 400);
            }

            if ($controllerProvider->getService() instanceof ServiceDefault) {
                $controllerProvider->getService()->setTableName($objectName);
            }

            if (!$object = $controllerProvider->getService()->getById($id)) {
                return new Response('Missing parameters.', 400);
            }

            $object->fillFromArray((array)$data);
            if (isset($app['securityFilter'])
                && !(
                    $app['securityFilter']->isMethodAllowedForItemName('put', $controllerProvider->getObjectName())
                    || $app['securityFilter']->isMethodAllowedForItem('put', $controllerProvider->getObjectName(), $object)
                )
            ) {
                return new Response('Method not allowed', 405);
            }
            $status = $controllerProvider->getService()->updateObject($object);

            return $status ? $app->json(array(
                'success' => true,
                'msg' => 'updated',
                'data' => array_merge(array('id' => $object->getId()), get_object_vars($object))
            )) : new Response('Server error.', 500);
        };
    }

    /**
     * @param self $controllerProvider
     * @return callable
     */
    protected function getAll($controllerProvider)
    {
        return function (Application $app, Request $request) use ($controllerProvider) {
            $filters = json_decode($request->get('filter')) ?: array();
            if ($query = $request->get('query')) {
                foreach ($query as $name => $value) {
                    $filter = new \StdClass;
                    $filter->field = $name;
                    $filter->value = $value;
                    $filters[] = $filter;
                }

            }
            if (isset($app['securityFilter'])) {
                if (!$app['securityFilter']->isMethodAllowedForItemName('getAll', $controllerProvider->getObjectName())) {
                    return new Response('Method not allowed', 405);
                }
                $filters = $app['securityFilter']->extendFilters($controllerProvider->getObjectName(), $filters);
            }
            if ($controllerProvider->getService() instanceof ServiceDefault) {
                $controllerProvider->getService()->setTableName($controllerProvider->getObjectName());
            }
            $start = $request->get('start');
            $limit = $request->get('limit');
            if ($request->get('sort')) {
                $controllerProvider->getService()->setSorters(json_decode($request->get('sort')));
            }
            $controllerProvider->getService()->setFilters($filters);
            $controllerProvider->getService()->setUser($controllerProvider->getUser($app));
            $total = $controllerProvider->getService()->getTotalCount();
            return $app->json(array(
                'total' => $total,
                'data' => $controllerProvider->getService()->getAllAssoc($start, $limit)));
        };
    }

    /**
     * @param self $controllerProvider
     * @return callable
     */
    protected function getById($controllerProvider)
    {
        return function (Application $app, Request $request, $id) use ($controllerProvider) {
            if ($controllerProvider->getService() instanceof ServiceDefault) {
                $controllerProvider->getService()->setTableName($controllerProvider->getObjectName());
            }
            if (isset($app['securityFilter'])) {
                if (!$app['securityFilter']->isMethodAllowedForItemName('getById', $controllerProvider->getObjectName())) {
                    return new Response('Method not allowed', 405);
                }
            }
            $controllerProvider->getService()->setUser($controllerProvider->getUser($app));
            return $app->json(array(
                    'success' => true,
                    'data' => $controllerProvider->getService()->getByIdAssoc($id)
                )
            );
        };
    }

    /**
     * @param self $controllerProvider
     * @return callable
     */
    protected function deleteById($controllerProvider)
    {
        return function (Application $app, Request $request, $id) use ($controllerProvider) {
            if ($controllerProvider->getService() instanceof ServiceDefault) {
                $controllerProvider->getService()->setTableName($controllerProvider->getObjectName());
            }
            if (!$object = $controllerProvider->getService()->getById($id)) {
                return new Response('Missing parameters.', 400);
            }
            if (isset($app['securityFilter'])
                && !(
                    $app['securityFilter']->isMethodAllowedForItemName('deleteById', $controllerProvider->getObjectName())
                    || $app['securityFilter']->isMethodAllowedForItem('deleteById', $controllerProvider->getObjectName(), $object)
                )
            ) {
                return new Response('Method not allowed', 405);
            }

            return $controllerProvider->getService()->deleteById($id) ? $app->json(array(
                'success' => true,
                'msg' => 'deleted',
                'data' => array()
            )) : new Response('Not found', 404);
        };
    }

    /**
     * @param Application $app
     * @return null|WorkerModel
     */
    protected function getUser($app)
    {
        /** @var TokenInterface $token */
        $token = $app['security']->getToken();
        if (null !== $token) {
            return $token->getUser();
        }
        return null;
    }

    /**
     * @param ControllerCollection $controllers
     */
    abstract protected function registerAdditionalControllers($controllers);

}
