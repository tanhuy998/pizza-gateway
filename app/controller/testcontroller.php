<?php 
    namespace App\Controller;

    use Dependencies\Http\Request as Request;

    Class TestController {
        public function Index(Request $request) {
            return $request->Path();
        }
    }