<?php  
    namespace Dependencies\Http;

use Application\Container\DIContainer;
use ReflectionFunction;

class Respone {

        const MIDDLEWARE_PASS = 200;
        const RENDER_OVERIDE = 10;
        const RENDER_CONTINOUS = 11;

        private $container;
        private $content;
        private $httpStatus;
        private $cookies;
        private $headers;
        private $renderMode;

        //private $middlewaresPassed;

        static protected $httpResponseCode = [
            '100' => 'Continue',
            '101' => 'Swicthing Protocol',
            '102' => 'Processing',
            '103' => 'Early Hints',
            '200' => 'OK',
            '201' => 'Created',
            '202' => 'Accepted',
            '203' => 'Non-Authoritative Information',
            '204' => 'No Content',
            '205' => 'Reset Content',
            '206' => 'Partial Content',
            '207' => 'Multi-Status',
            '208' => 'Already Reported',
            '226' => 'IM Used',
            '300' => 'Multiple Choice',
            '301' => 'Moved Permanently',
            '302' => 'Found',
            '303' => 'See Other',
            '304' => 'Not Modified',
            '305' => 'Use Proxy',
            '306' => 'Unused',
            '307' => 'Temporary Redirect',
            '308' => 'Permanent Redirect',
            '400' => 'Bad Request',
            '401' => 'Unathorized',
            '402' => 'Payment Required',
            '403' => 'Forbiden',
            '404' => 'Not Found',
            '405' => 'Method Not Allowed',
            '406' => 'Not Acceptable',
            '407' => 'Proxy Authentication Required',
            '408' => 'Request Timeout',
            '409' => 'Conflict',
            '410' => 'Gone',
            '411' => 'Length Required',
            '412' => 'Precondition Failed',
            '413' => 'Payload Too Large',
            '414' => 'URI Too Long',
            '415' => 'Unsupported Media Type',
            '416' => 'Range Not Satisfiable',
            '417' => 'Expectation Failed',
            '418' => 'I\'m a teapot',
            '421' => 'Misdirected Request',
            '422' => 'Unprocessable Entity',
            '423' => 'Locked',
            '424' => 'Failed Dependency',
            '425' => 'Too Early',
            '426' => 'Upgrade Required',
            '428' => 'Precondition Required',
            '429' => 'Too Many Request',
            '431' => 'Request Header Field Too Large',
            '451' => 'Unavailable For Legal Reasons',
            '500' => 'Internal Server Error',
            '501' => 'Not Implemented',
            '502' => 'Bad Gateway',
            '503' => 'Service Unavailable',
            '504' => 'Gateway Timeout',
            '505' => 'HTTP Version Not Supported',
            '506' => 'Variants Also Negotaites',
            '507' => 'Insufficient Storage',
            '508' => 'Loop Detected',
            '510' => 'Not Extend',
            '511' => 'Network Authentication Required'
        ]; 

        public function __construct(int $_status = 200, DIContainer $_container) {
            //ob_start();
            $this->container = $_container;
            $this->httpStatus = $_status;
            $this->content = '';
            $this->cookies = [];
            $this->headers = [];

            $this->renderMode = self::RENDER_CONTINOUS;
        }

        public function Header(string $_key, $_value) {
            $this->headers[$_key] = $_value;
            //var_dump($_value);
            return $this;
        }

        private function SendHeader() {
            $container = $this->container;

            //var_dump($this->header);
            array_walk($this->headers, function($_value, $_key) use($container) {
                $header_content = $_key.': '.$_value;
                //echo $header_content;
                // $container->call('header', [$header_content]);
                header($header_content);
                //var_dump($_value);
                //var_dump($header_content);
            });

            
        }

        public function Status(int $_code = null) {
            if(!is_null($_code)) {
                //http_response_code($_option);
                $code = strval($_code);

                $this->httpStatus = array_key_exists($code, self::$httpResponseCode)
                                    ? $_code : $this->httpStatus;
            }

            return $this->httpStatus;
        }

        public function Render($_content, int $_mode = self::RENDER_CONTINOUS) {

            $this->renderMode = ($_mode === self::RENDER_CONTINOUS || $_mode === self::RENDER_OVERIDE)
                                ? $_mode : $this->renderMode;

            $this->ResolveContent($_content);
        }

        private function ResolveContent($_content) {
            if (is_array($_content)) {
                $content = json_encode($_content);
                $this->Header('Content-Type', 'application/json');
            }

            if (is_string($_content)) {
                $content = $_content;
                //$this->Header('Content-Type', 'text/HTML;charset=utf-8');
            }

            if (isset($content)) {
                if ($this->renderMode === self::RENDER_CONTINOUS) {
                    $this->content .= $content;
                }
                else {
                    $this->content = $content;
                }
            }
        }

        public function GetContent() {

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

            http_response_code($this->httpStatus);
            $this->SendContent();
            $this->SendHeader();
            $this->SendCookies();

            //  Place referer
            if (!isset($_SERVER['HTTP_REFERER'])) {
                setcookie('_referer', CurrentLocation(), time()+60*30);
            }

            ob_end_flush();
            //ob_end_clean();
        }

        private function SendContent() {
            echo $this->content;
        }
    }