<?php
    namespace Dependencies\Router;

use Application\Application;
use Application\Container\Dependency;
use Closure;
    use Dependencies\Router\Route as Route;
    use Dependencies\Router\Router as Router;
    use Dependencies\Http\Request as Request;
    use Dependencies\Http\Respone as Respone;
    use Dependencies\Notification\EventClient as EventClient;

    class DomainHandler extends EventClient {

        private $subDomains;

        private $alias;

        private $router;

        private $flag;

        private $stack;

        public $route;

        public function __construct(Router $_router) {
            $this->router = $_router;
            $this->stack = [];

            $this->SubscribeEvent($_router, 'onCreateRoute');
        }

        public function SubDomain(string $_pattern, closure $_callback) {
            
            $this->ValidateParameters($_pattern);

            if (!isset($this->subDomains[$_pattern])) {
                $this->subDomains[$_pattern] = [];
            }

            $this->flag = true;

            $this->stack[] = $_pattern;

            $container = \Application\Container\DIContainer::GetInstance();

            $container->call($_callback);

            if (empty($this->stack)) $this->flag = false;
        }

        private function ValidateParameters(string $_pattern) {

            preg_match_all('/\{(.+?)\}/', $_pattern, $matches);

            $params = array_count_values($matches[1]);

            foreach ($params as $name => $time) {
                if ($time > 1) throw new \Exception("");
            }
        }

        // private function ResolveDomainPattern(string $_name) {

        //     return preg_replace('/\{.+?\}/', '{param}', $_name);
        // }

        protected function HandleEventNotification(\Dependencies\Event\EventArgs $_notification) {
            
            if ($_notification instanceof RouteCreateNotification) {
                $this->OnRouteCreated($_notification);
            }
        }
        
        private function OnRouteCreated(RouteCreateNotification $_notification) {

            if ($this->flag === false) return;

            $createdRoute = $_notification->GetCreatedRoute();

            $current_SettingDomain = end($this->stack);

            $createdRoute->SetSubDomain($current_SettingDomain);

            //$pattern = $this->ResolveDomainPattern($current_SettingDomain);

            $this->subDomains[$current_SettingDomain][] = $createdRoute;

            array_pop($this->stack);
        }
    }