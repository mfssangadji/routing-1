<?php

namespace JetFire\Routing;
use ReflectionMethod;


/**
 * Class Middleware
 * @package JetFire\Routing
 */
class Middleware
{

    /**
     * @var Router
     */
    private $router;

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @description global middleware
     */
    public function globalMiddleware()
    {
        if (isset($this->router->collection->middleware['global_middleware']))
            foreach ($this->router->collection->middleware['global_middleware'] as $mid) {
                if (class_exists($mid)) {
                    $mid_global = call_user_func($this->router->getConfig()['di'],$mid);
                    if (method_exists($mid_global, 'handle')) $this->callHandler($mid_global);
                }
            }
    }

    /**
     * @description block middleware
     */
    public function blockMiddleware()
    {
        if (isset($this->router->collection->middleware['block_middleware']))
            if (isset($this->router->collection->middleware['block_middleware'][$this->router->route->getTarget('block')]) && class_exists($this->router->collection->middleware['block_middleware'][$this->router->route->getTarget('block')])) {
                $class = $this->router->collection->middleware['block_middleware'][$this->router->route->getTarget('block')];
                $mid_block = call_user_func($this->router->getConfig()['di'],$class);
                if (method_exists($mid_block, 'handle')) $this->callHandler($mid_block);
            }
    }

    /**
     * @description controller middleware
     */
    public function classMiddleware()
    {
        if (isset($this->router->collection->middleware['class_middleware'])) {
            $ctrl = str_replace('\\', '/', $this->router->route->getTarget('controller'));
            if (isset($this->router->collection->middleware['class_middleware'][$ctrl]) && class_exists($this->router->route->getTarget('controller'))) {
                $class = $this->router->collection->middleware['class_middleware'][$ctrl];
                $mid_class = call_user_func($this->router->getConfig()['di'],$class);
                if (method_exists($mid_class, 'handle')) $this->callHandler($mid_class);
            }
        }
    }

    /**
     * @description route middleware
     */
    public function routeMiddleware()
    {
        if (isset($this->router->collection->middleware['route_middleware']))
            if (isset($this->router->route->getPath()['middleware']) && class_exists($this->router->collection->middleware['route_middleware'][$this->router->route->getPath()['middleware']])) {
                $class = $this->router->collection->middleware['route_middleware'][$this->router->route->getPath()['middleware']];
                $mid_route = call_user_func($this->router->getConfig()['di'],$class);
                if (method_exists($mid_route, 'handle')) $this->callHandler($mid_route);
            }
    }

    /**
     * @param $instance
     * @return mixed
     */
    private function callHandler($instance){
        $reflectionMethod = new ReflectionMethod($instance, 'handle');
        $dependencies = [];
        foreach ($reflectionMethod->getParameters() as $arg)
            if (!is_null($arg->getClass()))
                $dependencies[] = $this->getClass($arg->getClass()->name);
        $dependencies = array_merge($dependencies,[$this->router->route]);
        return $reflectionMethod->invokeArgs($instance, $dependencies);
    }

    /**
     * @param $class
     * @return Route|RouteCollection|Router|mixed
     */
    private function getClass($class){
        switch($class){
            case 'JetFire\Routing\Route':
                return $this->router->route;
                break;
            case 'JetFire\Routing\Router':
                return $this->router;
                break;
            case 'JetFire\Routing\RouteCollection':
                return $this->router->collection;
                break;
            default:
                return call_user_func_array($this->router->getConfig()['di'],[$class]);
                break;
        }
    }

}
