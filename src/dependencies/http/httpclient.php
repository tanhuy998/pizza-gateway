<?php
    namespace Dependencies\Http;

use Closure;
use Dependencies\Http\Request as Request;
    use Dependencies\Http\Respone as Respone;
    use Application\Container\DIContainer;
use Exception;

class HttpClient {

        protected $responseHeaders;
        protected $requestHeaders;

        protected $request;
        protected $endpoints;
        protected $callbacks;
        protected $response;
        //protected $body;

        public function __construct() {

            $this->requestHeaders = [];
            $this->responseHeaders = [];
            //$this->endpoints = [];
        }

        public function Forward(Request $_request) {
            
            $this->request = $_request;

            return $this;
        }

        public function To(string $_endpoint) {

            if (is_null($this->request)) throw new Exception();

            $this->endpoints = $_endpoint;

            return $this;
        }

        public function Then(Closure $_callback) {

            if (is_null($this->endpoints)) throw new Exception();
            $this->callbacks[] = $_callback;

            return $this;
        }

        public function Return() {

            if (is_null($this->endpoints)) throw new Exception();

            $ch = $this->prepare($this->request, $this->endpoints);

            $res_arr = $this->send($ch);

            $container = DIContainer::GetInstance();

            foreach ($this->callbacks as $callback) {

                if ($callback instanceof Closure) {

                    $container->Call($callback, [$res_arr]);
                }
            }

            return $res_arr;
        }

        protected function Prepare(Request $_request, string $_endpoint) {
            
            $uri = $_request->Uri();

            $endpoint = preg_replace('/\/+$/', '', $_endpoint);

            $url = $endpoint.$uri;
            var_dump($url);
            $headers = getallheaders();
            $ch = curl_init($url);
            $method = $_request->Method();
            $request_body = file_get_contents('php://input');
            //curl_setopt($ch, CURLOPT_URL, 'https://localhost/pizza/public/admin/category');
        
        
            $option = [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HEADER => TRUE,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_POSTFIELDS => $request_body,    
                CURLOPT_FOLLOWLOCATION => true,
            ];
        
            curl_setopt_array($ch, $option);
            $hd = [];
        
            foreach ($headers as $name => $value) {
                if ($name === 'Host') continue;

                if ($name === 'Content-Length') {
                    $hd[] = $name.': '.strlen($request_body);
                    continue;
                }
                $hd[] = $name.': '.$value;
            }

            $hd[] = 'Host: '.'piz-api.herokuapp.com';
        
            curl_setopt($ch, CURLOPT_HTTPHEADER, $hd);
            curl_setopt_array($ch, $option);

            return $ch;
        }

        protected function Send($ch) {
            $respone = curl_exec($ch);
        
            $output = $respone;

            // close curl resource to free up system resources
            //curl_close($ch);

            $headers = [];
            $output = rtrim($output);
            $data = explode("\n",$output);
            $headers['status'] = $data[0];
            array_shift($data);

            foreach($data as $part){

            //some headers will contain ":" character (Location for example), and the part after ":" will be lost, Thanks to @Emanuele
                $middle = explode(":",$part,2);

            //Supress warning message if $middle[1] does not exist, Thanks to @crayons
                if ( !isset($middle[1]) ) { $middle[1] = null; }

                $headers[trim($middle[0])] = trim($middle[1]);
            }
        
            $headers_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $body = substr($respone, $headers_size);

            curl_close($ch);

            return [
                'headers' => $headers,
                'body' => $body
            ];
        }

        public function RequestHeader(array $_headers) {

            $this->requestHeaders = array_merge($this->requestHeaders, $_headers);
        }

        public function ResponseHeader(array $_headers) {

            $this->responseHeaders = array_merge($this->responseHeaders, $_headers);
        }
    }