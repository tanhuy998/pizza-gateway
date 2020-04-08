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
use Exception;

class DomainHandler extends EventClient {

        private $Domains;

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

        public function Manage(string $_pattern, closure $_callback) {
            
            $this->Validate($_pattern);

            if (!isset($this->Domains[$_pattern])) {
                $this->Domains[$_pattern] = [];
            }

            $this->flag = true;

            $this->stack[] = $_pattern;

            $container = \Application\Container\DIContainer::GetInstance();

            $container->call($_callback);

            if (empty($this->stack)) $this->flag = false;
        }

        private function Validate(string $_pattern) {

            $domain_regex_pattern = '/^((\w|\{\w+\})([\w\-]*|\{\w+\})*(\w|\{\w+\})\.)?(\w|\{\w+\})+(\.([a-z]|\{[a-z]+\})+)?$/';

            // check if the domain pattern is valid
            if (!preg_match($domain_regex_pattern, $_pattern)) throw new Exception();

            // check duplication of domain pattern's parameters
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

            $this->Domains[$current_SettingDomain][] = $createdRoute;

            array_pop($this->stack);
        }
    }