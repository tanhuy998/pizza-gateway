<?php  
    namespace Dependencies\Router;

    use Dependencies\Router\Route as Route;
    use Dependencies\Http\Request as Request;

use Exception;

class Router {
        const GET = 1;
        const POST = 2;
        const PUT = 3;
        const DELETE = 4;

        /**
         *  Four lists storing routes of specific restful methods
         */
        private $get;
        private $post;
        private $put;
        private $delete;

        /**
         *  list of registered alias name of the route
         */
        private $nameList;

        /**
         *  Stack that store the registered routes
         */
        private $registerStack;

        public function __construct() {
            $this->get = [];
            $this->post = [];
            $this->put = [];
            $this->delete = [];

            $this->nameList = [];
            $this->registerStack = [];
        }

        /**
         *  magic fucntion to handle routes declaration
         *  support 4 method of restful get/post/put/delete
         */
        public function __call($_name, $_params) {
            $name = strtolower($_name);

            //var_dump($_params);
            $pattern = $_params[0];
            $action = $_params[1];

            //  If $params does not contain two elements so the function is called indirectly
            if (!isset($pattern) || !isset($action)) throw new Exception();

            $this->StandardizePattern($pattern);

            //  return the reference of list of the method to map route
            $this->InitMethod($name, $pattern);

            //  ResolveMethod function will push new member to $registerStack when method name is correct
            //  throw exception on fail
            $new_route = $this->LastRoute();

            $new_route->SetAction($action);

            return $new_route;
        }

        private function StandardizePattern(string &$_pattern) {
            $first_char = substr($_pattern,0,1);
            $last_char = substr($_pattern, strlen($_pattern)-1, 1);

            $_pattern = $first_char == '/' ? substr($_pattern,1, strlen($_pattern) -1): $_pattern;
            $_pattern = $last_char == '/' ? substr($_pattern,0,strlen($_pattern) -1): $_pattern;
        }


        public function BindName(string $_name, Route &$_route) {
            if (isset($this->nameList[$_name])) throw new Exception();

            $this->nameList[$_name] = $_route;
        }

        /**
         *  Retrive the last registered route from register stack
         */
        private function LastRoute(): Route {
            $head = count($this->registerStack) - 1;
            return $this->registerStack[$head];
        }


        private function InitMethod(string $_name, $_pattern) {
            switch ($_name) {
                case 'get':
                    $this->registerStack[] = new Route($this, $_pattern);
                    $this->get[$_pattern] = $this->registerStack[(count($this->registerStack) -1)];
                break;
                case 'post':
                    $this->registerStack[] = new Route($this, $_pattern);
                    $this->post[$_pattern] = $this->registerStack[(count($this->registerStack) -1)];
                break;
                case 'put':
                    $this->registerStack[] = new Route($this, $_pattern);
                    $this->put[$_pattern] = $this->registerStack[(count($this->registerStack) -1)];
                break;
                case 'delete':
                    $this->registerStack[] = new Route($this, $_pattern);
                    $this->delete[$_pattern] = $this->registerStack[(count($this->registerStack) -1)];
                break;
                default:
                    throw new Exception();
                break;
            }
        }

        

        public function Handle(Request $_request) {
            $support = [
                'get' => self::GET,
                'post' => self::POST,
                'put' => self::PUT,
                'delete' => self::DELETE
            ];

            $request_method = strtolower($_request->Method());

            $request_method = $support[$request_method];

            if (!isset($request_method)) throw new Exception();

            $route_list = $this->ResolveMethod($request_method);

            $request_path = SubRootDir() != '' ? str_replace(SubRootDir().'/', '', $_request->Path()) : $_request->path();
            
            foreach ($route_list as $pattern => $route) {
                if ($this->PatternMatch($request_path, $pattern)) {

                    return $route;
                }
            }
        }

        private function ResolveMethod($_method) {
            switch ($_method) {
                case self::GET:
                    return $this->get;
                break;
                case self::POST:
                    return $this->post;
                break;
                case self::PUT:
                    return $this->put;
                break;
                case self::DELETE:
                    return $this->delete;
                break;
                default:
                break;
            }
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
    }