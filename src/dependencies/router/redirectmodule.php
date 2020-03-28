<?php
    namespace Dependencies\Router;

use Application\Container\DIContainer as DIContainer;
use Dependencies\Router\Router as Router;
    use Dependencies\Router\IRedirectable as IRedirectable;
    use Exception;

    class RedirectModule implements IRedirectable {

        private $router;
        private $currentLocation;
        private $referer;

        public function __construct(Router $_router) {
            $this->router = $_router;
            $this->referer = $this->GetReferer();
            $this->currentLocation = CurrentLocation();
        }

        public function Location(string $_location, int $_status = 302) {
            $subroot_dir = SubRootDir();


            if ($this->IsRelativeLink($_location)) {
                $_location = preg_replace('/\/\/+/', '/', $_location);

                $_location = $subroot_dir !== '' ? '/'.$subroot_dir.$_location : $_location;
            }
            
            if ($_location === $this->currentLocation) return;
            
            $this->PlaceReferer();
            header('Location: '.$_location, true, $_status);
            exit;
        }

        public function Route(string $_name, array $_params = [], int $_status = 302) {
            $route = $this->router->Route($_name);

            if (!is_null($route)) {

                $route_path_pattern = $route->GetUriPattern();

                if ($route->HasParameter()) {
                    $route_params = $route->Parameters();

                    foreach ($route_params as $name) {
                        if (array_key_exists($name, $_params)) {

                            $route_path_pattern = str_replace("{$name}", $_params[$name], $route_path_pattern);
                        }
                        else {
                            throw new Exception();
                        }
                    }
                }

                $subroot_dir = SubRootDir();

                //$route_path_pattern = $subroot_dir !== '' ? '/'.$subroot_dir.$route_path_pattern : $route_path_pattern;

                $this->Location($route_path_pattern, $_status);
            }  
            
            throw new Exception("Trying to redirect to route '$_name' that is unnamed or unexists");
        }

        public function Back() {
            if ($this->referer !== '') {
                $this->Location($this->referer);
            }
        }

        // public function GetCurrentLocation(): string {
        //     $path = explode('?', $_SERVER['REQUEST_URI'])[0];

        //     $path = preg_replace('/^(\/)+/', '', $path);

        //     $path = '/'.$path;

        //     $path = preg_replace('/(\/)+$/', '', $path);

        //     return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$path;
        // }

        private function PlaceReferer() {

            if (isset($_SERVER['HTTP_REFERER'])) return;

            setcookie('_referer', CurrentLocation(), time()+60*30);
        }

        public function GetReferer(): ?string {

            if (isset($_SERVER['HTTP_REFERER'])) return $_SERVER['HTTP_REFERER'];

            if (array_key_exists('_referer', $_COOKIE)) {
                return $_COOKIE['_referer'];
            }
            else {
                
                setcookie('_referer', $this->currentLocation, time()+60*30);
                return $this->currentLocation;
            }
        }

        private function IsRelativeLink(string $_link) {
            //  Simple regex pattern to check a if a link is a url
            $reg = '/^https?:\/\/.+[a-zA-Z\/]$/';

            return !preg_match($reg, $_link);
        }

    }