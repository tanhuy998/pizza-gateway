<?php
    require_once 'autoload_map.php';

    /**
     * Autoloader class define an autoloader for classes that are using accross the app
     *  
     */
    class Autoloader {
        private static $autoLoaderObject;

        private $map;

        private function __construct() {
            $this->LoadMap();
        }


        private function LoadMap() {
            $this->map = include('autoload_map.php');

            return $this->map;
        }

        /**
         * Function that return the map property of this object
         * @return array Associative array that loaded from src/autoload_map.php
         */
        public function GetMap() {
            return $this->map;
        }


        /**
         * Function that load unmapped class which is not define in autoload's mapping array
         * @param string the fully qualified class name
         * @return void 
         */
        private function LoadUnmappedClass($_className) {
            $base_path = getenv('BASE_PATH');

            $_className = str_replace('\\', '/', $_className);

            $full_file_path_name = $base_path.'/'.$_className.'.php';

            if (file_exists($full_file_path_name)) {
                require_once($full_file_path_name);

                return;
            }

            $full_file_path_name = $base_path.'/src/'.$_className.'.php';

            if (file_exists($full_file_path_name)) {
                require_once($full_file_path_name);

                return; 
            }

            $full_file_path_name = $base_path.'/'.strtolower($_className).'.php';

            if (file_exists($full_file_path_name)) {
                require_once($full_file_path_name);

                return;
            }

            $full_file_path_name = $base_path.'/src/'.strtolower($_className).'.php';

            if (file_exists($full_file_path_name)) {
                require_once($full_file_path_name);

                return;
            }

            throw new Autoload\ClassNotDefinedException($_className);
            // else {
                
            
            //     else {
                    
            //         throw new Autoload\ClassNotDefinedException($_className);
            //     }
            // }
        }


        /**
         * Function that return the singleton object of this class
         * @return Autoloader Singleton object of this class
         */
        private static function GetLoader() {
            if (self::$autoLoaderObject !== null) {
                return self::$autoLoaderObject;
            }

            self::$autoLoaderObject = new self();
            return self::$autoLoaderObject;
        }
        
        
        /**
         * Begin register for unloaded classes when it is call to make new object
         * @return void
         */
        public static function Load() {
            spl_autoload_register(function (string $_className) {

                $class_map = self::GetLoader()->map;
                //var_dump($class_map);
                //$class_map = $class_map->$map;

                if (isset($class_map[$_className])) {
                    $Class_dir = $class_map[$_className];
                    if (file_exists($Class_dir)) {
                        require_once($Class_dir);
                    }
                }
                else {
                    self::GetLoader()->LoadUnmappedClass($_className);
                }

                // $Class_dir = $class_map[$_className];

                // if ($Class_dir !== null) {
                //     include($Class_dir);
                // }
            });
        }
    }