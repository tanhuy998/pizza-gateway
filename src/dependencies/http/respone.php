<?php  
    namespace Dependencies\Http;

use Application\Container\DIContainer;
use ReflectionFunction;

class Respone {

        const MIDDLEWARE_PASS = 200;

        private $container;
        private $content;
        private $status;
        private $cookies;
        private $headers;

        public function __construct(int $_status = 200, DIContainer $_container) {
            //ob_start();
            $this->container = $_container;
            $this->status = $_status;
            $this->content = '';
            $this->cookies = [];
            $this->headers = [];
        }

        public function Header(string $_key, $_value) {
            $this->headers[$_key] = $_value;

            return $this;
        }

        private function SendHeader() {
            $container = $this->container;

            array_walk($this->headers, function($_value, $_key) use($container) {
                $header_content = $_key.': '.$_value;

                $container->call('header', [$header_content]);
            });
        }

        public function Status(int $_option = null) {
            if(!is_null($_option)) {
                http_response_code($_option);
            }

            return http_response_code();
        }

        public function Render($_content) {
            
            $this->ResolveContent($_content);
        }

        private function ResolveContent($_content) {
            if (is_array($_content)) {
                $this->content = json_encode($_content);
                $this->Header('Content-Type', 'application/json');
            }

            if (is_string($_content)) {
                $this->content = $_content;
                $this->Header('Content-Type', 'text/HTML;charset=utf-8');
            }
        }

        // public function Status($_option = null) {
        //     if (!is_null($_option)) {
        //         $this->status = $_option;
                
        //         return;
        //     }

        //     return $this->status;
        // }

        public function Cookie($_option, string  $_value = '', int $_expires = 0, string $_path = '', string $_domain = '', bool $_secure = FALSE, bool $_http_only = FALSE) {
        
            if (is_string($_option)) {
                $cookie = [];
                $cookie['name'] = $_option;
                $cookie['value'] = $_value;
                $cookie['expires'] = $_expires;
                $cookie['path'] = $_path;
                $cookie['domain'] = $_path;
                $cookie['secure'] = $_path;
                $cookie['httponly'] = $_path;

                $this->SaveCookie($cookie);
            }

            if (is_array($_option)) {
                $this->SaveCookie($_option);
            }

            return $this;
        }

        public function DeleteCookie(string $_name) {
            $this->Cookie($_name,'', time()-3600*24*365);
        }

        private function SaveCookie(array $_cookie) {
            //$setCookie = new ReflectionFunction('setcookie');
            $this->cookies[] = $_cookie;
            //$this->container->Call('setcookie', $_cookie);
        }

        protected function SendCookies() {
            $container = $this->container;

            array_walk($this->cookies, function ($cookie) use($container) {
                $container->Call('setcookie', $cookie);
            });
        }

        public function Send() {

            
            echo $this->content;
            $this->SendHeader();
            $this->SendCookies();
            ob_end_flush();
            //ob_end_clean();
        }
    }