<?php
namespace APG\SilexRESTful;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use APG\SilexRESTful\Interfaces\Service;
use APG\SilexRESTful\Helpers;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

abstract class ControllerProviderAbstract implements ControllerProviderInterface
{
    protected $object_name;
    protected $service;
    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     * @internal ControllerCollection $controllers
     * @return ControllerCollection A ControllerCollection instance
     */
    final public function connect(Application $app)
    {
        // creates a new controller based on the default route
        /** @var $controllers ControllerCollection */
        $controllers = $app['controllers_factory'];

        /** @var ServiceDefault $default_service  */
        $default_service = $app['object.service'];
        $default_service->setTableName($this->object_name);

        $this->service = (class_exists(Helpers::to_camel_case($this->object_name) . '\Service')) ?
            $app['object.' . $this->object_name] : $app['object.service'];

        $this->registerAdditionalControllers($controllers);

        $controllers->get('/', $this->getAll($this->service, $this->object_name));

        $controllers->get('/{id}', $this->getById($this->service, $this->object_name));

        $controllers->post('/', $this->post($app, $this->service, $this->object_name));

        $controllers->put('/{id}', $this->put($app, $this->service, $this->object_name));

        $controllers->delete('/{id}', $this->deleteById($this->service, $this->object_name));


        return $controllers;
    }

    /**
     * @param Application $app
     * @param Service $service
     * @return callable
     */
    protected function post($app, $service, $object_name)
    {
        return function (Request $request) use ($app, $service, $object_name) {

            $content = json_decode($request->getContent());
            if (!$data = $content->$object_name) {
                return new Response('Missing parameters.', 400);
            }

            $class_name = Helpers::to_camel_case($object_name) . '\Model';
            $object = class_exists($class_name) ? new $class_name() : new ModelDummy($object_name);
            $object->fillFromArray($data);
            $status = $service->saveObject($object);
            return $status ? $app->json(array(
                    'success' => true,
                    'msg' => 'created',
                    'data' => array_merge(array('id' => $object->getId()),(array)$object)
                )) : new Response('Server error.', 500);
        };
    }

    /**
     * @param Application $app
     * @param Service $service
     * @return callable
     */
    protected function put($app, $service, $object_name)
    {
        return function (Application $app, Request $request, $id) use ($app, $service, $object_name) {

            $content = json_decode($request->getContent());
            if (!$data = $content->$object_name) {
                return new Response('Missing parameters.', 400);
            }

            if (get_class($service) == 'APG\SilexRESTful\ServiceDefault') {
                /** @var ServiceDefault $service */
                $service->setTableName($object_name);
            }

            if (!$object = $service->getById($id)) {
                return new Response('Missing parameters.', 400);
            }

            $object->fillFromArray((array)$data);
            $status = $service->updateObject($object);

            return $status ? $app->json(array(
                    'success' => true,
                    'msg' => 'updated',
                    'data' => array_merge(array('id' => $object->getId()),(array)$object)
                )) : new Response('Server error.', 500);
        };
    }

    /**
     * @param Service $service
     * @return callable
     */
    protected function getAll($service, $object_name)
    {
        return function (Application $app, Request $request) use($service, $object_name) {
            if (get_class($service) == 'APG\SilexRESTful\ServiceDefault') {
                /** @var ServiceDefault $service */
                $service->setTableName($object_name);
            }
            $start = $request->get('start');
            $limit = $request->get('limit');
            $service->setFilters(json_decode($request->get('filter')));
            $total = $service->getTotalCount();
            return $app->json(array(
                    'total' => $total,
                    'data' => $service->getAllAssoc($start,$limit)));
        };
    }
    /**
     * @param Service $service
     * @return callable
     */
    protected function getById($service, $object_name)
    {
        return function (Application $app, Request $request, $id) use($service, $object_name) {
            if (get_class($service) == 'APG\SilexRESTful\ServiceDefault') {
                /** @var ServiceDefault $service */
                $service->setTableName($object_name);
            }
            return $app->json(array(
                'success' => true,
                'data' => $service->getByIdAssoc($id)
                )
            );
        };
    }

    /**
     * @param Service $service
     * @return callable
     */
    protected function deleteById($service, $object_name)
    {
        return function (Application $app, Request $request, $id) use ($service, $object_name) {
            if (get_class($service) == 'APG\SilexRESTful\ServiceDefault') {
                /** @var ServiceDefault $service */
                $service->setTableName($object_name);
            }
            return $service->deleteById($id) ? $app->json(array(
                    'success' => true,
                    'msg' => 'deleted',
                    'data' => array()
                )) : new Response('Not found', 404);
        };
    }

    /**
     * @param ControllerCollection $controllers
     */
    abstract protected function registerAdditionalControllers($controllers);
}
