<?php
    namespace Dependencies\HttpHandler;

    use Dependencies\Http\Request as Request;

    class HttpHandler {

        private function __construct() {

        }

        public static function Request() {
            $request_method = $_SERVER['REQUEST_METHOD'];

            $hidden_method = $_POST['re_method'] ?? null;

            //  handle for recieving request from a form submitting case
            //  check for the value of hidden input named 're_method'
            if ($hidden_method && $request_method == 'POST') {
                $hidden_method == strtoupper($hidden_method);

                return new Request($hidden_method);
            }

            //  handle for recieving request from a form submitting case
            //  when there is no hidden input submitted from request
            //  the request method is now 'POST'
            if (!$hidden_method && $request_method == 'POST') {
                return new Request('POST');
            }

            if ($request_method == 'GET') {
                
                return new Request();
            }
            
            //  handle for receiving request from ajax/xhr protocol
            //  request method (such as PUT and DELETE)
            //  can be specified directly
            //  by ajax/xhr request
            //  so we haven't to check for hidden input
            return new Request($request_method);

        }
    }