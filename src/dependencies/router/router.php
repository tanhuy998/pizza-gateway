<?php  
    namespace Dependencies\Router;

    use Closure;
    use Dependencies\Router\Route as Route;
    use Dependencies\Http\Request as Request;
    use Dependencies\Http\Respone as Respone;
    use Dependencies\Middleware\Middleware as Middleware;
    use Application\Container\DIContainer as Container;

    use Exception;

    class Router {
        const GET = 'get';
        const POST = 'post';
        const PUT = 'put';
        const DELETE = 'delete';

        /**
         *  Four lists storing routes of specific restful methods
         */
        // private $get;
        // private $post;
        // private $put;
        // private $delete;

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

        public function __construct() {
            $this->corners[self::GET] = [];
            $this->corners[self::POST] = [];
            $this->corners[self::PUT] = [];
            $this->corners[self::DELETE] = [];

            $this->nameList = [];
            $this->registerStack = [];
        }

        /**
         *  magic fucntion to handle routes declaration
         *  support 4 methods of restful get/post/put/delete
         */
        public function __call($_method, $_args) {
            $method = strtolower($_method);

            //var_dump($_option);
            $path = $_args[0];
            $action = $_args[1];

            //  If $params does not contain two elements so the function is called indirectly
            if (!isset($pattern) || !isset($action)) throw new Exception();

            $this->StandardizePattern($pattern);

            $this->CreateRoute($method, $pattern);

            //  ResolveMethod function will push new member to $registerStack when method name is correct
            //  throw exception on fail
            $new_route = $this->LastRoute();

            return $new_route;
        }

        private function CreateRoute(string $_corner_name, $_path) {

            $name = strtolower($_corner_name);

            if (!array_key_exists($name, $this->corners)) throw new Exception();

            $corner = $this->corners[$name];

            if ($this->PathExists($_path, $corner)) throw new Exception();
            
            $this->registerStack[] = new Route($this, $_path);

            $corner[$_path] = end($this->registerStack);
        }

        private function StandardizePattern(string &$_pattern) {
            $first_char = substr($_pattern,0,1);
            $last_char = substr($_pattern, strlen($_pattern)-1, 1);

            $_pattern = $first_char == '/' ? substr($_pattern,1, strlen($_pattern) -1): $_pattern;
            $_pattern = $last_char == '/' ? substr($_pattern,0,strlen($_pattern) -1): $_pattern;
        }


        private function BindName() {
            $route = end($this->registerStack);

            $route_name = $route->Name();

            if (is_null($route_name)) return;

            if (array_key_exists($route_name, $this->nameList)) return;

            $this->nameList['$route_name'] = $route;
        }

        /**
         *  Retrive the last registered route from register stack
         */
        private function LastRoute(): Route {

            return end($this->registerStack);
        }

        private function PathExists(string $_path, array $_corner) {

            return array_key_exists($_path, $_corner);
        }

        

        public function Handle(Request $_request) {

            $corner = strtolower($_request->Method());

            if (!$this->HasCorner($corner)) throw new Exception();

            $route_list = $this->OrientateCorner($corner);

            $request_path = $this->RemoveSubrootDirectory($_request->Path());

            $direct_route = null;
            
            foreach ($route_list as $pattern => $route) {

                if ($this->PatternMatch($request_path, $pattern)) {

                    $direct_route = $route;

                    break;
                }
            }

            if (is_null($direct_route)) ;

            return $this->ResolveRoute($direct_route, $_request);
        }

        private function OrientateCorner($_corner): array {
            
            $corner = strtolower($_corner);

            if (!$this->HasCorner($_corner)) throw new Exception();

            return $this->
        }

        private function RemoveSubrootDirectory(string $_uri): string {

            return SubRootDir() != '' ? str_replace(SubRootDir().'/', '', $_uri) : $_uri;
        }

        private function HasCorner(string $_corner) {

            return array_key_exists($_corner, $this->corners);
        }

        /**
         *  resolve a spicific route
         *  @param Route
         *  @return Respone
         */
        private function ResolveRoute(Route $_route, $_request) {

            $middleware_chain = $_route->Middleware();

            $this->ResolveMiddleware($middleware_chain);

            $respone = new Respone();
            
            

            return $respone;
        }

        private function ResolveMiddleware(array $_chain): array {

            return [];
        }

        private function RunMiddleware(array $_middleware_chain, Request $_request, Respone $_respone) {

            $container = Container::GetInstance();

            array_walk($middleware_chain, function($_middleware) use(&$_request, &$_respone, $container) {

                if ($_middleware instanceof Closure) {

                    $container->CallClosure($_middleware);

                    return;
                }

                if (is_string($_middleware)) {

                    $middleware = $container->get($_middleware);

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
                return
            }    

            throw new Exception();
        }

        private function PatternMatch(string $_subject, string $_pattern): bool {
            // $pattern_part = explode('/', $_pattern);

            // $path_part = explode('/',$_subject);
            //echo $_pattern;
            //if (count($pattern_part) != count($path_part)) return false;

            // $max_index = count($pattern_part);

            $regx = '/\{[a-zA-Z0-9]+\}/';

            $pattern = preg_replace($regx,'[a-zA-Z0-9]+', $_pattern);
            
            $pattern = str_replace('/', '\/', $pattern);

            $pattern = '/^'.$pattern.'$/';
            
            return preg_match($pattern, $_subject) === 1? true: false;

            // for ($index = 0; $index < $max_index; ++$index) {
                
            // }
        }

        public function RouteRegisterEvent() {
            $this->BindName();
        }
    }