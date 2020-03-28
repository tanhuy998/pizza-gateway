<?php
    namespace Dependencies\Parsing;

    use Dependencies\Http\Request as Request;


    class URLParser {

        public function ParseUriParameters(Request $_request): array {
            $request_uri = $_request->Path();
            
            $request_real_uri = $this->RemoveSubrootDirectory($request_uri);

            $route_uri_pattern = $_request->Route()->GetUriPattern();

            preg_match_all('/\{(.+?)\}/', $route_uri_pattern, $matches);

            $route_params = $matches[1];

            $keys = preg_replace('/\{|\}/', '', $route_uri_pattern);
            $keys = explode('/', $keys);

            $values = explode('/', $request_real_uri);
            
            $arr = array_combine($keys, $values);
        
            $callback = function ($key) use ($route_params) {
            
                return in_array($key, $route_params);
            };

            return array_filter($arr ,$callback, ARRAY_FILTER_USE_KEY);
        }

        public function PatternMatch(string $_subject, string $_pattern): bool {
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

        public function RemoveSubrootDirectory(string $_uri): string {
            $subroot_dir = SubRootDir();

            $regex = '/'.$subroot_dir.'/';

            $return_uri = preg_replace($regex, '', $_uri);
            $return_uri = preg_replace('/(\/)+/', '/', $return_uri);
            
            return $return_uri;
            //return $subroot_dir != '' ? str_replace(SubRootDir().'/', '', $_uri) : $_uri;
        }

        public function StandardizePattern(string &$_pattern) {
            $first_char = substr($_pattern,0,1);
            $last_char = substr($_pattern, strlen($_pattern)-1, 1);

            $_pattern = preg_replace('/^(\/)+/', '', $_pattern);

            $_pattern = '/'.$_pattern;
            
            $_pattern = $_pattern !== '/' ? preg_replace('/(\/)+$/', '', $_pattern): $_pattern;
            // $_pattern = $first_char == '/' ? substr($_pattern,1, strlen($_pattern) -1): $_pattern;
            // $_pattern = $last_char == '/' ? substr($_pattern,0,strlen($_pattern) -1): $_pattern;
        }
    }