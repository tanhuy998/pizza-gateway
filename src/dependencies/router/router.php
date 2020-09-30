<?php  
    namespace Dependencies\Router;

    use Closure;
    use Dependencies\Router\Route as Route;
    use Dependencies\Http\Request as Request;
    use Dependencies\Http\Respone as Respone;
    use Dependencies\Middleware\Middleware as Middleware;
    use Application\Container\DIContainer as Container;
use Dependencies\Notification\EventClient;
use Dependencies\Notification\Notification;
use Dependencies\Router\RedirectModule as RedirectModule;
    use Dependencies\Parsing\URLParser as URLParser;
    use Dependencies\Router\DomainHandler as DomainHandler;
    use Exception;
use Reflection;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class Router extends EventClient {
        const GET = 'get';
        const POST = 'post';
        const PUT = 'put';
        const DELETE = 'delete';

        const MIDDLEWARE_PASS = 200;
        const CONTROLLER_NAMESPACE = 'App\Controller';

        /**
         *  Corner is represent http request's methods
         */
        private $corners;

        /**
         *  list of registered alias name of the route
         */
        private $nameList;

        /**
         *  Stack that store the registered routes
         */
        private $registerStack;

        private $container;

        public $redirect;
        public $parser;

        public $domain;

        public function __construct(Container $_container) {
            parent::__construct();
            $this->corners[self::GET] = [];
            $this->corners[self::POST] = [];
            $this->corners[self::PUT] = [];
            $this->corners[self::DELETE] = [];

            $this->nameList = [];
            $this->registerStack = [];

            $this->container = $_container;

            $this->redirect = new RedirectModule($this);
            $this->parser = new URLParser();

            $this->domain = new DomainHandler($this);

            $this->InitEvents();
        }

        private function InitEvents() {
            $this->AddEvent('onCreateRoute');
            $this->AddEvent('onInitialize');
            $this->AddEvent('onLoadController');

            $this->SubscribeEvent($this->domain, 'onManageRoute');
        } 

        /**
         *  magic fucntion to handle routes declaration
         *  support 4 methods of restful get/post/put/delete
         */
        public function __call($_method, $_args) {
            $method = strtolower($_method);

            //var_dump($_option);
            $pattern = $_args[0];
            $action = $_args[1];
            
            //  If $params does not contain two elements so the function is called indirectly
            if (!isset($pattern) || !isset($action)) throw new Exception();

            $this->parser->StandardizePattern($pattern);

            $existed_route = $this->CheckForRoute($method, $pattern);

            $send_route = null;

            if (is_null($existed_route)) {
                $this->CreateRoute($method, $pattern, null);

                //  ResolveMethod function will push new member to $registerStack when method name is correct
                //  throw exception on fail
                $send_route = $this->LastRoute();
            }
            else {
                $send_route = $existed_route;
            }
            
            $notification = new RouteCreateNotification($this, 'onCreateRoute', $send_route);
            $notification->SetState($action);

            $this->NotifyEvent('onCreateRoute', $notification);

            return $send_route;
        }

        public function AllVerbs(string $_pattern , $_action) {
            
            $supported_verbs = array_keys($this->corners);

            foreach ($supported_verbs as $verb) {
                
                $this->__call($verb, [$_pattern, $_action]);
            }

        }

        private function CheckForRoute(string $_method, string $_path) {

            $route_list = $this->OrientateCorner($_method);

            foreach ($route_list as $pattern => $route) {
                
                if ($pattern === $_path) return $route;
            }

            return null;
        }

        private function CreateRoute(string $_corner_name, $_pattern, $_action) {

            $name = strtolower($_corner_name);

            if (!array_key_exists($name, $this->corners)) throw new Exception();
            
            $corner = &$this->corners[$name];

            if ($this->PatternExists($_pattern, $corner)) throw new Exception();
            
            $this->registerStack[] = new Route($this, $_corner_name, $_pattern, $_action);

            $corner[$_pattern] = end($this->registerStack);
        }


        private function BindName() {
            $route = end($this->registerStack);
            
            $route_name = $route->GetName();
            
            if (is_null($route_name)) return;

            if (array_key_exists($route_name, $this->nameList)) return;

            $this->nameList[$route_name] = $route;
        }

        /**
         *  Retrive the last registered route from register stack
         */
        private function LastRoute(): Route {

            return end($this->registerStack);
        }

        private function PatternExists(string $_path, array $_corner) {

            return array_key_exists($_path, $_corner);
        }

        

        public function Handle(Request $_request): Respone {
            
            $direct_route = $this->ResolveRoute($_request);
            
            if (is_null($direct_route)) {
                return $this->UnExistRoute();
            }

            $_request->SetRoute($direct_route);

            return $this->ResolveRespone($_request);
        }

        private function ResolveRoute(Request $_request): ?Route {
            $corner = strtolower($_request->Method());
            
            if (!$this->HasCorner($corner)) throw new Exception();

            $route_list = $this->OrientateCorner($corner);

            $request_path = $this->parser->RemoveSubrootDirectory($_request->Path());

            foreach ($route_list as $pattern => $route) {
                echo $request_path;
                if ($this->parser->PatternMatch($request_path, $pattern)) {
                    
                    return $route;
                }
            }

            return null;
        }

        private function OrientateCorner($_corner): array {
            
            $corner = strtolower($_corner);

            if (!$this->HasCorner($_corner)) throw new Exception();

            return $this->corners[$_corner];
        }


        protected function UnExistRoute(): Respone {
            $respone = $this->container->Get(Respone::class);

            $respone->Render('404');
            $respone->Status(404);

            return $respone;
        }

        protected function HasCorner(string $_corner) {

            return array_key_exists($_corner, $this->corners);
        }

        /**
         *  resolve a spicific route
         *  @param Route
         *  @return Respone
         */
        private function ResolveRespone(Request $_request): Respone {

            $route = $_request->Route();

            $middleware_chain = $route->Middleware();

            $middlewares = $this->ResolveMiddleware($middleware_chain);
            
            $respone = $this->container->Get(Respone::class);

            $this->RunMiddleware($middlewares, $_request, $respone);

            if ($respone->Status() === self::MIDDLEWARE_PASS) {

                $request_hostname = $_request->Server('HTTP_HOST');
                
                $action = $route->Action($request_hostname);

                $result = $this->LoadController($action, $_request);

                //$content = $this->ConvertControllerResult($result);

                $respone->Render($result);
                $respone->Status(200);
            }

            return $respone;
        }

        private function LoadController($_action, Request $_request) {

            $route_args = $this->parser->ParseUriParameters($_request);

            if ($_action instanceof Closure) {

                $reflection = new ReflectionFunction($_action);

                $args = $this->AnalyseControllerParameters($reflection, $route_args);
                
                return $this->container->Call($_action, $args);
            }

            if (is_string($_action)) {
                $arr = explode('::', $_action);

                $controller = self::CONTROLLER_NAMESPACE.'\\'.$arr[0];
                $method = $arr[1];

                $reflection = new ReflectionMethod($controller, $method);

                $args = $this->AnalyseControllerParameters($reflection, $route_args);

                return $this->container->Call($controller, $method, $args);
            }
        }


        private function AnalyseControllerParameters(ReflectionFunctionAbstract $_function, array $_args): array {
            $parameters = $_function->getParameters();

            foreach ($parameters as $param) {
                $name = $param->getName();

                $type = $param->getType();

                if (!array_key_exists($name, $_args)) continue;
                
                // if the parameter has buitin type then cast the argument for the type
                if (!is_null($type)) {

                    $type_name = $type->getName();

                    switch($type_name) {
                        case 'int':
                            $_args[$name] = (int) $_args[$name];
                        break;
                        case 'string':
                            $_args[$name] = (string) $_args[$name];
                        break;
                        case 'float':
                            $_args[$name] = (float) $_args[$name];
                        break;
                        case 'double':
                            $_args[$name] = (double) $_args[$name];
                        break;
                        case 'array':
                            $_args[$name] = (array) $_args[$name];
                        break;
                    }
                }


            }

            return $_args;
        }

        private function ResolveMiddleware(array $_chain): array {

            return [];
        }

        private function RunMiddleware(array $_middleware_chain, Request $_request, Respone &$_respone) {

            $container = $this->container;
            
            array_walk($_middleware_chain, function($_middleware) use(&$_request, &$_respone, $container) {

                if ($_middleware instanceof Closure) {

                    $this->container->Call($_middleware);
                    
                    return;
                }

                if (is_string($_middleware)) {

                    $middleware = $this->$container->get($_middleware);

                    $middleware->execute();

                    return;
                }
            });
        }

        private function ValidateMiddleware($_middleware) {
            if (is_callable($_middleware)) {

                return;
            }

            if ($_middleware instanceof Middleware) {
                return;
            }    

            throw new Exception();
        }

        

        public function Route(string $_name): ?Route {
            
            if (array_key_exists($_name, $this->nameList)) {
                return $this->nameList[$_name];
            }

            return null;
        }

        public function RouteRegisterEvent() {
            $this->BindName();
        }


        public function Domain(string $_pattern, Closure $_init) {
            $this->domain->Manage($_pattern, $_init);
        }

        protected function HandleEventNotification(\Dependencies\Event\EventArgs $_notification) {
            
            if ($_notification->GetEventName() === 'onManageRoute'
                && $_notification->Sender() === $this->domain
            ) {
                $this->onManageDomain($_notification);
            }
        }

        private function onManageDomain(Notification $_notification) {
            $report = $_notification->GetState();

            $route = $report['route'];
            $action = $report['action'];
            $domain = $report['domain'];

            $route->SetAction($action, $domain);
        }


        
        // private function StandardizePattern(string &$_pattern) {
        //     $first_char = substr($_pattern,0,1);
        //     $last_char = substr($_pattern, strlen($_pattern)-1, 1);

        //     $_pattern = preg_replace('/^(\/)+/', '', $_pattern);

        //     $_pattern = '/'.$_pattern;
            
        //     $_pattern = $_pattern !== '/' ? preg_replace('/(\/)+$/', '', $_pattern): $_pattern;
        //     // $_pattern = $first_char == '/' ? substr($_pattern,1, strlen($_pattern) -1): $_pattern;
        //     // $_pattern = $last_char == '/' ? substr($_pattern,0,strlen($_pattern) -1): $_pattern;
        // }

        // private function PatternMatch(string $_subject, string $_pattern): bool {
        //     // $pattern_part = explode('/', $_pattern);

        //     // $path_part = explode('/',$_subject);
        //     //echo $_pattern;
        //     //if (count($pattern_part) != count($path_part)) return false;

        //     // $max_index = count($pattern_part);

        //     $regx = '/\{[a-zA-Z0-9]+\}/';

        //     $pattern = preg_replace($regx,'[a-zA-Z0-9]+', $_pattern);
            
        //     $pattern = str_replace('/', '\/', $pattern);

        //     $pattern = '/^'.$pattern.'$/';
            
        //     return preg_match($pattern, $_subject) === 1? true: false;

        //     // for ($index = 0; $index < $max_index; ++$index) {
                
        //     // }
        // }

        // private function RemoveSubrootDirectory(string $_uri): string {
        //     $subroot_dir = SubRootDir();

        //     $regex = '/'.$subroot_dir.'/';

        //     $return_uri = preg_replace($regex, '', $_uri);
        //     $return_uri = preg_replace('/(\/)+/', '/', $return_uri);
            
        //     return $return_uri;
        //     //return $subroot_dir != '' ? str_replace(SubRootDir().'/', '', $_uri) : $_uri;
        // }


        // private function ParseUriArguments(Request $_request): array {
        //     $request_uri = $_request->Path();
            
        //     $request_real_uri = $this->RemoveSubrootDirectory($request_uri);

        //     $route_uri_pattern = $_request->Route()->GetUriPattern();

        //     preg_match_all('/\{(.+?)\}/', $route_uri_pattern, $matches);

        //     $route_params = $matches[1];

        //     $keys = preg_replace('/\{|\}/', '', $route_uri_pattern);
        //     $keys = explode('/', $keys);

        //     $values = explode('/', $request_real_uri);
            
        //     $arr = array_combine($keys, $values);
        
        //     $callback = function ($key) use ($route_params) {
            
        //         return in_array($key, $route_params);
        //     };

        //     return array_filter($arr ,$callback, ARRAY_FILTER_USE_KEY);
        // }
    }