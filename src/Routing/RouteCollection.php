<?php

namespace JetFire\Routing;


/**
 * Class RouteCollection
 * @package JetFire\Routing
 */
class RouteCollection
{

    /**
     * @var array
     */
    private $routes = [];
    /**
     * @var array
     */
    public $routesByName = [];
    /**
     * @var int
     */
    public $countRoutes = 0;
    /**
     * @var
     */
    public $middleware;

    /**
     * @param array $routes
     * @param array $options
     */
    public function __construct($routes = null, $options = [])
    {
        if (!is_null($routes) || !empty($options)) $this->addRoutes($routes, $options);
    }

    /**
     * @param array|string $routes
     * @param array $options
     */
    public function addRoutes($routes = null, $options = [])
    {
        if(!is_null($routes) && !is_array($routes)) {
            if (strpos($routes, '.php') === false) $routes = trim($routes, '/') . '/';
            if (is_file($routes . '/routes.php') && is_array($routesFile = include $routes . '/routes.php')) $routes = $routesFile;
            elseif (is_file($routes) && is_array($routesFile = include $routes)) $routes = $routesFile;
            else throw new \InvalidArgumentException('Argument for "' . get_called_class() . '" constructor is not recognized. Expected argument array or file containing array but "'.$routes.'" given');
        }
        $this->routes['routes_' . $this->countRoutes] = is_array($routes) ? $routes : [];
        $this->routes['view_dir_' . $this->countRoutes] = (isset($options['view_dir']) && !empty($options['view_dir'])) ? rtrim($options['view_dir'], '/') . '/' : '';
        $this->routes['block_' . $this->countRoutes] = (isset($options['block']) && !empty($options['block'])) ? rtrim($options['block'], '/') . '/' : $this->routes['view_dir_' . $this->countRoutes];
        $this->routes['ctrl_namespace_' . $this->countRoutes] = (isset($options['ctrl_namespace']) && !empty($options['ctrl_namespace'])) ? trim($options['ctrl_namespace'], '\\') . '\\' : '';
        $this->routes['prefix_' . $this->countRoutes] = (isset($options['prefix']) && !empty($options['prefix'])) ? '/' . trim($options['prefix'], '/') : '';
        $this->countRoutes++;
    }

    /**
     * @param null $key
     * @return array
     */
    public function getRoutes($key = null)
    {
        if(!is_null($key))
            return isset($this->routes[$key])?$this->routes[$key]:'';
        return $this->routes;
    }

    /**
     * @param $args
     */
    public function setPrefix($args)
    {
        if (is_array($args)) {
            $nbrArgs = count($args);
            for ($i = 0; $i < $nbrArgs; ++$i)
                $this->routes['prefix_' . $i] = '/' . trim($args[$i], '/');
        } elseif (is_string($args))
            for ($i = 0; $i < $this->countRoutes; ++$i)
                $this->routes['prefix_' . $i] = '/' . trim($args, '/');
        if($this->countRoutes == 0)$this->countRoutes++;
    }

    /**
     * @param $args
     */
    public function setOption($args = [])
    {
        $nbrArgs = count($args);
        for ($i = 0; $i < $nbrArgs; ++$i) {
            if(is_array($args[$i])){
                $this->routes['block_' . $i] = (isset($args[$i]['block']) && !empty($args[$i]['block'])) ? rtrim($args[$i]['block'], '/') . '/' : '';
                $this->routes['view_dir_' . $i] = (isset($args[$i]['view_dir']) && !empty($args[$i]['view_dir'])) ? rtrim($args[$i]['view_dir'], '/') . '/' : '';
                $this->routes['ctrl_namespace_' . $i] = (isset($args[$i]['ctrl_namespace']) && !empty($args[$i]['ctrl_namespace'])) ? trim($args[$i]['ctrl_namespace'], '\\') . '\\' : '';
                $this->routes['prefix_' . $i] = (isset($args[$i]['prefix']) && !empty($args[$i]['prefix'])) ? '/'.trim($args[$i]['prefix'], '/') : '';
                if(!isset($this->routes['routes_' . $i]))$this->routes['routes_' . $i] = [];
            }
        }
        if($this->countRoutes == 0)$this->countRoutes++;
    }

    /**
     * @param $middleware
     * @throws \Exception
     */
    public function setMiddleware($middleware)
    {
        if (is_string($middleware)) $middleware = rtrim($middleware, '/');
        if(is_array($middleware))
            $this->middleware = $middleware;
        elseif (is_file($middleware) && is_array($mid = include $middleware))
            $this->middleware = $mid;
        else throw new \InvalidArgumentException('Accepted argument for setMiddleware are array and array file');
    }

    /**
     * @return bool
     */
    public function generateRoutesPath()
    {
        $root = 'http://' . (isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME']) . str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
        $count = 0;
        for ($i = 0; $i < $this->countRoutes; ++$i) {
            $prefix = (isset($this->routes['prefix_' . $i])) ? $this->routes['prefix_' . $i] : '';
            if (isset($this->routes['routes_' . $i]))
                foreach ($this->routes['routes_' . $i] as $route => $dependencies) {
                    if (is_array($dependencies) && isset($dependencies['use']))
                        $use = (is_callable($dependencies['use'])) ? 'closure-' . $count : trim($dependencies['use'], '/');
                    elseif(!is_array($dependencies))
                        $use = (is_callable($dependencies)) ? 'closure-' . $count : trim($dependencies, '/');
                    else
                        $use = $route;
                    (!is_callable($dependencies) && isset($dependencies['name'])) ? $this->routesByName[$use . '#' . $dependencies['name']] = $root . $prefix . $route : $this->routesByName[$use] = $root . $prefix . $route;
                    $count++;
                }
        }
        return true;
    }

    /**
     * @param null $name
     * @param array $params
     * @return mixed
     */
    public function getRoutePath($name, $params = [])
    {
        foreach ($this->routesByName as $key => $route) {
            $param = explode('#', $key);
            foreach ($params as $key2 => $value) $route = str_replace(':' . $key2, $value, $route);
            if ($param[0] == trim($name, '/')) return $route;
            else if (isset($param[1]) && $param[1] == $name) return $route;
        }
        return null;
    }
}
