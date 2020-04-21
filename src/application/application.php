<?php 
    namespace Application;

    use Application\Container\DIContainer as DIContainer;
    use Dependencies\Http;
    use Dependencies\Router\Route;
    use Dependencies\Router\Router as Router;
    use Dependencies\Event\EventEmitter as EventEmitter;
use Dependencies\Http\Request;
use Dependencies\Notification\EventClient;
use Dependencies\Parsing\DirectoryParser as DirectoryParser;
use Exception;

/**
     *  
     */
    class Application extends EventEmitter {
        private const APP_PENDING = '300';
        public const EVENT_ON_CONFIG = 'onConfig';
        public const EVENT_ON_SERVICES_LOAD = 'onRouting';
        public const EVENT_ON_RESPONE = 'onRespone';

        private $appState;
        private $directories;
        protected $basePath;

        private $arbitraries;

        public $container;
        public $router;

        private $parser;
        private $request;

        public function __construct(Request $_request) {

            $this->basePath = BasePath();   
            $this->request = $_request;
            $this->directories = [];
            $this->arbitraries = [];
            $this->parser = new DirectoryParser();
            $this->appState = self::APP_PENDING;
            
            $this->container = DIContainer::GetInstance();

            $this->Init();
        }

        public function Init() {
            $this->AddEvent(self::EVENT_ON_CONFIG);
        }

        public function Start(array $_init_list = null) {
            $this->Config();

            if (!is_null($_init_list)) {
                $this->RegisterCustomDependencies($_init_list);
            }
            $this->router = $this->container->Get('router');
        }

        public function Terminate() {

            exit;
        }

        private function Config() {
            $container = $this->container;

            $container->BindSingleton(self::class, self::class, $this)->Name('app');
            
            $container->BindSingleton(\Dependencies\Router\Router::class, \Dependencies\Router\Router::class, 
                function () use($container) {
                
                    return new \Dependencies\Router\Router($container);
                }
            )->name('router');

            $container->BindSingleton(\Dependencies\Http\Request::class, \Dependencies\Http\Request::class, $this->request)
                        ->Name('request');

            $container->BindSingleton(\Dependencies\Http\Respone::class, \Dependencies\Http\Respone::class)
                        ->Name('respone');


            $this->Emit(self::EVENT_ON_CONFIG);
        }

        private function RegisterCustomDependencies(array $_list) {

            foreach ($_list as $abstract => $concrete) {
                $this->container->Bind($abstract, $concrete);
            }
        }
        
        public function BindDir(string $_name, string $_path) {
            
            $parser = $this->parser;

            if ($parser->Isdirectory($_path)) {
                $this->directories[$_name] = $_path;
            }
            
        }

        public function HasDir(string $_name) {
            return isset($this->directories[$_name]);
        }

        public function File(string $_file_name) {

            $parser = $this->parser;

            $dir = null;

            if ($parser->Isdirectory($_file_name)) {
                $dir = $_file_name;
            } 
            else if ($this->HasDir($_file_name)) {
                $dir = $this->directories[$_file_name];
            }

            if (is_null($dir)) {
                throw new Exception();
            } 

            $file_content = file_get_contents($dir);

            if ($file_content === false) throw new Exception();

            return $file_content;
        }

        public function Directory(string $_name) {

            if (isset($this->directories[$_name])) {

                return $this->basePath.$this->directories[$_name];
            }

            return null;
        }   

        public function BindArbitrary($_param1, $_param2 = null) {
            if (is_string($_param1) && is_string($_param2)) {
                $key = $_param1;
                $value =$_param2;

                $this->arbitraries[$key] = $value;
            }

            if (is_array($_param1)) {
                foreach ($_param1 as $key => $value) {
                    $this->arbitraries[$key] = $value;
                }
            }
        }

        public function HasArbitrary(string $_name) {
            return isset($this->arbitraries[$_name]);
        }

        public function Arbitrary(string $_name) {

            if ($this->HasArbitrary($_name)) {
                return $this->arbitraries[$_name];
            }

            return null;
        }
    }