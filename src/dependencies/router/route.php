<?php 
    namespace Dependencies\Router;

use Closure;
use Dependencies\Router\Router as Router;
use Exception;
use ReflectionFunction;
use ReflectionType;

class Route {
        public const DOMAIN_DEFAULT = 0;

        private $router;

        private $actions;

        private $path;

        private $name;

        private $method;

        private $middlewares;

        protected $params;

        private $domainParams;

        public function __construct(Router &$_router, string $_method, string $_path, $_action = null, $_domain = self::DOMAIN_DEFAULT) {
            $this->path = $_path;

            $this->method = $_method;

            $this->middlewareChain = [];

            $this->router = $_router;

            $this->actions = [];

            if ($_action !== null) {
                $this->ValidateAction($_action);

                $this->actions[$_domain] = $_action;
            }

            preg_match_all('/\{(.+?)\}/', $_path, $matches);

            $this->ValidateParameters($matches[1]);
            $this->params = $matches[1];
        }

        public function Action($_domain = self::DOMAIN_DEFAULT) {

            if ($_domain === self::DOMAIN_DEFAULT) return $this->actions[self::DOMAIN_DEFAULT];

            foreach ($this->actions as $pattern => $action) {

                if ($this->router->parser->PatternMatch($_domain, $pattern)) {

                    return $action;
                    //return $this->actions[$pattern];
                }
            }
            
            return $this->actions[self::DOMAIN_DEFAULT];
        }
        
        public function Parameters() {
            return $this->params;
        }

        public function HasParameter(): bool {

            return (!is_null($this->params) || !empty($this->params));
        }

        public function SetAction($_action, $_domain = self::DOMAIN_DEFAULT) {

            $this->ValidateAction($_action);

            if (!isset($this->actions[$_domain])) {
                
                $this->actions[$_domain] = $_action;
            }
        }

        private function ValidateParameters(array $_params) {
            $params = array_count_values($_params);

            foreach ($params as $name => $time) {
                if ($time > 1) throw new Exception("");
            }
        }

        private function ValidateAction($_action) {
            if ($_action instanceof Closure) {
                return;
            }

            if (is_string($_action)) {
                $arr = explode('::' ,$_action);

                if (count($arr) === 2) {
                    return;
                }

                throw new Exception();
            }

            throw new Exception();
        }

        public function Method() {
            return $this->method;
        }

        public function Name(string $_name) {
            if (!isset($this->name)) {
                $this->name = $_name;

                $this->router->RouteRegisterEvent();
            }
        }

        public function GetName() {
            return $this->name;
        }

        public function Middleware(...$_chain) {

            if (empty($_chain)) {
                return $this->middlewareChain;
            }

            foreach ($_chain as $abstract) {

                if (is_string($abstract)) {

                    $chain = explode('-', $abstract);

                    $this->middlewares = array_merge($this->middlewares, $chain);

                    continue;
                }

                if ($abstract instanceof Closure) {
                    $this->middlewares[] = $abstract;
                    
                    continue;
                }
            }
        }


        public function GetUriPattern() {
            return $this->path;
        }

        public function SetDomain(string $_name) {

            if (isset($this->subdomain)) return;

            preg_match_all('/\{(.+?)\}/', $_name, $matches);

            if (!empty($matches[1])) {

                $this->ValidateParameters($matches[1]);

                $this->domainParams = $matches[1];
            }

            $this->subdomain = $_name;
        }

        public function GetSubdomain() {
            return $this->subdomain;
        }

        public function DomainParameters() {

            return $this->domainParams;
        }
    }